define(
    [
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Vault/js/view/payment/vault-enabler',
    ],
    function (
        $,
        $t,
        placeOrderAction,
        globalMessageList,
        Component,
        VaultEnabler
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                isActivePaymentTokenEnabler: false,
                template: 'Unzer_PAPI/payment/direct_debit',
                paymentCode: 'unzer-sepa-direct-debit'
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.isActivePaymentTokenEnabler(this.isActivePaymentTokenEnabler);
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                return this;
            },

            selectPaymentMethod: function () {
                const retVal = this._super();

                // Vault checkbox
                if (this.isVaultEnabled()) {
                    const checkbox = document.getElementById('unzer-card-save-sepa-checkbox');
                    const checkboxLabel = document.getElementById('unzer-card-save-sepa-typography');

                    if (checkbox && checkboxLabel) {
                        checkbox.removeAttribute('hidden');
                        checkbox.checked = !!this.isActivePaymentTokenEnabler;

                        checkbox.addEventListener('click', () => {
                            this.isActivePaymentTokenEnabler = !this.isActivePaymentTokenEnabler;
                            this.vaultEnabler.isActivePaymentTokenEnabler(this.isActivePaymentTokenEnabler);
                        });

                        checkboxLabel.textContent = $t('Save for later use.');
                    }
                }

                return retVal;
            },

            getData: function () {
                const data = this._super();
                this.vaultEnabler.visitAdditionalData(data);
                return data;
            }
        });
    }
);
