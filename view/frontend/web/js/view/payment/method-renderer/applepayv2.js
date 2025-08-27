define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/place-order',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Checkout/js/model/quote'
    ],
    function ($,
              ko,
              $t,
              globalMessageList,
              placeOrderAction,
              Component,
              quote) {

        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/applepayv2',
                buttonNeeded: false,
                paymentCode: 'unzer-apple-pay'
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                this.waitForSetApplePayData();
                const unzerCheckoutElementId = 'unzer-checkout-' + this.getCode();
                const unzerCheckout = document.getElementById(unzerCheckoutElementId);
                unzerCheckout.onPaymentSubmit = response => {
                    if (response.submitResponse && response.submitResponse.success) {
                        this.resourceId = response.submitResponse.data.id;
                        const result = this.placeOrder();
                        if (result) {
                            return {status: 'success'};
                        }
                    }

                    return {status: 'error', message: 'Unexpected error'}

                }
                return retVal;
            },

            waitForSetApplePayData: function (maxRetries = 10, interval = 500) {
                const unzerPaymentElement = document.getElementById('unzer-payment-' + this.getCode());

                if (unzerPaymentElement && typeof unzerPaymentElement.setApplePayData === 'function') {
                    const supportedNetworks = window.checkoutConfig.payment.unzer_applepayv2.supportedNetworks.map((network) => network.toLowerCase());

                    unzerPaymentElement.setApplePayData({
                        countryCode: quote.billingAddress().countryId,
                        currencyCode: window.checkoutConfig.quoteData.quote_currency_code,
                        totalLabel: window.checkoutConfig.payment.unzer_applepayv2.label,
                        totalAmount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2),
                        supportedNetworks: supportedNetworks,
                        merchantCapabilities: window.checkoutConfig.payment.unzer_applepayv2.merchantCapabilities,
                        requiredShippingContactFields: [],
                        requiredBillingContactFields: [],
                        total: {
                            label: window.checkoutConfig.payment.unzer_applepayv2.label,
                            amount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2)
                        },
                    });
                } else if (maxRetries > 0) {
                    console.log('Waiting for setApplePayData function to be available...');
                    setTimeout(() => this.waitForSetApplePayData(maxRetries - 1, interval), interval);
                } else {
                    console.error('setApplePayData is not available after multiple retries.');
                }
            },

            getPlaceOrderDeferredObject: function () {
                let deferred = $.Deferred(),
                    self = this;

                Promise.all([
                    customElements.whenDefined(this.paymentCode)
                ]).then(() => {
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
                }).catch(error => {
                    globalMessageList.addErrorMessage({
                        message: 'There was an error placing your order. ' + error
                    });
                    deferred.reject($t('There was an error placing your order. ' + error));
                });

                return deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });
            },
        });
    },
);

