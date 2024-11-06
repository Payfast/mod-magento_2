<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

namespace Payfast\Payfast\Model;

/**
 * Payfast payment information model
 *
 * Aware of all Payfast payment methods
 * Collects and provides access to Payfast-specific payment data
 * Provides business logic information about payment flow
 */
class Info
{
    /**
     * Cross-models public exchange keys
     *
     * @var string
     */
    public const PAYMENT_STATUS = 'payment_status';
    public const M_PAYMENT_ID  = 'm_payment_id';
    public const PF_PAYMENT_ID = 'pf_payment_id';
    public const EMAIL_ADDRESS = 'email_address';
    public const MERCHANT_ID  = 'merchant_id';
    public const MERCHANT_KEY = 'merchant_key';

    /**
     * Apply a filter upon value getting
     *
     * @param string $value
     * @param string $key
     *
     * @return string
     */
    protected function _getValue(string $value, string $key): string
    {
        $label       = '';
        $outputValue = implode(', ', (array)$value);

        return sprintf('#%s%s', $outputValue, $outputValue == $label ? '' : ': ' . $label);
    }
}
