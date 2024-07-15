<?php

namespace Payfast\Payfast\Logger;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Monolog\Handler\HandlerInterface;
use Payfast\Payfast\Model\Config;
use Payfast\Payfast\Model\ConfigFactory;

class Logger extends \Monolog\Logger
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string Path to the configuration setting
     */
    protected $configPath = 'debug';
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * Logger constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigFactory $configFactory
     * @param string $name
     * @param HandlerInterface[] $handlers
     * @param callable[] $processors
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigFactory $configFactory,
        string $name,
        array $handlers = array(),
        array $processors = array()
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors);

        $parameters = ['params' => [Config::METHOD_CODE]];

        $this->config = $configFactory->create($parameters);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string|\Throwable $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = array()): void
    {
        if ($this->isLoggingEnabled() === '1') {
            parent::info($message, $context);
        }
    }

    /**
     * Check if logging is enabled through configuration setting
     *
     * @return string|null
     */
    protected function isLoggingEnabled(): ?string
    {
        return $this->config->getValue('debug');
    }
}

