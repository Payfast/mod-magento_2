<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfast\Payfast\Block\Payment;

/**
 * PayFast common payment info block
 * Uses default templates
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var \Payfast\Payfast\Model\InfoFactory
     */
    protected $_payfastInfoFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Payfast\Payfast\Model\InfoFactory $payfastInfoFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Payfast\Payfast\Model\InfoFactory $payfastInfoFactory,
        array $data = []
    ) {
        $this->_payfastInfoFactory = $payfastInfoFactory;
        parent::__construct($context, $data);
    }

}
