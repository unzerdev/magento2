define(
    [
        'jquery',
        'mage/translate',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Vault/js/view/payment/vault-enabler',
    ],
    function (
        $,
        $t,
        Component,
        VaultEnabler
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                isActivePaymentTokenEnabler: false,
                template: 'Unzer_PAPI/payment/paypal',
                paymentCode: 'unzer-paypal',
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.isActivePaymentTokenEnabler(this.isActivePaymentTokenEnabler);
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                return this;
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                if (this.isVaultEnabled()) {
                    const checkbox = document.getElementById('unzer-card-save-paypal-checkbox');
                    const checkboxLabel = document.getElementById('unzer-card-save-paypal-typography');
                    if (checkbox && checkboxLabel) {
                        checkbox.removeAttribute('hidden');
                        checkbox.addEventListener('click', () => {
                            if (!this.isActivePaymentTokenEnabler) {
                                this.isActivePaymentTokenEnabler = true;
                                this.vaultEnabler.isActivePaymentTokenEnabler(true);

                                return;
                            }
                            this.isActivePaymentTokenEnabler = false;
                            this.vaultEnabler.isActivePaymentTokenEnabler(false);
                        })

                        checkboxLabel.textContent = $t('Save for later use.');
                    }
                }

                return retVal;
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
