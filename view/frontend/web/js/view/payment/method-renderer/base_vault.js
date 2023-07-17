define([
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader',
], function (VaultComponent, url, fullScreenLoader) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            redirectUrl: 'unzer/payment/redirect',
            template: 'Unzer_PAPI/payment/paypal_vault',
        },

        /**
         * Get Token
         * @returns {string}
         */
        getToken: function () {
            return this.publicHash;
        },

        afterPlaceOrder: function () {
            fullScreenLoader.startLoader();
            window.location.replace(url.build(this.redirectUrl));
        },
    })
});
