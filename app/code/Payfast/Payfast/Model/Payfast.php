<?php

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedExceptionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Payfast\Payfast\Block\Form;
use Payfast\Payfast\Block\Payment\Info;
use Magento\Framework\App\ProductMetadataInterface;
use Payfast\Payfast\Logger\Logger as Monolog;
use Magento\Sales\Model\Order;

/**
 * Payfast Module.
 *
 */
class Payfast
{
    /**
     * @var string
     */
    protected string $_code = Config::METHOD_CODE;

    /**
     * @var string
     */
    protected string $_formBlockType = Form::class;

    /**
     * @var string
     */
    protected string $_infoBlockType = Info::class;

    /**
     * @var string
     */
    protected string $_configType = Config::class;

    /**
     * Availability option
     *
     * @var bool
     */
    protected bool $_canOrder = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected bool $_canCapture = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected bool $_canUseInternal = true;

    /**
     * Website Payments Pro instance
     *
     * @var Config $config
     */
    protected Config $_config;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $_storeManager;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $_urlBuilder;

    /**
     * @var Session
     */
    protected Session $_checkoutSession;

    /**
     * @var LocalizedExceptionFactory
     */
    protected LocalizedExceptionFactory $_exception;

    /**
     * @var TransactionRepositoryInterface
     */
    protected TransactionRepositoryInterface $transactionRepository;

    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $transactionBuilder;
    protected Monolog $payfastLogger;

    /**
     * @param ConfigFactory $configFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Session $checkoutSession
     * @param LocalizedExceptionFactory $exception
     * @param TransactionRepositoryInterface $transactionRepository
     * @param BuilderInterface $transactionBuilder
     * @param Monolog $payfastLogger
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
     * @param int|Store $store
     *
     * @return $this
     * @throws NoSuchEntityException
     */
    public function setStore(Store|int $store): static
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
    public function canUseForCurrency(string $currencyCode): bool
    {
        return $this->_config->isCurrencyCodeSupported($currencyCode);
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @return string
     * @see    \Magento\Sales\Model\Payment::place()
     */
    public function getConfigPaymentAction(): string
    {
        return $this->_config->getPaymentAction();
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        return $this->_config->isMethodAvailable();
    }

    /**
     * This where we compile data posted by the form to payfast
     *
     * @var Order $order
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
     * @return string
     */
    public function getTotalAmount(Order $order): string
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
     * @param float $number
     *
     * @return string
     */
    public function getNumberFormat(float $number): string
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * Get the successful url for a paid transaction
     *
     * @return string
     */
    public function getPaidSuccessUrl(): string
    {
        return $this->_urlBuilder->getUrl('payfast/redirect/success', ['_secure' => true]);
    }

    /**
     * Get the order placement url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        $pre = __METHOD__ . ' : ';
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
    public function getCheckoutRedirectUrl(): string
    {
        $pre = __METHOD__ . ' : ';
        $this->payfastLogger->info($pre . 'bof');

        return $this->_urlBuilder->getUrl('payfast/redirect');
    }

    /**
     * Get the payment cancelled url
     *
     * @return string
     */
    public function getPaidCancelUrl(): string
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
    public function getPaidNotifyUrl(): string
    {
        return $this->_urlBuilder->getUrl('payfast/notify', ['_secure' => true]);
    }

    /**
     * Get the Payfast url
     *
     * @return string
     */
    public function getPayfastUrl(): string
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
    public function getPayfastHost(string $serverMode): string
    {
        if (!in_array($serverMode, ['live', 'test'])) {
            $pfHost = "payfast.$serverMode";
        } else {
            $pfHost = (($serverMode == 'live') ? 'www' : 'sandbox') . '.payfast.co.za';
        }

        return $pfHost;
    }

    /**
     * Get  the name of the store
     *
     * @return string
     */
    protected function getStoreName(): string
    {
        $pre = __METHOD__ . ' : ';
        $this->payfastLogger->info($pre . 'bof');

        $storeName = $this->_config->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
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
    protected function getOrderTransaction(OrderPaymentInterface $payment): false|TransactionInterface
    {
        try {
            return $this->transactionRepository->getByTransactionType(
                TransactionInterface::TYPE_ORDER,
                $payment->getId()
            );
        } catch (InputException $e) {
            $this->payfastLogger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Get the version number of the app
     *
     * @return string
     */
    private function getAppVersion(): string
    {
        $objectManager = ObjectManager::getInstance();
        $version       = $objectManager->get(ProductMetadataInterface::class)->getVersion();

        return (preg_match('([0-9])', $version)) ? $version : '2.0.0';
    }
}
