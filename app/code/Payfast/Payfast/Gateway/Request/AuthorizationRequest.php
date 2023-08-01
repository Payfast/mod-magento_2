<?php namespace Payfast\Payfast\Gateway\Request;

/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

use Magento\Framework\App\ObjectManager;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payfast\Payfast\Model\Config;
use Psr\Log\LoggerInterface;

class AuthorizationRequest implements BuilderInterface
{

    /**
     * @var Config
     */
    private $payfastConfig;

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
     * @param Config          $payfastConfig
     */
    public function __construct(ConfigInterface $config, LoggerInterface $logger, Config $payfastConfig)
    {
        $this->config = $config;

        $this->logger = $logger;

        $this->payfastConfig = $payfastConfig;
    }

    /**
     * if this was a cc payment then we would append cc fields in and dispatch it.
     * Builds ENV request
     *
     * @param  array $buildSubject
     * @return array
     * @throws \Exception
     */
    public function build(array $buildSubject)
    {
        $pre  = __METHOD__ . ' : ';

        $this->logger->debug($pre . 'bof');

        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        try {
            $payment = $buildSubject['payment'];

            $order = $payment->getOrder();

            $address = $order->getBillingAddress();

            $merchantId = $this->config->getValue('merchant_id', $order->getStoreId());
            $merchantKey = $this->config->getValue('merchant_key', $order->getStoreId());
            $data = [
                // Merchant details
                'merchant_id' => $merchantId,
                'merchant_key' => $merchantKey,
                'return_url' => $this->payfastConfig->getPaidSuccessUrl(),
                'cancel_url' => $this->payfastConfig->getPaidCancelUrl(),
                'notify_url' => $this->payfastConfig->getPaidNotifyUrl(),

                // Buyer details
                'name_first' => $address->getFirstname(),
                'name_last' => $address->getLastname(),
                'email_address' => $address->getEmail(),

                // Item details
                'm_payment_id' => $order->getOrderIncrementId(),
                'amount' => $order->getGrandTotalAmount(),

                // 'item_name' => $this->_storeManager->getStore()->getName() .', Order #'. $order->getOrderIncrementId(),
                'item_name' => 'Order #' . $order->getOrderIncrementId(),
                'currency' => $order->getCurrencyCode(),

            ];
            $pfOutput = '';
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

            $pfSignature = md5($pfOutput);

            $data['signature'] = $pfSignature;
            $data['user_agent'] = 'Magento ' . $this->getAppVersion();

            $this->logger->debug($pre . 'generated  signature : ' . $data['signature']);
        } catch (\Exception $exception) {
            $this->logger->critical($pre . $exception->getTraceAsString());
            throw $exception;
        }

        $this->logger->debug($pre . 'eof');

        return $data;
    }

    /**
     * getAppVersion
     *
     * @return string
     */
    private function getAppVersion()
    {
        $objectManager = ObjectManager::getInstance();
        $version = $objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();

        return  (preg_match('([0-9])', $version)) ? $version : '2.0.0';
    }
}
