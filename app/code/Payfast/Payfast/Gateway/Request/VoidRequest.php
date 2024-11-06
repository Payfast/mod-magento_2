<?php

namespace Payfast\Payfast\Gateway\Request;

/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

class VoidRequest extends AbstractRequest
{
    /**
     * Builds ENV request
     *
     * @param array $options
     * @return array
     */
    public function build(array $options): array
    {
        return $this->buildRequest($options, 'V', 'merchant_key');
    }
}
