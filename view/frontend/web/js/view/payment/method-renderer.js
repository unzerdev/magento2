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
                type: 'unzer_direct_debit_secured',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/direct_debit_secured'
            },
            {
                type: 'unzer_bank_transfer',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/bank_transfer'
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
                type: 'unzer_invoice_secured',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice_secured'
            },
            {
                type: 'unzer_invoice_secured_b2b',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/invoice_secured_b2b'
            },
            {
                type: 'unzer_paypal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paypal'
            },
            {
                type: 'unzer_sofort',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/sofort'
            },
            {
                type: 'unzer_giropay',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/giropay'
            },
            {
                type: 'unzer_eps',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/eps'
            },
            {
                type: 'unzer_alipay',
                    component: 'Unzer_PAPI/js/view/payment/method-renderer/alipay'
            },
            {
                type: 'unzer_wechatpay',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/wechatpay'
            },
            {
                type: 'unzer_przelewy24',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/przelewy24'
            },
            {
                type: 'unzer_bancontact',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/bancontact'
            },
            {
                type: 'unzer_prepayment',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/prepayment'
            }
        );
        return Component.extend({});
    }
);
