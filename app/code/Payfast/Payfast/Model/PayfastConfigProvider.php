<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Payfast\Payfast\Helper\Data as PayfastHelper;

class PayfastConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /** @var PayfastHelper */
    protected $payfastHelper;

    /**
     * @var string[]
     */
    protected $methodCodes = [
        Config::METHOD_CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PayfastHelper $paymentHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        PayfastHelper $payfastHelper,
        PaymentHelper $paymentHelper
    ) {
        $this->_logger = $logger;
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $this->localeResolver = $localeResolver;
        $this->config = $configFactory->create();
        $this->currentCustomer = $currentCustomer;
        $this->payfastHelper = $payfastHelper;
        $this->paymentHelper = $paymentHelper;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }

        $this->_logger->debug( $pre . 'eof and this  methods has : ', $this->methods );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $config = [
            'payment' => [
                'payfast' => [
                    'paymentAcceptanceMarkSrc' => $this->config->getPaymentMarkImageUrl(),
                    'paymentAcceptanceMarkHref' => $this->config->getPaymentMarkWhatIsPayfast(),
                ]
            ]
        ];

        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['payfast']['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
                $config['payment']['payfast']['billingAgreementCode'][$code] = $this->getBillingAgreementCode($code);

            }
        }
        $this->_logger->debug($pre . 'eof', $config);
        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $methodUrl = $this->methods[$code]->getCheckoutRedirectUrl();

        $this->_logger->debug($pre . 'eof');
        return $methodUrl;
    }

    /**
     * Return billing agreement code for method
     *
     * @param string $code
     * @return null|string
     */
    protected function getBillingAgreementCode($code)
    {

        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $customerId = $this->currentCustomer->getCustomerId();
        $this->config->setMethod($code);

        $this->_logger->debug($pre . 'eof');

        // always return null
        return $this->payfastHelper->shouldAskToCreateBillingAgreement($this->config, $customerId);
    }
}
