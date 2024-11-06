<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Block\Payment;

use Magento\Framework\View\Element\Template\Context;
use Payfast\Payfast\Model\InfoFactory;

/**
 * Payfast common payment info block
 * Uses default templates
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var InfoFactory
     */
    protected InfoFactory $_payfastInfoFactory;

    /**
     * @param Context $context
     * @param InfoFactory $payfastInfoFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        InfoFactory $payfastInfoFactory,
        array $data = []
    ) {
        $this->_payfastInfoFactory = $payfastInfoFactory;
        parent::__construct($context, $data);
    }
}
