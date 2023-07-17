define([
    'Unzer_PAPI/js/view/payment/method-renderer/base_vault',
], function (VaultComponent) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Unzer_PAPI/payment/cards_vault',
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get formatted expiration date
         * @returns {String}
         */
        getFormattedExpirationDate: function () {
            return this.details.formattedExpirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * Get Credit Card Brand
         * @returns {String}
         */
        getCardBrand: function () {
            return this.details.cardBrand;
        }
    });
});
