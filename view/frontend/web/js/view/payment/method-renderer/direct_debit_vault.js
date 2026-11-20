define([
    'jquery',
    'ko',
    'mage/translate',
    'Unzer_PAPI/js/view/payment/method-renderer/base_vault',
    'Unzer_PAPI/js/model/checkout/terms-checked',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/place-order',
    'Magento_Ui/js/model/messageList',
], function ($, ko, $t, VaultComponent, termsChecked, quote, placeOrderAction, globalMessageList) {
    'use strict';

    return VaultComponent.extend({
        allTermsChecked: termsChecked.allTermsChecked,

        defaults: {
            template: 'Unzer_PAPI/payment/direct_debit_vault',
            mandateElementName: 'unzer-sepa-mandate',
        },

        initialize: function () {
            this._super();

            import('//static-v2.unzer.com/v2/ui-components/index.js')
                .catch(error => {
                    console.error('Failed to load Unzer scripts: ' + error);
                });

            quote.paymentMethod.subscribe(selectedMethod => {
                if (!selectedMethod || selectedMethod.method !== this.getId()) {
                    this.removeMandate();
                }
            });

            return this;
        },

        getMaskedIban: function () {
            return this.details.maskedIban;
        },

        getAccountHolder: function () {
            return this.details.accountHolder;
        },

        getPaymentIcon: function () {
            return this.getDirectDebitConfig().paymentIcon;
        },

        getDirectDebitConfig: function () {
            return window.checkoutConfig.payment['unzer_direct_debit'] || {};
        },

        getPublicKey: function () {
            return this.getDirectDebitConfig().publicKey || window.checkoutConfig.payment.unzer.publicKey;
        },

        getMandateContainerId: function () {
            return 'unzer-component-' + this.getId();
        },

        initializeForm: function () {
            if (this.isActive()) {
                this.renderMandate();
            }
        },

        selectPaymentMethod: function () {
            const retVal = this._super();

            this.renderMandate();

            return retVal;
        },

        renderMandate: function () {
            termsChecked.init(this);

            const container = $('#' + this.getMandateContainerId());
            container.empty();

            const unzerPayment = $('<unzer-payment>')
                .attr('id', 'unzer-payment-' + this.getId())
                .attr('publicKey', this.getPublicKey())
                .attr('locale', window.checkoutConfig.payment.unzer.locale)
                .append($(`<${this.mandateElementName}>`).attr('recurring', 'false'));

            container.append(unzerPayment);
            container.append(this.createUnzerCheckoutElement());
        },

        createUnzerCheckoutElement: function () {
            const payButton = $('<button>')
                .attr('id', 'unzer-pay-button-' + this.getId())
                .attr('type', 'submit')
                .attr('title', $t('Place Order'))
                .addClass('button action primary checkout unzerPlaceOrderPadding')
                .attr('data-bind', `
                    click: placeOrder,
                    css: {disabled: !isPlaceOrderActionAllowed() || !allTermsChecked()}
                `)
                .append($('<span>').text($t('Place Order')));

            ko.applyBindings(this, payButton[0]);

            return $('<unzer-checkout>')
                .attr('id', 'unzer-checkout-' + this.getId())
                .append(payButton);
        },

        removeMandate: function () {
            $('#' + this.getMandateContainerId()).empty();
        },

        getPlaceOrderDeferredObject: function () {
            const deferred = $.Deferred();

            customElements.whenDefined('unzer-payment').then(() => {
                const unzerCheckout = document.getElementById('unzer-checkout-' + this.getId());

                unzerCheckout.onPaymentSubmit = response => {
                    if (!response.submitResponse || !response.submitResponse.success) {
                        const message = response.submitResponse && response.submitResponse.message
                            ? response.submitResponse.message
                            : $t('This payment method is currently unavailable.');

                        deferred.reject(message);

                        return;
                    }

                    placeOrderAction(this.getData(), this.messageContainer)
                        .done(function () {
                            deferred.resolve.apply(deferred, arguments);
                        })
                        .fail(request => {
                            deferred.reject(
                                request.responseJSON && request.responseJSON.message
                                    ? request.responseJSON.message
                                    : $t('An unknown error occurred. Please try again.')
                            );
                        });
                };
            }).catch(error => {
                deferred.reject($t('There was an error placing your order.') + ' ' + error);
            });

            return deferred.fail(message => {
                globalMessageList.addErrorMessage({message: message});
            });
        },
    });
});
