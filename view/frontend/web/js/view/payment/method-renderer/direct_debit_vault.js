define([
    'Unzer_PAPI/js/view/payment/method-renderer/base_vault',
], function (VaultComponent) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Unzer_PAPI/payment/direct_debit_vault',
        },

        /**
         * @returns {String}
         */
        getMaskedIban: function () {
            return this.details.maskedIban;
        },

        /**
         * @returns {String}
         */
        getAccountHolder: function () {
            return this.details.accountHolder;
        },

        /**
         * @returns {String}
         */
        getPaymentIcon: function () {
            return window.checkoutConfig.payment['unzer_direct_debit'].paymentIcon;
        },
    });
});
