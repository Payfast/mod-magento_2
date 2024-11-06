<?php

namespace Payfast\Payfast\Gateway\Request;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payfast\Payfast\Gateway\Http\Client\ClientMock;

/**
 * MockDataRequest class
 */
class MockDataRequest implements BuilderInterface
{
    public const FORCE_RESULT = 'FORCE_RESULT';

    /**
     * Builds ENV request. We would then compose our data in here.
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment']) || !$buildSubject['payment'] instanceof PaymentDataObjectInterface) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment   = $paymentDO->getPayment();

        $transactionResult = $payment->getAdditionalInformation('transaction_result');

        return [
            self::FORCE_RESULT => $transactionResult === null
                ? ClientMock::SUCCESS
                : $transactionResult
        ];
    }
}
