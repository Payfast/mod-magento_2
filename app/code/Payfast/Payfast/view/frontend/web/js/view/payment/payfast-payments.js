/*browser:true*/
/*global define*/
/*Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
    Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'payfast',
                component: 'Payfast_Payfast/js/view/payment/method-renderer/payfast-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);