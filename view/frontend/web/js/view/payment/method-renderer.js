define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'hpg2_creditcard',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/creditcard'
            },
            {
                type: 'hpg2_flexipay_direct',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/flexipay_direct'
            },
            {
                type: 'hpg2_ideal',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/ideal'
            },
            {
                type: 'hpg2_invoice',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/invoice'
            },
            {
                type: 'hpg2_paypal',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/paypal'
            },
            {
                type: 'hpg2_sofort',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/sofort'
            }
        );
        return Component.extend({});
    }
);