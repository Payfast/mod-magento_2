<?php

namespace Payfast\Payfast\Observer;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Payfast\Payfast\Model\Config;
use Psr\Log\LoggerInterface as LoggerInterfaceAlias;

/**
 * SalesOrderBeforeSaveObserver class
 */
class SalesOrderBeforeSaveObserver implements ObserverInterface
{
    /**
     * @var LoggerInterfaceAlias
     */
    private LoggerInterfaceAlias $_logger;

    /**
     * SalesOrderBeforeSaveObserver constructor.
     *
     * @param LoggerInterfaceAlias $logger
     */
    public function __construct(LoggerInterfaceAlias $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Born out of necessity to force order status to not be in processing. Provided that user has not paid.
     *
     * @param Observer $observer
     *
     * @return $this
     * @throws LocalizedException
     */
    public function execute(Observer $observer): static
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        /**
         * @var Order $order
         */
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethodInstance()->getCode() == Config::METHOD_CODE
            && $order->getState() == Order::STATE_PROCESSING
            && empty($order->getPayment()->getAdditionalInformation('pf_payment_id'))
        ) {
            $this->_logger->debug($pre . 'setting order status and preventing sending of emails.');

            $this->_logger->debug('order status : ' . $observer->getOrder()->getStatus());
            $this->_logger->debug('order state : ' . $observer->getOrder()->getState());

            $observer->getOrder()->setStatus(Order::STATE_PENDING_PAYMENT);
            $observer->getOrder()->setState(Order::STATE_PENDING_PAYMENT);
            $observer->getOrder()->setCanSendNewEmailFlag(false);

            return $this;
        }

        $this->_logger->debug('order status : ' . $order->getStatus());
        $this->_logger->debug('order state : ' . $order->getState());

        $this->_logger->debug(
            $pre . "pf_payment_id is : ( {$order->getPayment()->getAdditionalInformation('pf_payment_id')} )"
        );

        $this->_logger->debug($pre . 'eof');

        return $this;
    }
}
