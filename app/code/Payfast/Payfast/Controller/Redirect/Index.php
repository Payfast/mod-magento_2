<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Controller\Redirect;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Payfast\Payfast\Controller\AbstractPayfast;
use Payfast\PayfastCommon;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends AbstractPayfast
{
    /**
     * Execute: This method illustrate magento2 super power.
     */

    public function execute(): Page|ResultInterface|ResponseInterface
    {
        $pre = __METHOD__ . ' : ';
        $this->payfastLogger->info($pre . 'bof');

        $page_object = $this->pageFactory->create();

        try {
            $this->_initCheckout();
        } catch (LocalizedException $e) {
            $this->_logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_redirect('checkout/cart');
        } catch (Exception $e) {
            $this->_logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('We can\'t start Payfast Checkout.'));
            $this->_redirect('checkout/cart');
        }

        return $page_object;
    }
}
