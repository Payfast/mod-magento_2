<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

// @codingStandardsIgnoreFile

namespace Payfast\Payfast\Model;

/**
 * PayFast payment information model
 *
 * Aware of all PayFast payment methods
 * Collects and provides access to PayFast-specific payment data
 * Provides business logic information about payment flow
 */
class Info
{
    /**
     * Cross-models public exchange keys
     *
     * @var string
     */
    const PAYMENT_STATUS = 'payment_status';
    const M_PAYMENT_ID = 'm_payment_id';
    const PF_PAYMENT_ID = 'pf_payment_id';
    const EMAIL_ADDRESS = 'email_address';

    /**
     * Apply a filter upon value getting
     *
     * @param string $value
     * @param string $key
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _getValue( $value, $key )
    {
        $label = '';
        $outputValue = implode( ', ', (array)$value );

        return sprintf( '#%s%s', $outputValue, $outputValue == $label ? '' : ': ' . $label );
    }

}
