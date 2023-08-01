<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
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
 * Works with PayFast-specific system configuration
 *
 * @SuppressWarnings(PHPMD.ExcesivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Config extends AbstractConfig
{

    /**
 * @var Payfast this is a model which we will use.
*/
    const METHOD_CODE = 'payfast';

    /**
 * @var string should this module send confirmation email
*/
    const KEY_SEND_CONFIRMATION_EMAIL = 'allowed_confirmation_email';

    /**
 * @var string should this module send invoice email
*/
    const KEY_SEND_INVOICE_EMAIL = 'allowed_confirmation_email';

    /**
     * Core data
     */
    protected $directoryHelper;

    protected $_supportedBuyerCountryCodes = ['ZA'];

    /**
     * Currency codes supported by PayFast methods @var string[]
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

    /**
     * @param ScopeConfigInterface     $scopeConfig
     * @param Data                     $directoryHelper
     * @param StoreManagementInterface $storeManager
     * @param LoggerInterface          $logger
     * @param Repository               $assetRepo
     * @param UrlInterface             $urlBuilder
     * @param array                    $params
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
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_urlBuilder = $urlBuilder;

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
     * @see    \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see    \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        return $this->_urlBuilder->getUrl('payfast/redirect');
    }
    /**
     * getPaidSuccessUrl
     */
    public function getPaidSuccessUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/redirect/success', [ '_secure' => true ]);
    }

    /**
     * getPaidCancelUrl
     */
    public function getPaidCancelUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/redirect/cancel', [ '_secure' => true ]);
    }
    /**
     * getPaidNotifyUrl
     */
    public function getPaidNotifyUrl()
    {
        return $this->_urlBuilder->getUrl('payfast/notify', [ '_secure' => true ]);
    }

    /**
     * Check whether method available for checkout or not
     * Logic based on merchant country, methods dependence
     *
     * @param                                        string|null $methodCode
     * @return                                       bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isMethodAvailable($methodCode = null)
    {
        return parent::isMethodAvailable($methodCode);
    }

    /**
     * Return buyer country codes supported by PayFast
     *
     * @return string[]
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
     * Check whether method supported for specified country or not
     * Use $_methodCode and merchant country by default
     *
     * @param  string|null $method
     * @param  string|null $countryCode
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
     * @param                                         string|null $countryCode 2-letters iso code
     * @return                                        array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
     * Get PayFast "mark" image URL
     * may be his can be place in the config xml
     *
     * @return string
     */
    public function getPaymentMarkImageUrl()
    {
        return $this->_assetRepo->getUrl('Payfast_Payfast::images/logo.png');
    }

    /**
     * Get "What Is PayFast" localized URL
     * Supposed to be used with "mark" as popup window
     *
     * @return string
     */
    public function getPaymentMarkWhatIsPayfast()
    {
        return 'PayFast Payment gateway';
    }

    /**
     * Mapper from PayFast-specific payment actions to Magento payment actions
     *
     * @return string|null
     */
    public function getPaymentAction()
    {
        $paymentAction = null;
        $pre = __METHOD__ . ' : ';
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
     * @param  string $code
     * @return bool
     */
    public function isCurrencyCodeSupported($code)
    {
        $supported = false;
        $pre = __METHOD__ . ' : ';

        $this->_logger->debug($pre . "bof and code: {$code}");

        if (in_array($code, $this->_supportedCurrencyCodes)) {
            $supported = true;
        }

        $this->_logger->debug($pre . "eof and supported : {$supported}");

        return $supported;
    }

    /**
     * _mapPayFastFieldset
     * Map PayFast config fields
     *
     * @param                                        string $fieldName
     * @return                                       string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _mapPayFastFieldset($fieldName)
    {
        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param                                        string $fieldName
     * @return                                       string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getSpecificConfigPath($fieldName)
    {
        return $this->_mapPayFastFieldset($fieldName);
    }
}
