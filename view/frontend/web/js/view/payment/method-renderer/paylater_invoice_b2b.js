define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/place-order',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2'
    ],
    function (
        $,
        ko,
        $t,
        globalMessageList,
        placeOrderAction,
        Component
    ) {
        'use strict';

        return Component.extend({
            isThreatMetrixNeeded: true,

            defaults: {
                customerType: 'b2b',
                template: 'Unzer_PAPI/payment/paylater_invoice_b2b',
                paymentCode: 'unzer-paylater-invoice',
                customerNeeded: true,
            },
        });
    }
);
