<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Controller\Redirect;



/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Success extends \Payfast\Payfast\Controller\AbstractPayfast
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /**
     * execute
     * this method illustrate magento2 super power.
     */

    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        try
        {


            // ToDo update the order.
            $this->_redirect('checkout/onepage/success');

        }
        catch ( \Magento\Framework\Exception\LocalizedException $e )
        {
            $this->_logger->error( $pre . $e->getMessage());

            $this->messageManager->addExceptionMessage( $e, $e->getMessage() );
            $this->_redirect( 'checkout/cart' );
        }
        catch ( \Exception $e )
        {
            $this->_logger->error( $pre . $e->getMessage());
            $this->messageManager->addExceptionMessage( $e, __( 'We can\'t start PayFast Checkout.' ) );
            $this->_redirect( 'checkout/cart' );
        }

        return '';
    }

}