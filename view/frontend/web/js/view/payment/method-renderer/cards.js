define(
    [
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function (ko, Component, VaultEnabler) {
        'use strict';

        return Component.extend({
            defaults: {
                isActivePaymentTokenEnabler: false,
                fields: {
                    cvc: {valid: null},
                    expiry: {valid: null},
                    number: {valid: null},
                },
                template: 'Unzer_PAPI/payment/cards'
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.isActivePaymentTokenEnabler(this.isActivePaymentTokenEnabler);
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                return this;
            },

            initializeForm: function () {
                var self = this;

                this.resourceProvider = this.sdk.Card();
                this.resourceProvider.create('number', {
                    containerId: 'unzer-card-element-id-number',
                    onlyIframe: false
                });
                this.resourceProvider.create('expiry', {
                    containerId: 'unzer-card-element-id-expiry',
                    onlyIframe: false
                });
                this.resourceProvider.create('cvc', {
                    containerId: 'unzer-card-element-id-cvc',
                    onlyIframe: false
                });

                this.fields.cvc.valid = ko.observable(false);
                this.fields.expiry.valid = ko.observable(false);
                this.fields.number.valid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    if ("type" in event) {
                        self.fields[event.type].valid("success" in event && event.success);
                    }
                });
            },

            allInputsValid: function () {
                var self = this;

                return ko.computed(function () {
                    return self.fields.cvc.valid() &&
                        self.fields.expiry.valid() &&
                        self.fields.number.valid();
                });
            },

            validate: function () {
                return this.allInputsValid()();
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
