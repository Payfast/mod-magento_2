<?php

namespace Payfast\Payfast\Controller\Notify;

/**
 * Copyright (c) 2023 Payfast (Pty) Ltd
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

class Index extends AbstractPayfast implements CsrfAwareActionInterface, HttpPostActionInterface
{

    /**
     * indexAction
     *
     * Instantiate ITN model and pass ITN request to it
     */
    public function execute(): ResultInterface
    {
        $this->_logger->debug('Notify: ' . json_encode($_POST));
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        // Variable Initialization
        $pfError       = false;
        $pfErrMsg      = '';
        $pfData        = [];
        $serverMode    = $this->getConfigData('server');
        $pfParamString = '';

        $pfHost = $this->paymentMethod->getPayfastHost($serverMode);

        pflog(' Payfast ITN call received');

        pflog('Server = ' . $pfHost);

        //// Notify Payfast that information has been received
        if (!$pfError) {
            header('HTTP/1.0 200 OK');
            flush();
        }

        $passPhrase = $this->_config->getValue('passphrase');
        if (empty($passPhrase)) {
            $passPhrase = null;
        }

        //// Get data sent by Payfast
        if (!$pfError) {
            // Posted variables from ITN
            $pfData = pfGetData();

            if (empty($pfData)) {
                $pfError  = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if (!$pfError) {
            pflog('Verify security signature');

            // If signature different, log for debugging
            if (!pfValidSignature(
                $pfData,
                $pfParamString,
                $passPhrase
            )) {
                $pfError  = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }

        //// Verify source IP (If not in debug mode)
        if (!$pfError && !defined('PF_DEBUG')) {
            pflog('Verify source IP');

            if (!pfValidIP($_SERVER['REMOTE_ADDR'], $serverMode)) {
                $pfError  = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }

        //// Get internal order and verify it hasn't already been processed
        if (!$pfError) {
            pflog("Check order hasn't been processed");

            // Load order
            $orderId = $pfData[Info::M_PAYMENT_ID];

            $this->_order = $this->orderFactory->create()->loadByIncrementId($orderId);

            pflog('order status is : ' . $this->_order->getStatus());

            // Check order is in "pending payment" state
            if ($this->_order->getState() !== Order::STATE_PENDING_PAYMENT) {
//                $pfError = true;
                $pfErrMsg = PF_ERR_ORDER_PROCESSED;
            }
        }

        //// Verify data received
        if (!$pfError) {
            pflog('Verify data received');

            if (!pfValidData($pfHost, $pfParamString)) {
                $pfError  = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Check status and update order
        if (!$pfError) {
            pflog('Check status and update order');

            // Successful
            if ($pfData[Info::PAYMENT_STATUS] === "COMPLETE") {
                $this->setPaymentAdditionalInformation($pfData);
                // Save invoice
                $this->saveInvoice();
            }
        }

        // If an error occurred
        if ($pfError) {
            pflog('Error occurred: ' . $pfErrMsg);
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
     * saveInvoice
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function saveInvoice()
    {
        pflog(__METHOD__ . ' : bof');

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
                pflog(
                    'before sending order email, canSendNewEmailFlag is ' . boolval(
                        $this->_order->getCanSendNewEmailFlag()
                    )
                );
                $this->orderSender->send($this->_order);

                pflog('after sending order email');
            }

            if ($this->_config->getValue(PayfastConfig::KEY_SEND_INVOICE_EMAIL)) {
                pflog('before sending invoice email is ' . boolval($this->_order->getCanSendNewEmailFlag()));
                foreach ($this->_order->getInvoiceCollection() as $invoice) {
                    pflog('sending invoice #' . $invoice->getId());
                    if ($invoice->getId()) {
                        $this->invoiceSender->send($invoice);
                    }
                }

                pflog('after sending ' . boolval($invoice->getIncrementId()));
            }
        } catch (LocalizedException $e) {
            pflog(__METHOD__ . ' localizedException caught and will be re thrown. ');
            pflog(__METHOD__ . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            pflog(__METHOD__ . 'Exception caught and will be re thrown.');
            pflog(__METHOD__ . $e->getMessage());
            throw $e;
        }

        pflog(__METHOD__ . ' : eof');
    }

    /**
     * @param  $pfData
     *
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function setPaymentAdditionalInformation($pfData)
    {
        pflog(__METHOD__ . ' : bof');
        pflog('Order complete');

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
            pflog(__METHOD__ . ' localizedException caught and will be re thrown. ');
            pflog(__METHOD__ . $e->getMessage());
            throw $e;
        }

        pflog(__METHOD__ . ' : eof');
    }
}
