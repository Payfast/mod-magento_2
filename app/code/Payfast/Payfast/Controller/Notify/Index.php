<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

namespace Payfast\Payfast\Controller\Notify;

class Index extends \Payfast\Payfast\Controller\AbstractPayfast
{
    private $storeId;


    /**
     * indexAction
     *
     * Instantiate ITN model and pass ITN request to it
     */
    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

        // Variable Initialization
        $pfError = false;
        $pfErrMsg = '';
        $pfData = array();
        $serverMode = $this->getConfigData('server');
        $pfParamString = '';

        $pfHost = $this->_paymentMethod->getPayfastHost( $serverMode );

        pflog( ' PayFast ITN call received' );

        pflog( 'Server = '. $pfHost );
        
        //// Notify PayFast that information has been received
        if( !$pfError )
        {
            header( 'HTTP/1.0 200 OK' );
            flush();
        }
        
        //// Get data sent by PayFast
        if( !$pfError )
        {
            // Posted variables from ITN
            $pfData = pfGetData();

            if ( empty( $pfData ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }
        
        //// Verify security signature
        if( !$pfError )
        {
            pflog( 'Verify security signature' );
        
            // If signature different, log for debugging
            if ( !pfValidSignature( $pfData, $pfParamString, $this->getConfigData( 'passphrase' ), $this->getConfigData( 'server' ) ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }
        
        //// Verify source IP (If not in debug mode)
        if( !$pfError && !defined( 'PF_DEBUG' ) )
        {
            pflog( 'Verify source IP' );
        
            if( !pfValidIP( $_SERVER['REMOTE_ADDR'] , $serverMode ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }
        
        //// Get internal order and verify it hasn't already been processed
        if( !$pfError )
        {
            pflog( "Check order hasn't been processed" );
            
            // Load order
    		$orderId = $pfData['m_payment_id'];

            $this->_order = $this->_orderFactory->create()->loadByIncrementId($orderId);

    		$this->storeId = $this->_order->getStoreId();


            pflog( 'order status is : '. $this->_order->getStatus());

            // Check order is in "pending payment" state
            if( $this->_order->getStatus() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_ORDER_PROCESSED;
            }
        }
        
        //// Verify data received
        if( !$pfError )
        {
            pflog( 'Verify data received' );
        
            $pfValid = pfValidData( $pfHost, $pfParamString );
        
            if( !$pfValid )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Check status and update order
        if( !$pfError )
        {
            pflog( 'Check status and update order' );
            
            // Successful
            if( $pfData['payment_status'] == "COMPLETE" )
            {
                pflog( 'Order complete' );
                
                // Update order additional payment information
                $payment = $this->_order->getPayment();
        		$payment->setAdditionalInformation( "payment_status", $pfData['payment_status'] );
        		$payment->setAdditionalInformation( "m_payment_id", $pfData['m_payment_id'] );
                $payment->setAdditionalInformation( "pf_payment_id", $pfData['pf_payment_id'] );
                $payment->setAdditionalInformation( "email_address", $pfData['email_address'] );
        		$payment->setAdditionalInformation( "amount_fee", $pfData['amount_fee'] );
                $payment->registerCaptureNotification( $pfData['amount_gross'], true);
                $payment->save();

                // Save invoice
                $this->saveInvoice();

            }
        }
        
        // If an error occurred
        if( $pfError )
        {
            pflog( 'Error occurred: '. $pfErrMsg );
            $this->_logger->critical($pre. "Error occured : ". $pfErrMsg );
        }
    }

    /**
	 * saveInvoice
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	protected function saveInvoice()
    {
        pflog( 'Saving invoice' );
        
		// Check for mail msg
		$invoice = $this->_order->prepareInvoice();

		$invoice->register()->capture();

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->_transactionFactory->create();
        $transaction->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        $this->_order->addStatusHistoryComment( __( 'Notified customer about invoice #%1.', $invoice->getIncrementId() ) );
        $this->_order->setIsCustomerNotified(true);
        $this->_order->save();
    }
}