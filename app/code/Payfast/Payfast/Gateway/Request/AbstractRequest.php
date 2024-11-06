<?php

namespace Payfast\Payfast\Gateway\Request;

use InvalidArgumentException;
use LogicException;
use Magento\Setup\Module\Dependency\Report\BuilderInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Psr\Log\LoggerInterface;

/**
 * AbstractRequest class
 */
abstract class AbstractRequest implements BuilderInterface
{

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

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
     * Common build logic
     *
     * @param array $buildSubject
     * @param string $txnType
     * @param string $configKey
     *
     * @return array
     * @throws InvalidArgumentException|LogicException
     */
    protected function buildRequest(array $buildSubject, string $txnType, string $configKey): array
    {
        $pre = __METHOD__ . ':';

        $this->logger->debug($pre . 'bof');

        if (!isset($buildSubject['payment']) || !$buildSubject['payment'] instanceof PaymentDataObjectInterface) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new LogicException('Order payment should be provided.');
        }

        $this->logger->debug($pre . 'bof');

        return [
            'TXN_TYPE'     => $txnType,
            'TXN_ID'       => $payment->getLastTransId(),
            'MERCHANT_KEY' => $this->config->getValue($configKey, $order->getStoreId())
        ];
    }
}
