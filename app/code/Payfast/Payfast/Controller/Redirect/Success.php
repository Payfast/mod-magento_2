<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Controller\Redirect;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Payfast\Payfast\Controller\AbstractPayfast;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Success extends AbstractPayfast
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * Execute: This method illustrate magento2 super power.
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        try {
            return $this->_redirect('checkout/onepage/success', $this->_request->getParams());
        } catch (Exception $e) {
            $this->_logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('We can\'t start Payfast Checkout.'));

            return $this->_redirect('checkout/cart');
        }
    }
}
