<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Block;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Payfast\Payfast\Helper\Data;
use Payfast\Payfast\Model\Config;
use Payfast\Payfast\Model\ConfigFactory;
use Payfast\Payfast\Model\PayfastConfigProvider;

/**
 * Form class
 */
class Form extends \Magento\Payment\Block\Form
{
    /**
     * @var string Payment method code
     */
    protected string $_methodCode = Config::METHOD_CODE;

    /**
     * @var Data
     */
    protected Data $_payfastData;

    /**
     * @var ConfigFactory|PayfastConfigProvider
     */
    protected PayfastConfigProvider|ConfigFactory $payfastConfigFactory;

    /**
     * @var ResolverInterface
     */
    protected ResolverInterface $_localeResolver;

    /**
     * @var Config
     */
    protected Config $_config;

    /**
     * @var bool
     */
    protected $_isScopePrivate;

    /**
     * @var CurrentCustomer
     */
    protected CurrentCustomer $currentCustomer;

    /**
     * @param Context $context
     * @param PayfastConfigProvider $payfastConfigFactory
     * @param ResolverInterface $localeResolver
     * @param Data $payfastData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        PayfastConfigProvider $payfastConfigFactory,
        ResolverInterface $localeResolver,
        Data $payfastData,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $pre                        = __METHOD__ . ' : ';
        $this->_payfastData         = $payfastData;
        $this->payfastConfigFactory = $payfastConfigFactory;
        parent::__construct($context, $data);
        $this->_logger->debug($pre . 'bof');
        $this->_localeResolver = $localeResolver;

        $this->_isScopePrivate = true;
        $this->currentCustomer = $currentCustomer;
        $this->_logger->debug($pre . 'eof');
    }

    /**
     * Payment method code getter
     *
     * @return string  'payfast'
     */
    public function getMethodCode(): string
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        // Call the parent method to retain any parent functionality
        $parentMethodCode = parent::getMethodCode();

        // You can choose to either return the parent method's value
        // or use the existing logic if needed
        return $this->_methodCode ?? $parentMethodCode;
    }

    /**
     * Set template and redirect message
     *
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();

        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $this->_config = $this->payfastConfigFactory->create()->setMethod($this->getMethodCode());
    }
}
