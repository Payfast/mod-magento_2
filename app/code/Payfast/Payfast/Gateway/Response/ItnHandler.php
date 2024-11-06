<?php

namespace Payfast\Payfast\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use UnexpectedValueException;

/**
 * ItnHandler class
 */
class ItnHandler implements HandlerInterface
{
    public const TXN_ID = 'TXN_ID';

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        // Check if $payment is an instance of the expected interface or class
        if ($payment instanceof Payment) {
            $payment->setTransactionId($response[self::TXN_ID]);
            $payment->setIsTransactionClosed(false);
        } else {
            throw new UnexpectedValueException('Payment object does not implement OrderPaymentInterface.');
        }
    }
}
