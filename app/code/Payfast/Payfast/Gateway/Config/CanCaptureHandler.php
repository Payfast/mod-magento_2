<?php

namespace Payfast\Payfast\Gateway\Config;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

/**
 * CanCaptureHandler class
 */
class CanCaptureHandler implements ValueHandlerInterface
{
    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return bool
     */
    public function handle(array $subject, $storeId = null): bool
    {
        $paymentDO = SubjectReader::readPayment($subject);

        $payment = $paymentDO->getPayment();

        return $payment instanceof Payment && !$payment->getAmountPaid();
    }
}
