<?php

namespace Payfast\Payfast\Gateway\Http\Client;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class ClientMock implements ClientInterface
{
    public const SUCCESS = 1;
    public const FAILURE = 0;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Place request
     *
     * @param TransferInterface $transferObject
     *
     * @return Object
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        // TODO: Implement placeRequest() method.
        $response = $this->generateResponseForCode(
            $this->getResultCode(
                $transferObject
            )
        );

        $this->logger->debug(
            [
                'method'   => __METHOD__,
                'request'  => $transferObject->getBody(),
                'response' => $response
            ]
        );

        return $response;
    }

    /**
     * Generates response
     *
     * @param string $resultCode
     *
     * @return array
     */
    protected function generateResponseForCode($resultCode)
    {
        return array_merge(
            [
                'RESULT_CODE' => $resultCode,
                'TXN_ID'      => $this->generateTxnId()
            ],
            $this->getFieldsBasedOnResponseType($resultCode)
        );
    }

    /**
     * Generate the txn id
     *
     * @return string
     */
    protected function generateTxnId()
    {
        //@codingStandardsIgnoreStart
        return md5(mt_rand(0, 1000));
        //@codingStandardsIgnoreEnd
    }

    /**
     * Returns result code will always return false for now since Payfast needs to do a redirect.
     *
     * @param TransferInterface $transfer
     *
     * @return int
     */
    private function getResultCode(TransferInterface $transfer)
    {
        $headers = $transfer->getHeaders();

        if (isset($headers['force_result'])) {
            return (int)$headers['force_result'];
        }

        return self::SUCCESS;
    }

    /**
     * Returns response fields for result code
     *
     * @param int $resultCode
     *
     * @return array
     */
    private function getFieldsBasedOnResponseType($resultCode)
    {
        switch ($resultCode) {
            case self::FAILURE:
                return [
                    'FRAUD_MSG_LIST' => [
                        'Stolen card',
                        'Customer location differs'
                    ]
                ];
        }

        return [];
    }
}
