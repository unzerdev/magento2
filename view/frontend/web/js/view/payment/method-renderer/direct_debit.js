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

            /**
             * @returns {Boolean}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * Returns vault code.
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vault_code;
            },

            getData: function () {
                const data = this._super();
                this.vaultEnabler.visitAdditionalData(data);
                return data;
            },

            getPlaceOrderDeferredObject: function () {
                const deferred = $.Deferred();
                const self = this;

                Promise.all([
                    customElements.whenDefined('unzer-sepa-direct-debit')
                ]).then(() => {
                    const unzerCheckout = document.getElementById('unzer-checkout-unzer_direct_debit');

                    if (!unzerCheckout) {
                        deferred.reject($t('SEPA component not found on the page.'));
                        return;
                    }

                    unzerCheckout.onPaymentSubmit = function (response) {
                        const sr = response && response.submitResponse;

                        if (sr && (sr.status === 'SUCCESS' || sr.success === true)) {
                            self.resourceId = sr.data && sr.data.id;

                            placeOrderAction(self.getData(), self.messageContainer)
                                .done(function () {
                                    deferred.resolve.apply(deferred, arguments);
                                })
                                .fail(function (request) {
                                    const msg = request && request.responseJSON && request.responseJSON.message
                                        ? request.responseJSON.message
                                        : $t('An unknown error occurred. Please try again.');
                                    globalMessageList.addErrorMessage({ message: msg });
                                    deferred.reject(msg);
                                });
                        } else {
                            const msg = (sr && sr.message) ? sr.message : $t('Unknown submit error.');
                            globalMessageList.addErrorMessage({
                                message: $t('There was an error placing your order. ') + msg
                            });
                            deferred.reject($t('There was an error placing your order. ') + msg);
                        }
                    };
                }).catch(function (error) {
                    deferred.reject($t('There was an error placing your order. ') + error);
                });

                return deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({ message: error });
                });
            }
        });
    }
);
