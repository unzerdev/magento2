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
                        checkbox.closest('.unzer-save-for-later').removeAttribute('hidden');
                        checkbox.checked = !!this.isActivePaymentTokenEnabler;
                        checkbox.onchange = () => {
                            this.isActivePaymentTokenEnabler = checkbox.checked;
                            this.vaultEnabler.isActivePaymentTokenEnabler(checkbox.checked);
                        };

                        checkboxLabel.textContent = $t('Save for later use.');
                    }
                }

                return retVal;
            },

            getData: function () {
                var data = this._super();

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            }
        });
    }
);
