<?php

namespace Payfast\Payfast\Gateway\Validator;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use InvalidArgumentException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Payfast\Payfast\Gateway\Http\Client\ClientMock;
use Psr\Log\LoggerInterface;

/**
 * ResponseCodeValidator class
 */
class ResponseCodeValidator extends AbstractValidator
{
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param LoggerInterface $logger
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
    public function validate(array $validationSubject): ResultInterface
    {
        $pre = __METHOD__ . ' : ';
        $this->logger->debug($pre . 'bof');

        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        $this->logger->debug($pre . 'response has : ' . json_encode($response));

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
     * Check if the transaction was successful
     *
     * @param array $response
     *
     * @return bool
     */
    private function isSuccessfulTransaction(array $response): bool
    {
        return isset($response[self::RESULT_CODE])
               && $response[self::RESULT_CODE] !== ClientMock::FAILURE;
    }
}
