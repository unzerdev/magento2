define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Vault/js/view/payment/vault-enabler',
    ],
    function ($,
              ko,
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
                template: 'Unzer_PAPI/payment/cards',
                paymentCode: 'unzer-card',
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
                    const checkbox = document.getElementById('unzer-card-save-card-checkbox');
                    const checkboxLabel = document.getElementById('unzer-card-save-card-typography');
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
                const data = this._super();

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },

            getPlaceOrderDeferredObject: function () {
                let deferred = $.Deferred(),
                    self = this;

                Promise.all([
                    customElements.whenDefined('unzer-card')
                ]).then(() => {
                    const unzerCheckout = document.getElementById('unzer-checkout-unzer_cards');
                    unzerCheckout.onPaymentSubmit = response => {
                        if (response.submitResponse && response.submitResponse.status === 'SUCCESS') {
                            this.resourceId = response.submitResponse.data.id;
                            placeOrderAction(self.getData(), self.messageContainer)
                                .done(function () {
                                    deferred.resolve.apply(deferred, arguments);
                                })
                                .fail(function (request) {
                                    if (request.responseJSON && request.responseJSON.message) {
                                        globalMessageList.addErrorMessage({
                                            message: request.responseJSON.message
                                        });
                                        deferred.reject(request.responseJSON.message);
                                    } else {
                                        globalMessageList.addErrorMessage({
                                            message: 'An unknown error occurred. Please try again.'
                                        });
                                        deferred.reject('An unknown error occurred.');
                                    }
                                });
                        } else {
                            deferred.reject($t('There was an error placing your order. ' + response.submitResponse.message));
                        }
                    };
                }).catch(error => {
                    deferred.reject($t('There was an error placing your order. ' + error));
                });

                return deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });
            }
        });
    }
);
