<?php

namespace Payfast\Payfast\Gateway\Request;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Psr\Log\LoggerInterface;

class CaptureRequest implements BuilderInterface
{

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(ConfigInterface $config, LoggerInterface $logger)
    {
        $this->config = $config;

        $this->logger = $logger;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $pre = __METHOD__ . ':';

        $this->logger->debug($pre . 'bof');

        if (!isset($buildSubject['payment']) || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];

        $order = $paymentDO->getOrder();

        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        $this->logger->debug($pre . 'bof');

        return [
            'TXN_TYPE'     => 'S',
            'TXN_ID'       => $payment->getLastTransId(),
            'MERCHANT_KEY' => $this->config->getValue('merchant_gateway_key', $order->getStoreId())
        ];
    }
}
