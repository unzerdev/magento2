define([
    'jquery',
    'ko',
    'mage/translate',
    'Magento_Checkout/js/action/place-order',
    'Magento_Ui/js/model/messageList',
    'Unzer_PAPI/js/view/payment/method-renderer/basev2'
], function ($, ko, $t, placeOrderAction, globalMessageList, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            isActivePaymentTokenEnabler: false,
            template: 'Unzer_PAPI/payment/wero'
        }
    });
});
