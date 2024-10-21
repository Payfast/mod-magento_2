<?php

namespace Payfast\Payfast\Controller\Notify;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website
 * in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or
 * part thereof in any way.
 */

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Payfast\Payfast\Controller\AbstractPayfast;
use Payfast\Payfast\Model\Config as PayfastConfig;
use Payfast\Payfast\Model\Info;
use Magento\Framework\Controller\ResultFactory;
use Payfast\PayfastCommon;

class Index extends AbstractPayfast implements CsrfAwareActionInterface, HttpPostActionInterface
{

    /**
     * IndexAction
     *
     * Instantiate ITN model and pass ITN request to it
     */
    public function execute(): ResultInterface
    {
        define('PF_SOFTWARE_NAME', 'Magento');
        define('PF_SOFTWARE_VER', '2.4.6');
        define('PF_MODULE_NAME', 'Payfast-Magento');
        define('PF_MODULE_VER', '2.5.1');

        $this->_logger->debug('Notify: ' . json_encode($this->request->getPostValue()));
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        // Variable Initialization
        $pfError       = false;
        $pfErrMsg      = '';
        $pfData        = [];
        $serverMode    = $this->getConfigData('server');
        $pfParamString = '';

        $pfHost = $this->paymentMethod->getPayfastHost($serverMode);

        $this->payfastLogger->info(' Payfast ITN call received');

        $this->payfastLogger->info('Server = ' . $pfHost);

        //// Notify Payfast that information has been received
        if (!$pfError) {
            $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $resultRaw->setHttpResponseCode(200);
            $resultRaw->setContents('OK');
        }

        $passPhrase = $this->_config->getValue('passphrase');
        if (empty($passPhrase)) {
            $passPhrase = null;
        }

        //// Get data sent by Payfast
        if (!$pfError) {
            // Posted variables from ITN
            $pfData = PayfastCommon\PayfastCommon::pfGetData();

            if (empty($pfData)) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon\PayfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if (!$pfError) {
            $this->payfastLogger->info('Verify security signature');

            // If signature different, log for debugging
            if (!PayfastCommon\PayfastCommon::pfValidSignature(
                $pfData,
                $pfParamString,
                $passPhrase
            )) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon\PayfastCommon::PF_ERR_INVALID_SIGNATURE;
            }
        }

        //// Get internal order and verify it hasn't already been processed
        if (!$pfError) {
            $this->payfastLogger->info("Check order hasn't been processed");

            // Load order
            $orderId = $pfData[Info::M_PAYMENT_ID];

            $this->_order = $this->orderFactory->create()->loadByIncrementId($orderId);

            $this->payfastLogger->info('order status is : ' . $this->_order->getStatus());

            // Check order is in "pending payment" state
            if ($this->_order->getState() !== Order::STATE_PENDING_PAYMENT) {
                $pfErrMsg = PayfastCommon\PayfastCommon::PF_ERR_ORDER_PROCESSED;
            }
        }

        //// Verify data received
        if (!$pfError) {
            $this->payfastLogger->info('Verify data received');

            if (!PayfastCommon\PayfastCommon::pfValidData($pfHost, $pfParamString)) {
                $pfError  = true;
                $pfErrMsg = PayfastCommon\PayfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        //// Check status and update order
        if (!$pfError) {
            $this->payfastLogger->info('Check status and update order');

            // Successful
            if ($pfData[Info::PAYMENT_STATUS] === "COMPLETE") {
                $this->setPaymentAdditionalInformation($pfData);
                // Save invoice
                $this->saveInvoice();
            }
        }

        // If an error occurred
        if ($pfError) {
            $this->payfastLogger->info('Error occurred: ' . $pfErrMsg);
            $this->_logger->critical($pre . "Error occured : " . $pfErrMsg);

            return $this->rawResult
                ->setHttpResponseCode(400)
                ->setHeader('Content-Type', 'text/html')
                ->setContents($pfErrMsg);
        }

        return $this->rawResult
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'text/html')
            ->setContents('HTTP/1.0 200');
    }

    /**
     * Create exception in case CSRF validation failed.
     *
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     *
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * SaveInvoice
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function saveInvoice()
    {
        $this->payfastLogger->info(__METHOD__ . ' : bof');

        try {
            $invoice = $this->_order->prepareInvoice();

            /** @var \Magento\Sales\Model\Order $order */
            $order  = $invoice->getOrder();
            $status = $this->getConfigData('successful_order_status');
            $state  = $this->getConfigData('successful_order_state');
            if (!$status || $status === '') {
                $status = Order::STATE_PROCESSING;
            }
            if (!$state || $state === '') {
                $state = Order::STATE_PROCESSING;
            }
            $order->setIsInProcess(true);
            $order->setState($state);
            $order->setStatus($status);
            $order->save();
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($order)->save();

            $this->orderResourceModel->save($this->_order);

            if ($this->_config->getValue(PayfastConfig::KEY_SEND_CONFIRMATION_EMAIL)) {
                $this->payfastLogger->info(
                    'before sending order email, canSendNewEmailFlag is ' . boolval(
                        $this->_order->getCanSendNewEmailFlag()
                    )
                );
                $this->orderSender->send($this->_order);

                $this->payfastLogger->info('after sending order email');
            }

            if ($this->_config->getValue(PayfastConfig::KEY_SEND_INVOICE_EMAIL)) {
                $this->payfastLogger->info(
                    'before sending invoice email is ' .
                    boolval($this->_order->getCanSendNewEmailFlag())
                );
                foreach ($this->_order->getInvoiceCollection() as $invoice) {
                    $this->payfastLogger->info('sending invoice #' . $invoice->getId());
                    if ($invoice->getId()) {
                        $this->invoiceSender->send($invoice);
                    }
                }

                $this->payfastLogger->info('after sending ' . boolval($invoice->getIncrementId()));
            }
        } catch (LocalizedException $e) {
            $this->payfastLogger->info(__METHOD__ . ' localizedException caught and will be re thrown. ');
            $this->payfastLogger->info(__METHOD__ . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->payfastLogger->info(__METHOD__ . 'Exception caught and will be re thrown.');
            $this->payfastLogger->info(__METHOD__ . $e->getMessage());
            throw $e;
        }

        $this->payfastLogger->info(__METHOD__ . ' : eof');
    }

