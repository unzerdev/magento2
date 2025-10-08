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
                type: 'unzer_ideal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/ideal'
            },
            {
                type: 'unzer_paylater_invoice',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paylater_invoice'
            },
            {
                type: 'unzer_paylater_invoice_b2b',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paylater_invoice_b2b'
            },
            {
                type: 'unzer_paylater_installment',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paylater_installment'
            },
            {
                type: 'unzer_paylater_direct_debit',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paylater_direct_debit'
            },
            {
                type: 'unzer_paypal',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/paypal'
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
            },
            {
                type: 'unzer_applepayv2',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/applepayv2'
            },
            {
                type: 'unzer_googlepay',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/googlepay'
            },
            {
                type: 'unzer_twint',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/twint'
            },
            {
                type: 'unzer_open_banking',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/open_banking'
            },
            {
                type: 'unzer_klarna',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/klarna'
            },
            {
                type: 'unzer_wero',
                component: 'Unzer_PAPI/js/view/payment/method-renderer/wero'
            }
        );
        return Component.extend({});
    }
);
