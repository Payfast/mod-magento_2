<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Payfast\Payfast\Helper\Data as PayfastHelper;
use Psr\Log\LoggerInterface;

/**
 * PayfastConfigProvider class
 */
class PayfastConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    protected ResolverInterface $localeResolver;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var CurrentCustomer
     */
    protected CurrentCustomer $currentCustomer;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $_logger;

    /**
     * @var PayfastHelper
     */
    protected PayfastHelper $payfastHelper;

    /**
     * @var string[]
     */
    protected array $methodCodes = [
        Config::METHOD_CODE
    ];

    /**
     * @var MethodInterface[]
     */
    protected array $methods = [];

    /**
     * @var PaymentHelper
     */
    protected PaymentHelper $paymentHelper;

    /**
     * @param LoggerInterface $logger
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PayfastHelper $payfastHelper
     * @param PaymentHelper $paymentHelper
     *
     * @throws LocalizedException
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        PayfastHelper $payfastHelper,
        PaymentHelper $paymentHelper
    ) {
        $this->_logger = $logger;
        $pre           = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $this->localeResolver  = $localeResolver;
        $this->config          = $configFactory->create();
        $this->currentCustomer = $currentCustomer;
        $this->payfastHelper   = $payfastHelper;
        $this->paymentHelper   = $paymentHelper;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }

        $this->_logger->debug($pre . 'eof and this  methods has : ', $this->methods);
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $config = [
            'payment' => [
                'payfast' => [
                    'paymentAcceptanceMarkSrc'  => $this->config->getPaymentMarkImageUrl(),
                    'paymentAcceptanceMarkHref' => $this->config->getPaymentMarkWhatIsPayfast(),
                ]
            ]
        ];

        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['payfast']['redirectUrl'][$code]          = $this->getMethodRedirectUrl($code);
                $config['payment']['payfast']['billingAgreementCode'][$code] = $this->getBillingAgreementCode($code);

                $config['payment']['payfast']['isActive'][$code] = $this->config->isActive();
            }
        }
        $this->_logger->debug($pre . 'eof', $config);

        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     *
     * @return string
     */
    protected function getMethodRedirectUrl(string $code): string
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $this->_logger->debug("code is : $code");

        $methodUrl = $this->config->getCheckoutRedirectUrl();

        $this->_logger->debug($pre . 'eof');

        return $methodUrl;
    }

    /**
     * Return billing agreement code for method
     *
     * @param string $code
     *
     * @return bool
     */
    protected function getBillingAgreementCode(string $code): bool
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $customerId = $this->currentCustomer->getCustomerId();

        $this->config->setMethod($code);

        $this->_logger->debug($pre . 'eof');

        // always return null
        return $this->payfastHelper->shouldAskToCreateBillingAgreement();
    }
}
