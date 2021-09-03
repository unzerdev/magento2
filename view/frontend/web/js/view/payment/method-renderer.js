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
                component: 'Unzer_PAPI/js/view/payment/method-renderer/cards'
            },
            {
                type: 'hpmgw_direct_debit',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/direct_debit'
            },
            {
                type: 'hpmgw_direct_debit_guaranteed',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/direct_debit_guaranteed'
            },
            {
                type: 'hpmgw_flexipay_direct',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/flexipay_direct'
            },
            {
                type: 'hpmgw_ideal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/ideal'
            },
            {
                type: 'hpmgw_invoice',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice'
            },
            {
                type: 'hpmgw_invoice_guaranteed',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice_guaranteed'
            },
            {
                type: 'hpmgw_invoice_guaranteed_b2b',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice_guaranteed_b2b'
            },
            {
                type: 'hpmgw_paypal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paypal'
            },
            {
                type: 'hpmgw_sofort',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/sofort'
            }
        );
        return Component.extend({});
    }
);