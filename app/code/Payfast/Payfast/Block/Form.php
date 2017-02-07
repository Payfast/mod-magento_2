<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfast\Payfast\Block\Payfast;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Payfast\Payfast\Model\Config;
use Payfast\Payfast\Model\Payfast\Checkout;

class Form extends \Magento\Payment\Block\Form
{
    /** @var string Payment method code */
    protected $_methodCode = Config::METHOD_CODE;

    /** @var \Payfast\Payfast\Helper\Data */
    protected $_payfastData;

    /** @var \Payfast\Payfast\Model\ConfigFactory */
    protected $payfastConfigFactory;

    /** @var ResolverInterface */
    protected $_localeResolver;

    /** @var \Payfast\Payfast\Model\Config */
    protected $_config;

    /** @var bool */
    protected $_isScopePrivate;

    /** @var CurrentCustomer */
    protected $currentCustomer;

    /**
     * @param Context $context
     * @param \Payfast\Payfast\Model\ConfigFactory $payfastConfigFactory
     * @param ResolverInterface $localeResolver
     * @param \Payfast\Payfast\Helper\Data $payfastData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Payfast\Payfast\Model\ConfigFactory $payfastConfigFactory,
        ResolverInterface $localeResolver,
        \Payfast\Payfast\Helper\Data $payfastData,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $this->_payfastData = $payfastData;
        $this->payfastConfigFactory = $payfastConfigFactory;
        $this->_localeResolver = $localeResolver;
        $this->_config = null;
        $this->_isScopePrivate = true;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context, $data);
        $this->_logger->debug( $pre . "eof" );
    }

    /**
     * Set template and redirect message
     *
     * @return void
     */
    protected function _construct()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $this->_config = $this->payfastConfigFactory->create()->setMethod( $this->getMethodCode() );
        parent::_construct();
    }

    /**
     * Payment method code getter
     *
     * @return string  'payfast'
     */
    public function getMethodCode()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

        return $this->_methodCode;
    }




}
