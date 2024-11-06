<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
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
    public const PAYMENT_ACTION_SALE = 'Sale';

    public const PAYMENT_ACTION_AUTH = 'Authorization';

    public const PAYMENT_ACTION_ORDER = 'Order';

    public const ACTION_ORDER = 'order';

    public const ACTION_AUTHORIZE = 'authorize';

    public const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    public ScopeConfigInterface $_scopeConfig;
    /**
     * Current payment method code
     *
     * @var string
     */
    protected string $_methodCode = '';
    /**
     * Current store id
     *
     * @var int
     */
    protected int $_storeId = 0;
    /**
     * @var string
     */
    protected string $pathPattern = '';
    /**
     * @var MethodInterface
     */
    protected MethodInterface $methodInstance;

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
     * Check if is active
     *
     * @return null|string
     */
    public function isActive(): ?string
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
    public function setMethodInstance(MethodInterface $method): static
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
    public function setMethod(MethodInterface|string $method): static
    {
        if ($method instanceof MethodInterface) {
            $this->_methodCode = $method->getCode();
        } else {
            $this->_methodCode = $method;
        }

        return $this;
    }

    /**
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode(): string
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
    public function setStoreId(int $storeId): static
    {
        $this->_storeId = $storeId;

        return $this;
    }

    /**
     * Returns payment configuration value
     *
     * @param string $field
     * @param int $storeId
     *
     * @return string
     *
     */
    public function getValue($field, $storeId = null): string
    {
        // Call the parent method to retain its behavior, if applicable
        $parentValue = parent::getValue($field, $storeId);

        $underscored = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $field));
        $path        = $this->_getSpecificConfigPath($underscored);

        if ($path !== null) {
            $value = $this->_scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );

            return $this->_prepareValue($underscored, $value ?? '');
        }

        // Return the parent method result or an empty string if there's no path
        return $parentValue !== '' ? $parentValue : '';
    }

    /**
     * Sets method code
     *
     * @param string $methodCode
     *
     * @return void
     */
    public function setMethodCode($methodCode): void
    {
        // Call the parent class's setMethodCode method if it exists
        parent::setMethodCode($methodCode);

        // Custom logic
        $this->_methodCode = $methodCode;
    }

    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     *
     * @return void
     */
    public function setPathPattern($pathPattern): void
    {
        // Call the parent class's setMethodCode method if it exists
        parent::setPathPattern($pathPattern);

        // Custom logic
        $this->pathPattern = $pathPattern;
    }

    /**
     * Check whether method available for checkout or not
     *
     * @param string|null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable(string $methodCode = null): bool
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
     */
    public function isMethodActive(string $method): bool
    {
        switch ($method) {
            case Config::METHOD_CODE:
                $isEnabled = $this->_scopeConfig->isSetFlag(
                    'payment/' . $this->getMethodCode() . '/active',
                    ScopeInterface::SCOPE_STORE,
                    $this->_storeId
                );
                $method    = $this->getMethodCode();
                break;
            default:
                $isEnabled = $this->_scopeConfig->isSetFlag(
                    "payment/$method/active",
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
     */
    public function isMethodSupportedForCountry(string $method = null, string $countryCode = null): bool
    {
        return true;
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     *
     * @return string|null
     */
    protected function _getSpecificConfigPath(string $fieldName): ?string
    {
        if ($this->pathPattern) {
            return sprintf($this->pathPattern, $this->_methodCode, $fieldName);
        }

        return "payment/$this->_methodCode/$fieldName";
    }

    /**
     * Perform additional config value preparation and return new value if needed
     *
     * @param string $key Underscored key
     * @param string $value Old value
     *
     * @return string Modified value or old value
     */
    protected function _prepareValue(string $key, string $value): string
    {
        return $value;
    }
}
