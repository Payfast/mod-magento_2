<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AbstractConfig
 */
abstract class AbstractConfig extends \Magento\Payment\Gateway\Config\Config implements ConfigInterface
{
    /**
* #@+
     * Payment actions
     */
    const PAYMENT_ACTION_SALE = 'Sale';

    const PAYMENT_ACTION_AUTH = 'Authorization';

    const PAYMENT_ACTION_ORDER = 'Order';

    const ACTION_ORDER = 'order';

    const ACTION_AUTHORIZE = 'authorize';

    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * #@-
     */

    /**
     * Current payment method code
     *
     * @var string
     */
    protected $_methodCode;

    /**
     * Current store id
     *
     * @var int
     */
    protected $_storeId;

    /**
     * @var string
     */
    protected $pathPattern;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    public $_scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig, self::getMethodCode());
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * @return null|string
     */
    public function isActive()
    {
        return $this->getValue('active');
    }
    /**
     * Sets method instance used for retrieving method specific data
     *
     * @param MethodInterface $method
     *
     * @return $this
     */
    public function setMethodInstance($method)
    {
        $this->methodInstance = $method;

        return $this;
    }

    /**
     * Method code setter
     *
     * @param string|MethodInterface $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        if ($method instanceof MethodInterface) {
            $this->_methodCode = $method->getCode();
        } elseif (is_string($method)) {
            $this->_methodCode = $method;
        }

        return $this;
    }

    /**
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }

    /**
     * Store ID setter
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;

        return $this;
    }

    /**
     * Returns payment configuration value
     *
     * @param string $key
     * @param null   $storeId
     *
     * @return null|string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValue($key, $storeId = null)
    {
        $underscored = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key));
        $path = $this->_getSpecificConfigPath($underscored);

        if ($path !== null) {
            $value = $this->_scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );
            $value = $this->_prepareValue($underscored, $value);

            return $value;
        }

        return null;
    }

    /**
     * Sets method code
     *
     * @param string $methodCode
     *
     * @return void
     */
    public function setMethodCode($methodCode)
    {
        $this->_methodCode = $methodCode;
    }

    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     *
     * @return void
     */
    public function setPathPattern($pathPattern)
    {
        $this->pathPattern = $pathPattern;
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
        if ($this->pathPattern) {
            return sprintf($this->pathPattern, $this->_methodCode, $fieldName);
        }

        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    /**
     * Perform additional config value preparation and return new value if needed
     *
     * @param string $key   Underscored key
     * @param string $value Old value
     *
     * @return string Modified value or old value
     */
    protected function _prepareValue($key, $value)
    {
        return $value;
    }

    /**
     * Check whether method available for checkout or not
     *
     * @param null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        $methodCode = $methodCode ?: $this->_methodCode;

        return $this->isMethodActive($methodCode);
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isMethodActive($method)
    {
        switch ($method) {
        case Config::METHOD_CODE:
            $isEnabled = $this->_scopeConfig->isSetFlag(
                'payment/' . $this->getMethodCode() . '/active',
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            ) ||
                $this->_scopeConfig->isSetFlag(
                    'payment/' . $this->getMethodCode() . '/active',
                    ScopeInterface::SCOPE_STORE,
                    $this->_storeId
                );
            $method = $this->getMethodCode();
            break;
        default:
            $isEnabled = $this->_scopeConfig->isSetFlag(
                "payment/{$method}/active",
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );
        }

        return $this->isMethodSupportedForCountry($method) && $isEnabled;
    }

    /**
     * Check whether method supported for specified country or not
     *
     * @param string|null $method
     * @param string|null $countryCode
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isMethodSupportedForCountry($method = null, $countryCode = null)
    {
        return true;
    }
}
