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
                type: 'unzer_cards',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/cards'
            },
            {
                type: 'unzer_direct_debit',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/direct_debit'
            },
            {
                type: 'unzer_direct_debit_guaranteed',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/direct_debit_guaranteed'
            },
            {
                type: 'unzer_flexipay_direct',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/flexipay_direct'
            },
            {
                type: 'unzer_ideal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/ideal'
            },
            {
                type: 'unzer_invoice',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice'
            },
            {
                type: 'unzer_invoice_guaranteed',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice_guaranteed'
            },
            {
                type: 'unzer_invoice_guaranteed_b2b',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice_guaranteed_b2b'
            },
            {
                type: 'unzer_paypal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paypal'
            },
            {
                type: 'unzer_sofort',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/sofort'
            }
        );
        return Component.extend({});
    }
);