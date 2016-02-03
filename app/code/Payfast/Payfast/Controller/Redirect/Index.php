<?php
/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfast\Payfast\Controller\Redirect;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends \Payfast\Payfast\Controller\AbstractPayfast
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;


    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = \Payfast\Payfast\Model\Config::METHOD_CODE;


    /**
     * execute
     * this method illustrate magento2 super power.
     */

    public function execute()
    {
        $pre = __METHOD__ . " : ";
        pflog($pre . 'bof');

        $page_object = $this->pageFactory->create();;

        try
        {
            $this->_initCheckout();
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

        return $page_object;
    }

}