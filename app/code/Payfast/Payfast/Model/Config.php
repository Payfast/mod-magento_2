<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Model;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Api\StoreManagementInterface;
use Psr\Log\LoggerInterface;

/**
 * Config model that is aware of all \Payfast\Payfast payment methods
 * Works with Payfast-specific system configuration
 */
class Config extends AbstractConfig
{

    /**
     *
     */
    public const METHOD_CODE = 'payfast';

    /**
     * @var string should this module send confirmation email
     */
    public const KEY_SEND_CONFIRMATION_EMAIL = 'allowed_confirmation_email';

    /**
     * @var string should this module send invoice email
     */
    public const KEY_SEND_INVOICE_EMAIL = 'allowed_confirmation_email';

    /**
     *
     */
    protected $directoryHelper;

    /**
     *
     */
    protected $_supportedBuyerCountryCodes = ['ZA'];

    /**
     * Currency codes supported by Payfast methods
     *
     */
    protected $_supportedCurrencyCodes = ['ZAR'];
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var Repository
     */
    protected $_assetRepo;
    protected StoreManagementInterface $_storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $directoryHelper
     * @param StoreManagementInterface $storeManager
     * @param LoggerInterface $logger
     * @param Repository $assetRepo
     * @param UrlInterface $urlBuilder
     * @param array $params
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $directoryHelper,
        StoreManagementInterface $storeManager,
        LoggerInterface $logger,
        Repository $assetRepo,
        UrlInterface $urlBuilder,
        $params = []
    ) {
        $this->_logger = $logger;
        parent::__construct($scopeConfig);
        $this->directoryHelper = $directoryHelper;
        $this->_storeManager   = $storeManager;
        $this->_assetRepo      = $assetRepo;
        $this->_urlBuilder     = $urlBuilder;

        if ($params) {
            $method = array_shift($params);
            $this->setMethod($method);
            if ($params) {
                $storeId = array_shift($params);
                $this->setStoreId($storeId);
            }
        }
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @return string
     * @see    \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     * @see    \Magento\Checkout\Controller\Onepage::savePaymentAction()
     */
    public function getCheckoutRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        return $this->_urlBuilder->getUrl('payfast/redirect');
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
     * Get the payment cancelled url
     *
     * @return string
     */
    public function getPaidCancelUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/redirect/cancel', ['_secure' => true]);
    }

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
     * Check whether method available for checkout or not. Logic based on merchant country, methods dependence
     *
     * @param string|null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        // This method override is kept for potential future modifications
        // or to maintain consistency in method signatures.
        return parent::isMethodAvailable($methodCode);
    }

    /**
     * Return buyer country codes supported by Payfast
     *
     */
    public function getSupportedBuyerCountryCodes()
    {
        return $this->_supportedBuyerCountryCodes;
    }

    /**
     * Return merchant country code, use default country if it's not specified in General settings
     *
     * @return string
     */
    public function getMerchantCountry()
    {
        return $this->directoryHelper->getDefaultCountry($this->_storeId);
    }

    /**
     * Check whether method supported for specified country or not. Use $_methodCode and merchant country by default
     *
     * @param string|null $method
     * @param string|null $countryCode
     *
     * @return bool
     */
    public function isMethodSupportedForCountry($method = null, $countryCode = null)
    {
        if ($method === null) {
            $method = $this->getMethodCode();
        }

        if ($countryCode === null) {
            $countryCode = $this->getMerchantCountry();
        }

        return in_array($method, $this->getCountryMethods($countryCode));
    }

    /**
     * Return list of allowed methods for specified country iso code
     *
     * @param string|null $countryCode 2-letters iso code
     *
     * @return array
     */
    public function getCountryMethods($countryCode = null)
    {
        $countryMethods = [
            'other' => [
                self::METHOD_CODE,
            ],

        ];
        if ($countryCode === null) {
            return $countryMethods;
        }

        return isset($countryMethods[$countryCode]) ? $countryMethods[$countryCode] : $countryMethods['other'];
    }

    /**
     * Get Payfast "mark" image URL. May be his can be place in the config xml
     *
     * @return string
     */
    public function getPaymentMarkImageUrl()
    {
        return $this->_assetRepo->getUrl('Payfast_Payfast::images/logo.svg');
    }

    /**
     * Get "What Is Payfast" localized URL. Supposed to be used with "mark" as popup window
     *
     * @return string
     */
    public function getPaymentMarkWhatIsPayfast()
    {
        return 'Payfast Payment gateway';
    }

    /**
     * Mapper from Payfast-specific payment actions to Magento payment actions
     *
     * @return string|null
     */
    public function getPaymentAction()
    {
        $paymentAction = null;
        $pre           = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $action = $this->getValue('paymentAction');

        $this->_logger->debug($pre . 'payment action is : ' . $action);

        switch ($action) {
            case self::PAYMENT_ACTION_AUTH:
                $paymentAction = self::ACTION_AUTHORIZE;
                break;
            case self::PAYMENT_ACTION_SALE:
                $paymentAction = self::ACTION_AUTHORIZE_CAPTURE;
                break;
            case self::PAYMENT_ACTION_ORDER:
                $paymentAction = self::ACTION_ORDER;
                break;
        }

        $this->_logger->debug($pre . 'eof : paymentAction is ' . $paymentAction);

        return $paymentAction;
    }

    /**
     * Check whether specified currency code is supported
     *
     * @param string $code
     *
     * @return bool
     */
    public function isCurrencyCodeSupported($code)
    {
        $supported = false;
        $pre       = __METHOD__ . ' : ';

        $this->_logger->debug($pre . "bof and code: {$code}");

        if (in_array($code, $this->_supportedCurrencyCodes)) {
            $supported = true;
        }

        $this->_logger->debug($pre . "eof and supported : {$supported}");

        return $supported;
    }

    /**
     * Map Payfast config fields
     *
     * @param string $fieldName
     *
     * @return string|null
     */
    protected function _mapPayfastFieldset($fieldName)
    {
        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     *
     * @return string|null
     */
    protected function _getSpecificConfigPath($fieldName)
    {
        return $this->_mapPayfastFieldset($fieldName);
    }
}
