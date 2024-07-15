<?php

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedExceptionFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Payfast\PayfastCommon;
use Payfast\Payfast\Block\Form;
use Payfast\Payfast\Block\Payment\Info;
use Payfast\Payfast\Model\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Payfast\Payfast\Logger\Logger as Monolog;

/**
 * Payfast Module.
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Payfast
{
    /**
     * @var string
     */
    protected $_code = Config::METHOD_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = Form::class;

    /**
     * @var string
     */
    protected $_infoBlockType = Info::class;

    /**
     * @var string
     */
    protected $_configType = Config::class;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Website Payments Pro instance
     *
     * @var Config $config
     */
    protected $_config;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var LocalizedExceptionFactory
     */
    protected $_exception;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;
    protected Monolog $payfastLogger;

    /**
     * @param ConfigFactory $configFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Session $checkoutSession
     * @param LocalizedExceptionFactory $exception
     * @param TransactionRepositoryInterface $transactionRepository
     * @param BuilderInterface $transactionBuilder
     */
    public function __construct(
        ConfigFactory $configFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        Session $checkoutSession,
        LocalizedExceptionFactory $exception,
        TransactionRepositoryInterface $transactionRepository,
        BuilderInterface $transactionBuilder,
        Monolog $payfastLogger
    ) {
        $this->_storeManager         = $storeManager;
        $this->_urlBuilder           = $urlBuilder;
        $this->_checkoutSession      = $checkoutSession;
        $this->_exception            = $exception;
        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder    = $transactionBuilder;
        $this->payfastLogger         = $payfastLogger;

        $parameters = ['params' => [$this->_code]];

        $this->_config = $configFactory->create($parameters);

        if (!defined('PF_DEBUG')) {
            define('PF_DEBUG', $this->_config->getValue('debug'));
        }
    }

    /**
     * Store setter. Also updates store ID in config object
     *
     * @param Store|int $store
     *
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setStore($store)
    {
        $this->setData('store', $store);

        if (null === $store) {
            $store = $this->_storeManager->getStore()->getId();
        }
        $this->_config->setStoreId(is_object($store) ? $store->getId() : $store);

        return $this;
    }

    /**
     * Whether method is available for specified currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->_config->isCurrencyCodeSupported($currencyCode);
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @return string
     * @see    \Magento\Sales\Model\Payment::place()
     */
    public function getConfigPaymentAction()
    {
        return $this->_config->getPaymentAction();
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return $this->_config->isMethodAvailable();
    }

    /**
     * This where we compile data posted by the form to payfast
     *
     * @return array
     */
    public function getStandardCheckoutFormFields(): array
    {
        $pre = __METHOD__ . ' : ';
        // Variable initialization

        $order = $this->_checkoutSession->getLastRealOrder();

        $description = '';

        $this->payfastLogger->info($pre . 'serverMode : ' . $this->_config->getValue('server'));

        // If NOT test mode, use normal credentials
        if ($this->_config->getValue('server') == 'live') {
            $merchantId  = $this->_config->getValue('merchant_id');
            $merchantKey = $this->_config->getValue('merchant_key');
        } else {
            // If test mode, use generic / specific sandbox credentials
            $merchantId  = !empty($this->_config->getValue('merchant_id')) ?
                $this->_config->getValue('merchant_id') :
                '10000100';
            $merchantKey = !empty($this->_config->getValue('merchant_key')) ?
                $this->_config->getValue('merchant_key') :
                '46f0cd694581a';
        }

        // Create description
        foreach ($order->getAllItems() as $items) {
            $description .= $this->getNumberFormat($items->getQtyOrdered()) . ' x ' . $items->getName() . ';';
        }

        $pfDescription = trim(substr($description, 0, 254));

        // Construct data for the form
        $data = [
            // Merchant details
            'merchant_id'   => $merchantId,
            'merchant_key'  => $merchantKey,
            'return_url'    => $this->getPaidSuccessUrl(),
            'cancel_url'    => $this->getPaidCancelUrl(),
            'notify_url'    => $this->getPaidNotifyUrl(),

            // Buyer details
            'name_first'    => $order->getData('customer_firstname'),
            'name_last'     => $order->getData('customer_lastname'),
            'email_address' => $order->getData('customer_email'),

            // Item details
            'm_payment_id'  => $order->getRealOrderId(),
            'amount'        => $this->getTotalAmount($order),
            'item_name'     => 'Order #' . $order->getRealOrderId(),
            //this html special characters breaks signature.
            //'item_description' => $pfDescription,
        ];

        $pfOutput = '';
        // Create output string
        foreach ($data as $key => $val) {
            if (!empty($val)) {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        $passPhrase = $this->_config->getValue('passphrase');
        if (!empty($passPhrase)) {
            $pfOutput .= 'passphrase=' . urlencode($passPhrase);
        } else {
            $pfOutput = rtrim($pfOutput, '&');
        }

        $this->payfastLogger->info($pre . 'pfOutput for signature is : ' . $pfOutput);

        //@codingStandardsIgnoreStart
        $pfSignature = md5($pfOutput);
        //@codingStandardsIgnoreEnd

        $data['signature']  = $pfSignature;
        $data['user_agent'] = 'Magento ' . $this->getAppVersion();
        $this->payfastLogger->info($pre . 'data is :' . json_encode($data));

        return ($data);
    }

    /**
     * Get the total amount from the order
     *
     * @param Order $order
     *
     * @return float
     */
    public function getTotalAmount($order): string
    {
        if ($this->_config->getValue('use_store_currency')) {
            $price = $this->getNumberFormat($order->getGrandTotal());
        } else {
            $price = $this->getNumberFormat($order->getBaseGrandTotal());
        }

        return $price;
    }

    /**
     * Formats the number
     *
     * @param int $number
     *
     * @return float
     */
    public function getNumberFormat($number)
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * Get the successful url for a paid transaction
     *
     * @return string
     */
    public function getPaidSuccessUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/redirect/success', ['_secure' => true]);
    }

    /**
     * Get the order placement url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        $this->payfastLogger->info($pre . 'bof');

        return $this->_urlBuilder->getUrl('payfast/redirect');
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @return string
     * @see    Quote\Payment::getCheckoutRedirectUrl()
     * @see    \Magento\Checkout\Controller\Onepage::savePaymentAction()
     */
    public function getCheckoutRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        $this->payfastLogger->info($pre . 'bof');

        return $this->_urlBuilder->getUrl('payfast/redirect');
    }

    /**
     * Get the payment cancelled url
     *
     * @return string
     */
    public function getPaidCancelUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/redirect/cancel', ['_secure' => true]);
    }

    /*
     * called dynamically by checkout's framework.
     */

    /**
     * Get the payment notify url
     *
     * @return string
     */
    public function getPaidNotifyUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/notify', ['_secure' => true]);
    }

    /**
     * Get the Payfast url
     *
     * @return string
     */
    public function getPayfastUrl()
    {
        return 'https://' . $this->getPayfastHost($this->_config->getValue('server')) . '/eng/process';
    }

    /**
     * Gett the Payfast host
     *
     * @param string $serverMode
     *
     * @return string
     */
    public function getPayfastHost($serverMode)
    {
        if (!in_array($serverMode, ['live', 'test'])) {
            $pfHost = "payfast.{$serverMode}";
        } else {
            $pfHost = (($serverMode == 'live') ? 'www' : 'sandbox') . '.payfast.co.za';
        }

        return $pfHost;
    }

    /**
     * Get  the name of the store
     *
     * @return mixed
     */
    protected function getStoreName()
    {
        $pre = __METHOD__ . " : ";
        $this->payfastLogger->info($pre . 'bof');

        $storeName = $this->_config->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $this->payfastLogger->info($pre . 'store name is ' . $storeName);

        return $storeName;
    }

    /**
     * Get transaction with type order
     *
     * @param OrderPaymentInterface $payment
     *
     * @return false|TransactionInterface
     */
    protected function getOrderTransaction($payment)
    {
        return $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_ORDER,
            $payment->getId(),
            $payment->getOrder()->getId()
        );
    }

    /**
     * Get the version number of the app
     *
     * @return string
     */
    private function getAppVersion(): string
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $version       = $objectManager->get(ProductMetadataInterface::class)->getVersion();

        return (preg_match('([0-9])', $version)) ? $version : '2.0.0';
    }
}
