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
                type: 'hpmgw_cards',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/cards'
            },
            {
                type: 'hpmgw_direct_debit',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/direct_debit'
            },
            {
                type: 'hpmgw_direct_debit_guaranteed',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/direct_debit_guaranteed'
            },
            {
                type: 'hpmgw_flexipay_direct',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/flexipay_direct'
            },
            {
                type: 'hpmgw_ideal',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/ideal'
            },
            {
                type: 'hpmgw_invoice',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/invoice'
            },
            {
                type: 'hpmgw_invoice_guaranteed',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/invoice_guaranteed'
            },
            {
                type: 'hpmgw_paypal',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/paypal'
            },
            {
                type: 'hpmgw_sofort',
                component: 'Heidelpay_MGW/js/view/payment/method-renderer/sofort'
            }
        );
        return Component.extend({});
    }
);