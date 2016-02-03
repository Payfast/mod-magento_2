<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
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
