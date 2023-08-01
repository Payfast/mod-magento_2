<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Controller\Redirect;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Payfast\Payfast\Controller\AbstractPayfast;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Cancel extends AbstractPayfast
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * execute
     * this method illustrate magento2 super power.
     */

    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');
        $page_object = $this->pageFactory->create();

        try {
            // Get the user session
            $this->_order = $this->checkoutSession->getLastRealOrder();

            $this->messageManager->addNoticeMessage('You have successfully canceled the order using PayFast Checkout.');

            if ($this->_order->getId() && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED) {
                $this->_order->registerCancellation('Cancelled by user from ' . $this->_configMethod)->save();
            }

            $this->checkoutSession->restoreQuote();

            $this->_redirect('checkout/cart');
        } catch (LocalizedException $e) {
            $this->_logger->error($pre . $e->getMessage());

            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_redirect('checkout/cart');
        } catch (\Exception $e) {
            $this->_logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('We can\'t start PayFast Checkout.'));
            $this->_redirect('checkout/cart');
        }

        return $page_object;
    }
}
