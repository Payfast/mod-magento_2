/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active Payfast account. If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/view/messages'
    ],
    function ($,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url,
        quote,
    ) {
        'use strict'

        return Component.extend(
            {
                defaults: {
                    template: 'Payfast_Payfast/payment/payfast'
                },
                redirectAfterPlaceOrder: false,

                getCode: function () {
                    return 'payfast'
                },
                /**
                 * Get value of instruction field.
                 *
                 * @returns {String}
                 */
                getInstructions: function () {
                    return window.checkoutConfig.payment.instructions[this.item.method]
                },
                isAvailable: function () {
                    return quote.totals().grand_total <= 0
                },

                afterPlaceOrder: function () {
                    window.location.replace(url.build(window.checkoutConfig.payment.payfast.redirectUrl.payfast))
                },
                /**
                 * Returns payment acceptance mark link path
                 */
                getPaymentAcceptanceMarkHref: function () {
                    return window.checkoutConfig.payment.payfast.paymentAcceptanceMarkHref
                },
                /**
                 * Returns payment acceptance mark image path
                 */
                getPaymentAcceptanceMarkSrc: function () {
                    return window.checkoutConfig.payment.payfast.paymentAcceptanceMarkSrc
                },
            }
        )
    }
)
