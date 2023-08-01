<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Helper;

use Magento\Framework\App\Config\BaseFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Payfast\Payfast\Model\ConfigFactory;
use Psr\Log\LoggerInterface;
use \Magento\Payment\Helper\Data as helperData;

/**
 * PayFast Data helper
 */
class Data extends AbstractHelper
{

    /**
     * Cache for shouldAskToCreateBillingAgreement()
     *
     * @var bool
     */
    protected static $_shouldAskToCreateBillingAgreement = false;

    /**
     * @var helperData
     */
    protected $_paymentData;

    /**
     * @var array
     */
    private $methodCodes;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param Context     $context
     * @param helperData  $paymentData
     * @param BaseFactory $configFactory
     * @param array       $methodCodes
     */
    public function __construct(Context $context, helperData $paymentData, BaseFactory $configFactory, array $methodCodes)
    {
        $this->_logger = $context->getLogger();

        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof, methodCodes is : ', $methodCodes);

        $this->_paymentData = $paymentData;
        $this->methodCodes = $methodCodes;
        $this->configFactory = $configFactory;

        parent::__construct($context);
        $this->_logger->debug($pre . 'eof');
    }

    /**
     * Check whether customer should be asked confirmation whether to sign a billing agreement
     * should always return false.
     *
     * @return bool
     */
    public function shouldAskToCreateBillingAgreement()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . "bof");
        $this->_logger->debug($pre . "eof");

        return self::$_shouldAskToCreateBillingAgreement;
    }

    /**
     * Retrieve available billing agreement methods
     *
     * @param null|string|bool|int|Store $store
     * @param Quote|null                 $quote
     *
     * @return MethodInterface[]
     */
    public function getBillingAgreementMethods($store = null, $quote = null)
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');
        $result = [ ];

//        foreach ($this->_paymentData->getStoreMethods($store, $quote) as $method) {
        foreach ($this->_paymentData->getPaymentMethodList() as $method) {
            if ($method instanceof MethodInterface) {
                $result[] = $method;
            }
        }
        $this->_logger->debug($pre . 'eof | result : ', $result);

        return $result;
    }
}
