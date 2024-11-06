<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Controller;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\Generic;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction;
use Payfast\Payfast\Logger\Logger as Monolog;
use Payfast\Payfast\Model\Config;
use Payfast\Payfast\Model\Payfast;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http as Response;

/**
 * Abstract Payfast Checkout Controller
 */
abstract class AbstractPayfast implements ActionInterface, HttpGetActionInterface
{
    /**
     * @var Raw $rawResult
     */
    protected Raw $rawResult;
    /**
     * Internal cache of checkout models
     *
     * @var array
     */
    protected array $_checkoutTypes = [];
    /**
     * @var ObjectManagerInterface and I could place this as property type but it will break lower php versions
     */
    protected ObjectManagerInterface $_objectManager;
    /**
     * @var Config
     */
    protected mixed $_config;
    /**
     * @var bool|Quote
     */
    protected Quote|bool $_quote = false;
    /**
     * @var RedirectInterface
     */
    protected RedirectInterface $_redirect;
    /**
     * Config mode type
     *
     * @var string
     */
    protected string $_configType = Config::class;
    /**
     * Config method type
     *
     * @var string
     */
    protected string $_configMethod = Config::METHOD_CODE;
    /**
     * Checkout mode type
     *
     * @var string
     */
    protected string $_checkoutType;
    /**
     * @var ActionFlag
     */
    protected ActionFlag $_actionFlag;
    /**
     * @var Session
     */
    protected Session $customerSession;
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected \Magento\Checkout\Model\Session $checkoutSession;
    /**
     * @var OrderFactory
     */
    protected OrderFactory $orderFactory;
    /**
     * @var Generic
     */
    protected Generic $_payfastSession;
    /**
     * @var Data
     */
    protected Data $urlHelper;
    /**
     * @var Order $orderResourceModel
     */
    protected Order $orderResourceModel;
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $_logger;
    /**
     * @var \Magento\Sales\Model\Order $_order
     */
    protected \Magento\Sales\Model\Order $_order;
    /**
     * @var PageFactory
     */
    protected PageFactory $_pageFactory;
    /**
     * @var Transaction $salesTransactionResourceModel
     */
    protected Transaction $salesTransactionResourceModel;
    /**
     * @var TransactionFactory
     */
    protected TransactionFactory $transactionFactory;
    /**
     * @var Payfast $paymentMethod
     */
    protected Payfast $paymentMethod;
    /**
     * @var PageFactory
     */
    protected PageFactory $pageFactory;
    /**
     * @var OrderSender
     */
    protected OrderSender $orderSender;
    /**
     * @var InvoiceSender
     */
    protected InvoiceSender $invoiceSender;
    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $_response;
    /**
     * @var RequestInterface
     */
    protected RequestInterface $_request;
    /**
     * @var MessageManagerInterface
     */
    protected MessageManagerInterface $messageManager;
    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;
    /**
     * @var Http
     */
    protected Http $request;
    /**
     * @var Monolog
     */
    protected Monolog $payfastLogger;
    protected UrlInterface $_url;
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Generic $payfastSession
     * @param Data $urlHelper
     * @param Order $orderResourceModel
     * @param LoggerInterface $logger
     * @param TransactionFactory $transactionFactory
     * @param Payfast $paymentMethod
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param Transaction $salesTransactionResourceModel
     * @param Raw $rawResult
     * @param ResultFactory $resultFactory
     * @param Http $request
     * @param Monolog $payfastLogger
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderFactory $orderFactory,
        Generic $payfastSession,
        Data $urlHelper,
        Order $orderResourceModel,
        LoggerInterface $logger,
        TransactionFactory $transactionFactory,
        Payfast $paymentMethod,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        Transaction $salesTransactionResourceModel,
        Raw $rawResult,
        ResultFactory $resultFactory,
        Http $request,
        Monolog $payfastLogger,
        OrderRepositoryInterface $orderRepository
    ) {
        $pre = __METHOD__ . ' : ';

        $this->_logger = $logger;

        $this->_logger->debug($pre . 'bof');
        $this->_request                      = $context->getRequest();
        $this->_response                     = $context->getResponse();
        $this->customerSession               = $customerSession;
        $this->checkoutSession               = $checkoutSession;
        $this->orderFactory                  = $orderFactory;
        $this->_payfastSession               = $payfastSession;
        $this->urlHelper                     = $urlHelper;
        $this->orderResourceModel            = $orderResourceModel;
        $this->pageFactory                   = $pageFactory;
        $this->transactionFactory            = $transactionFactory;
        $this->paymentMethod                 = $paymentMethod;
        $this->orderSender                   = $orderSender;
        $this->invoiceSender                 = $invoiceSender;
        $this->salesTransactionResourceModel = $salesTransactionResourceModel;
        $this->rawResult                     = $rawResult;
        $parameters                          = ['params' => [$this->_configMethod]];
        $this->_objectManager                = $context->getObjectManager();
        $this->_url                          = $context->getUrl();
        $this->_actionFlag                   = $context->getActionFlag();
        $this->_redirect                     = $context->getRedirect();
        $this->_response                     = $context->getResponse();
        $this->_actionFlag                   = $context->getActionFlag();
        $this->messageManager                = $context->getMessageManager();
        $this->resultFactory                 = $resultFactory;
        $this->request                       = $request;
        $this->payfastLogger                 = $payfastLogger;
        $this->orderRepository               = $orderRepository;

        $this->_config = $this->_objectManager->create($this->_configType, $parameters);

        if (!defined('PF_DEBUG')) {
            define('PF_DEBUG', $this->getConfigData('debug'));
        }

        $this->_logger->debug($pre . 'eof');
    }

    /**
     * Custom getter for payment configuration
     *
     * @param string $field i.e merchant_id, server
     *
     * @return string
     */
    public function getConfigData(string $field): string
    {
        return $this->_config->getValue($field);
    }

    /**
     * Returns a list of action flags [flag_key] => boolean
     *
     * @return array
     */
    public function getActionFlagList(): array
    {
        return [];
    }

    /**
     * Returns login url parameter for redirect
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->orderResourceModel->getLoginUrl();
    }

    /**
     * Returns action name which requires redirect
     *
     * @return string
     */
    public function getRedirectActionName(): string
    {
        return 'index';
    }

    /**
     * Redirect to login page
     *
     * @return void
     */
    public function redirectLogin(): void
    {
        $this->_actionFlag->set('', 'no-dispatch', true);
        $this->customerSession->setBeforeAuthUrl($this->_redirect->getRefererUrl());

        // Cast the response to Http to ensure setRedirect() is valid
        /** @var Response $response */
        $response = $this->getResponse();
        $response->setRedirect(
            $this->urlHelper->addRequestParam($this->orderResourceModel->getLoginUrl(), ['context' => 'checkout'])
        );
    }

    /**
     * Handle response
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->_response;
    }

    /**
     * Instantiate
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _initCheckout(): void
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $this->checkoutSession->loadCustomerQuote();

        $this->_order = $this->checkoutSession->getLastRealOrder();

        if (!$this->_order->getId()) {
            $phrase = __('We could not find "Order" for processing');
            $this->_logger->critical($pre . $phrase);

            /** @var Response $response */
            $response = $this->getResponse();
            $response->setStatusHeader(404, '1.1', 'Not found');
            throw new LocalizedException($phrase);
        }

        if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
            $this->_logger->debug($pre . 'updating order state and status');

            $this->_order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $this->_order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);

            $this->orderResourceModel->save($this->_order);
        }

        if ($this->_order->getQuoteId()) {
            $this->checkoutSession->setPayfastQuoteId($this->checkoutSession->getQuoteId());
            $this->checkoutSession->setPayfastSuccessQuoteId($this->checkoutSession->getLastSuccessQuoteId());
            $this->checkoutSession->setPayfastRealOrderId($this->checkoutSession->getLastRealOrderId());
            $quote = $this->checkoutSession->getQuote()->setIsActive(false);
            /** @var QuoteRepository $quoteRepository */
            $quoteRepository = $this->_objectManager->get(QuoteRepository::class);
            $quoteRepository->save($quote);
        }

        $this->_logger->debug($pre . 'eof');
    }

    /**
     * Payfast session instance getter
     *
     * @return Generic
     */
    protected function _getSession(): Generic
    {
        return $this->_payfastSession;
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession(): \Magento\Checkout\Model\Session
    {
        return $this->checkoutSession;
    }

    /**
     * Return checkout quote object
     *
     * @return bool|Quote
     */
    protected function _getQuote(): bool|Quote
    {
        if (!$this->_quote) {
            try {
                $this->_quote = $this->_getCheckoutSession()->getQuote();
            } catch (NoSuchEntityException|LocalizedException $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        return $this->_quote;
    }

    /**
     * Used to be part of inherited abstractAction now we need to code it in.
     *
     * @param string $path
     * @param array $arguments
     *
     * @return ResponseInterface
     */
    protected function _redirect(string $path, array $arguments = []): ResponseInterface
    {
        $this->_redirect->redirect($this->getResponse(), $path, $arguments);

        return $this->getResponse();
    }
}
