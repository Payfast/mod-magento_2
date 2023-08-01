<?php
/**
 * Copyright (c) 2023 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
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
    protected $_payfastInfoFactory;

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
