<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
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
     * Execute: This method illustrate magento2 super power.
     */
    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');
        $page_object = $this->pageFactory->create();

        try {
            // Get the user session
            $this->_order = $this->checkoutSession->getLastRealOrder();

            $this->messageManager->addNoticeMessage('You have successfully canceled the order using Payfast Checkout.');

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
            $this->messageManager->addExceptionMessage($e, __('We can\'t start Payfast Checkout.'));
            $this->_redirect('checkout/cart');
        }

        return $page_object;
    }
}
