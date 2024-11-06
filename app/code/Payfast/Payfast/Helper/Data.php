<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Helper;

use Magento\Framework\App\Config\BaseFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Payment\Helper\Data as helperData;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Payfast\Payfast\Model\ConfigFactory;
use Psr\Log\LoggerInterface;

/**
 * Payfast Data helper
 */
class Data extends AbstractHelper
{

    /**
     * Cache for shouldAskToCreateBillingAgreement()
     *
     * @var bool
     */
    protected static bool $_shouldAskToCreateBillingAgreement = false;

    /**
     * @var helperData
     */
    protected helperData $_paymentData;
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param Context $context
     * @param helperData $paymentData
     */
    public function __construct(
        Context $context,
        helperData $paymentData
    ) {
        $this->_logger = $context->getLogger();

        $pre = __METHOD__ . ' : ';

        $this->_paymentData  = $paymentData;

        parent::__construct($context);
        $this->_logger->debug($pre . 'eof');
    }

    /**
     * Check if customer should be asked confirmation whether to sign a billing agreement should always return false.
     *
     * @return bool
     */
    public function shouldAskToCreateBillingAgreement(): bool
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $this->_logger->debug($pre . 'eof');

        return self::$_shouldAskToCreateBillingAgreement;
    }

    /**
     * Retrieve available billing agreement methods
     *
     * @param bool|int|string|Store|null $store
     * @param Quote|null $quote
     *
     * @return MethodInterface[]
     */
    public function getBillingAgreementMethods(Store|bool|int|string $store = null, Quote $quote = null): array
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $result = [];

        foreach ($this->_paymentData->getPaymentMethodList() as $method) {
            if ($method instanceof MethodInterface) {
                $result[] = $method;
            }
        }
        $this->_logger->debug($pre . 'eof | result : ', $result);

        return $result;
    }
}
