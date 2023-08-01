<?php
namespace Payfast\Payfast\Gateway\Validator;

/**
 * Copyright (c) 2023 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Payfast\Payfast\Gateway\Http\Client\ClientMock;
use Psr\Log\LoggerInterface;

class ResponseCodeValidator extends AbstractValidator
{
    const RESULT_CODE = 'RESULT_CODE';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(ResultInterfaceFactory $resultFactory, LoggerInterface $logger)
    {
        parent::__construct($resultFactory);

        $this->logger = $logger;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $pre = __METHOD__ . ' : ';
        $this->logger->debug($pre . 'bof');

        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        $this->logger->debug($pre . 'response has : ' . print_r($response, true));

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true,
                [__('Gateway will now call Payfast via redirect method.')]
            );
        } else {
            return $this->createResult(
                false,
                [__('Gateway is not called just yet, we will now call Payfast via redirect.')]
            );
        }
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function isSuccessfulTransaction(array $response)
    {
        return isset($response[self::RESULT_CODE])
               && $response[self::RESULT_CODE] !== ClientMock::FAILURE;
    }
}
