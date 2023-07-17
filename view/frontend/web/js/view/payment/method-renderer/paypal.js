define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function (Component, VaultEnabler) {
        'use strict';

        return Component.extend({
            defaults: {
                isActivePaymentTokenEnabler: false,
                template: 'Unzer_PAPI/payment/paypal'
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.isActivePaymentTokenEnabler(this.isActivePaymentTokenEnabler);
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                return this;
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.Paypal();
            },

            /**
             * @returns {Boolean}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * Returns vault code.
             *
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vault_code;
            },

            getData: function () {
                var data = this._super();

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            }
        });
    }
);
