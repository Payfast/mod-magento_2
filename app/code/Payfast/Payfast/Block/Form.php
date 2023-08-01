<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Block;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Payfast\Payfast\Helper\Data;
use Payfast\Payfast\Model\Config;
use Payfast\Payfast\Model\PayfastConfigProvider;

class Form extends \Magento\Payment\Block\Form
{
    /**
     * @var string Payment method code
     */
    protected $_methodCode = Config::METHOD_CODE;

    /**
     * @var Data
     */
    protected $_payfastData;

    /**
     * @var \Payfast\Payfast\Model\ConfigFactory
     */
    protected $payfastConfigFactory;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Payfast\Payfast\Model\Config
     */
    protected $_config;

    /**
     * @var bool
     */
    protected $_isScopePrivate;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @param Context                              $context
     * @param \Payfast\Payfast\Model\ConfigFactory $payfastConfigFactory
     * @param ResolverInterface                    $localeResolver
     * @param Data         $payfastData
     * @param CurrentCustomer                      $currentCustomer
     * @param array                                $data
     */
    public function __construct(
        Context $context,
        PayfastConfigProvider $payfastConfigFactory,
        ResolverInterface $localeResolver,
        Data $payfastData,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $pre = __METHOD__ . " : ";
        $this->_payfastData = $payfastData;
        $this->payfastConfigFactory = $payfastConfigFactory;
        parent::__construct($context, $data);
        $this->_logger->debug($pre . 'bof');
        $this->_localeResolver = $localeResolver;

        $this->_isScopePrivate = true;
        $this->currentCustomer = $currentCustomer;
        $this->_logger->debug($pre . "eof");
    }

    /**
     * Set template and redirect message
     *
     * @return void
     */
    protected function _construct()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');
        $this->_config = $this->payfastConfigFactory->create()->setMethod($this->getMethodCode());
    }

    /**
     * Payment method code getter
     *
     * @return string  'payfast'
     */
    public function getMethodCode()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        return $this->_methodCode;
    }
}
