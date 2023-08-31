define([
    'Unzer_PAPI/js/view/payment/method-renderer/base_vault',
], function (VaultComponent) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Unzer_PAPI/payment/paypal_vault',
        },

        /**
         * Get PayPal payer email
         * @returns {String}
         */
        getPayerEmail: function () {
            return this.details.payerEmail;
        },

        /**
         * Get type of payment
         * @returns {String}
         */
        getPaymentIcon: function () {
            return window.checkoutConfig.payment['unzer_paypal'].paymentIcon;
        },
    });
});