    /**
     * Set additional payment information
     *
     * @param array $pfData
     *
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function setPaymentAdditionalInformation($pfData)
    {
        $this->payfastLogger->info(__METHOD__ . ' : bof');
        $this->payfastLogger->info('Order complete');

        try {
            // Update order additional payment information
            /**
             * @var Payment $payment
             */
            $payment = $this->_order->getPayment();
            $payment->setAdditionalInformation(Info::PAYMENT_STATUS, $pfData[Info::PAYMENT_STATUS]);
            $payment->setAdditionalInformation(Info::M_PAYMENT_ID, $pfData[Info::M_PAYMENT_ID]);
            $payment->setAdditionalInformation(Info::PF_PAYMENT_ID, $pfData[Info::PF_PAYMENT_ID]);
            $payment->setAdditionalInformation(Info::EMAIL_ADDRESS, $pfData[Info::EMAIL_ADDRESS]);
            $payment->setAdditionalInformation("amount_fee", $pfData['amount_fee']);
            $payment->registerCaptureNotification($pfData['amount_gross'], true);

            $this->_order->setPayment($payment);
        } catch (LocalizedException $e) {
            $this->payfastLogger->info(__METHOD__ . ' localizedException caught and will be re thrown. ');
            $this->payfastLogger->info(__METHOD__ . $e->getMessage());
            throw $e;
        }

        $this->payfastLogger->info(__METHOD__ . ' : eof');
    }
}
