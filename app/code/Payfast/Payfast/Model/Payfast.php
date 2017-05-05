<?php

/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Model;

include_once( dirname( __FILE__ ) .'/../Model/payfast_common.inc' );

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payfast extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = Config::METHOD_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = 'Payfast\Payfast\Block\Form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Payfast\Payfast\Block\Payment\Info';

    /** @var string */
    protected $_configType = 'Payfast\Payfast\Model\Config';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = false;

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
    protected $_canAuthorize = true;

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
    protected $_canVoid = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * Website Payments Pro instance
     *
     * @var \Payfast\Payfast\Model\Config $config
     */
    protected $_config;
    /**
     * Payment additional information key for payment action
     *
      * @var string
     */
    protected $_isOrderPaymentActionKey = 'is_order_action';

    /**
     * Payment additional information key for number of used authorizations
     *
     * @var string
     */
    protected $_authorizationCountKey = 'authorization_count';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Exception\LocalizedExceptionFactory
     */
    protected $_exception;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Payfast\Payfast\Model\ConfigFactory $configFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Payfast\Payfast\Model\CartFactory $cartFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct( \Magento\Framework\Model\Context $context,
                                 \Magento\Framework\Registry $registry,
                                 \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
                                 \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
                                 \Magento\Payment\Helper\Data $paymentData,
                                 \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                 \Magento\Payment\Model\Method\Logger $logger,
                                 ConfigFactory $configFactory,
                                 \Magento\Store\Model\StoreManagerInterface $storeManager,
                                 \Magento\Framework\UrlInterface $urlBuilder,
                                 \Magento\Checkout\Model\Session $checkoutSession,
                                 \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
                                 \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
                                 \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
                                 \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
                                 \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
                                 array $data = [ ] )
    {
        parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data );
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_checkoutSession = $checkoutSession;
        $this->_exception = $exception;
        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder = $transactionBuilder;

        $parameters = [ 'params' => [ $this->_code ] ];

        $this->_config = $configFactory->create( $parameters );

        if (! defined('PF_DEBUG'))
        {
            define('PF_DEBUG', $this->getConfigData('debug'));
        }

    }


    /**
     * Store setter
     * Also updates store ID in config object
     *
     * @param \Magento\Store\Model\Store|int $store
     *
     * @return $this
     */
    public function setStore( $store )
    {
        $this->setData( 'store', $store );

        if ( null === $store )
        {
            $store = $this->_storeManager->getStore()->getId();
        }
        $this->_config->setStoreId( is_object( $store ) ? $store->getId() : $store );

        return $this;
    }


    /**
     * Whether method is available for specified currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency( $currencyCode )
    {
        return $this->_config->isCurrencyCodeSupported( $currencyCode );
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see \Magento\Sales\Model\Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->_config->getPaymentAction();
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable( \Magento\Quote\Api\Data\CartInterface $quote = null )
    {
        return parent::isAvailable( $quote ) && $this->_config->isMethodAvailable();
    }


    /**
     * @return mixed
     */
    protected function getStoreName()
    {
        $pre = __METHOD__ . " : ";
        pflog( $pre . 'bof' );

        $storeName = $this->_scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        pflog( $pre . 'store name is '. $storeName );

        return $storeName;
    }

    /**
     * this where we compile data posted by the form to payfast
     * @return array
     */
    public function getStandardCheckoutFormFields()
    {
        $pre = __METHOD__ . ' : ';
        // Variable initialization

        $order = $this->_checkoutSession->getLastRealOrder();

        $description = '';

        $this->_logger->debug($pre . 'serverMode : '. $this->getConfigData( 'server' ));

        // If NOT test mode, use normal credentials
        if( $this->getConfigData( 'server' ) == 'live' )
        {
            $merchantId = $this->getConfigData( 'merchant_id' );
            $merchantKey = $this->getConfigData( 'merchant_key' );
        }
        // If test mode, use generic sandbox credentials
        else
        {
            $merchantId = '10000100';
            $merchantKey = '46f0cd694581a';
        }

        // Create description
        foreach( $order->getAllItems() as $items )
        {
            $description .= $this->getNumberFormat( $items->getQtyOrdered() ) . ' x ' . $items->getName() .';';
        }

        $pfDescription = trim( substr( $description, 0, 254 ) );

        // Construct data for the form
        $data = array(
            // Merchant details
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => $this->getPaidSuccessUrl(),
            'cancel_url' => $this->getPaidCancelUrl(),
            'notify_url' => $this->getPaidNotifyUrl(),

            // Buyer details
            'name_first' => $order->getData( 'customer_firstname' ),
            'name_last' => $order->getData( 'customer_lastname' ),
            'email_address' => $order->getData( 'customer_email' ),

            // Item details
            'm_payment_id' => $order->getRealOrderId(),
            'amount' => $this->getTotalAmount( $order ),
            'item_name' => $this->_storeManager->getStore()->getName() .', Order #'. $order->getRealOrderId(),
             //this html special characters breaks signature.
            //'item_description' => $pfDescription,
        );

        $pfOutput = '';
        // Create output string
        foreach( $data as $key => $val )
        {
            if (!empty( $val ))
            {
                $pfOutput .= $key .'='. urlencode( $val ) .'&';
            }
        }

        $passPhrase = $this->getConfigData('passphrase');
        $pfOutput = substr( $pfOutput, 0, -1 );

        if ( !empty( $passPhrase ) && $this->getConfigData('server') !== 'test' )
        {
            $pfOutput = $pfOutput."&passphrase=".urlencode( $passPhrase );
        }

        pflog( $pre . 'pfOutput for signature is : '. $pfOutput );

        $pfSignature = md5( $pfOutput );

        $data['signature'] = $pfSignature;
        $data['user_agent'] = 'Magento ' . $this->getAppVersion();
        pflog( $pre . 'data is :'. print_r( $data, true ) );
        $this->logger->debug( $data );

        return( $data );
    }

    /**
     * getAppVersion
     *
     * @return string
     */
    private function getAppVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $version = $objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();

        return  (preg_match('([0-9])', $version )) ? $version : '2.0.0';
    }
    /**
     * getTotalAmount
     */
    public function getTotalAmount( $order )
    {
        if( $this->getConfigData( 'use_store_currency' ) )
            $price = $this->getNumberFormat( $order->getGrandTotal() );
        else
            $price = $this->getNumberFormat( $order->getBaseGrandTotal() );

        return $price;
    }

    /**
     * getNumberFormat
     */
    public function getNumberFormat( $number )
    {
        return number_format( $number, 2, '.', '' );
    }

    /**
     * getPaidSuccessUrl
     */
    public function getPaidSuccessUrl()
    {
        return $this->_urlBuilder->getUrl( 'payfast/redirect/success', array( '_secure' => true ) );
    }

    /**
     * Get transaction with type order
     *
     * @param OrderPaymentInterface $payment
     *
     * @return false|\Magento\Sales\Api\Data\TransactionInterface
     */
    protected function getOrderTransaction( $payment )
    {
        return $this->transactionRepository->getByTransactionType( Transaction::TYPE_ORDER, $payment->getId(), $payment->getOrder()->getId() );
    }

    /*
     * called dynamically by checkout's framework.
     */
    public function getOrderPlaceRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        pflog( $pre . 'bof' );

        return $this->_urlBuilder->getUrl( 'payfast/redirect' );

    }
     /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        pflog( $pre . 'bof' );

        return $this->_urlBuilder->getUrl( 'payfast/redirect' );
    }

    /**
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     */
    public function initialize( $paymentAction, $stateObject )
    {
        $pre = __METHOD__ . " : ";
        pflog( $pre . 'bof' );

        $stateObject->setState( \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT );
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified( false );

        return parent::initialize( $paymentAction, $stateObject ); // TODO: Change the autogenerated stub

    }

    /**
     * getPaidCancelUrl
     */
    public function getPaidCancelUrl()
    {
        return $this->_urlBuilder->getUrl( 'payfast/redirect/cancel', array( '_secure' => true ) );
    }
    /**
     * getPaidNotifyUrl
     */
    public function getPaidNotifyUrl()
    {
        return $this->_urlBuilder->getUrl( 'payfast/notify', array( '_secure' => true ) );
    }

    /**
     * getPayFastUrl
     *
     * Get URL for form submission to PayFast.
     */
    public function getPayFastUrl()
    {

        return( 'https://'. $this->getPayfastHost( $this->getConfigData('server') ) . '/eng/process' );
    }

    /**
     * @param $serverMode
     *
     * @return string
     */
    public function getPayfastHost( $serverMode )
    {
        if ( !in_array( $serverMode, [ 'live', 'test' ] ) )
        {
            $pfHost = "www.payfast.{$serverMode}";
        }
        else
        {
            $pfHost = ( ( $serverMode == 'live' ) ? 'www' : 'sandbox' ) . '.payfast.co.za';
        }

        return $pfHost;
    }
}
