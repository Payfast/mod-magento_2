<?php

namespace Payfast\Payfast\Gateway\Request;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use Exception;
use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payfast\Payfast\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * AuthorizationRequest class
 */
class AuthorizationRequest implements BuilderInterface
{

    /**
     * @var Config
     */
    private Config $payfastConfig;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param Config $payfastConfig
     */
    public function __construct(ConfigInterface $config, LoggerInterface $logger, Config $payfastConfig)
    {
        $this->config = $config;

        $this->logger = $logger;

        $this->payfastConfig = $payfastConfig;
    }

    /**
     * Builds ENV request if this was a cc payment then we would append cc fields in and dispatch it.
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        $pre = __METHOD__ . ' : ';

        $this->logger->debug($pre . 'bof');

        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
        try {
            $payment = $buildSubject['payment'];

            $order = $payment->getOrder();

            $address = $order->getBillingAddress();

            $merchantId  = $this->config->getValue('merchant_id', $order->getStoreId());
            $merchantKey = $this->config->getValue('merchant_key', $order->getStoreId());
            $data        = [
                // Merchant details
                'merchant_id'   => $merchantId,
                'merchant_key'  => $merchantKey,
                'return_url'    => $this->payfastConfig->getPaidSuccessUrl(),
                'cancel_url'    => $this->payfastConfig->getPaidCancelUrl(),
                'notify_url'    => $this->payfastConfig->getPaidNotifyUrl(),

                // Buyer details
                'name_first'    => $address->getFirstname(),
                'name_last'     => $address->getLastname(),
                'email_address' => $address->getEmail(),

                // Item details
                'm_payment_id'  => $order->getOrderIncrementId(),
                'amount'        => $order->getGrandTotalAmount(),

                'item_name' => 'Order #' . $order->getOrderIncrementId(),
                'currency'  => $order->getCurrencyCode(),

            ];
            $pfOutput    = '';
            // Create output string
            foreach ($data as $key => $val) {
                if (!empty($val) && $key !== 'currency') {
                    $pfOutput .= $key . '=' . urlencode($val) . '&';
                }
            }

            $passPhrase = $this->config->getValue('passphrase', $order->getStoreId()) ?? '';
            if (!empty($passPhrase)) {
                $pfOutput .= 'passphrase=' . urlencode($passPhrase);
            } else {
                $pfOutput = rtrim($pfOutput, '&');
            }

            $this->logger->debug($pre . 'pfOutput for signature is : ' . $pfOutput);
            //@codingStandardsIgnoreStart
            $pfSignature = md5($pfOutput);
            //@codingStandardsIgnoreEnd
            $data['signature']  = $pfSignature;
            $data['user_agent'] = 'Magento ' . $this->getAppVersion();

            $this->logger->debug($pre . 'generated  signature : ' . $data['signature']);
        } catch (Exception $exception) {
            $this->logger->critical($pre . $exception->getTraceAsString());
            throw $exception;
        }

        $this->logger->debug($pre . 'eof');

        return $data;
    }

    /**
     * Get the App version
     *
     * @return string
     */
    private function getAppVersion(): string
    {
        $objectManager = ObjectManager::getInstance();
        $version       = $objectManager->get(ProductMetadataInterface::class)->getVersion();

        return (preg_match('([0-9])', $version)) ? $version : '2.0.0';
    }
}
