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
    function (
        $,
        ko,
        $t,
        globalMessageList,
        placeOrderAction,
        Component,
        quote
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/googlepay',
                buttonNeeded: false,
                paymentCode: 'unzer-google-pay'
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                this.waitForSetGooglePayData();
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

            waitForSetGooglePayData: function (maxRetries = 10, interval = 500) {
                const unzerPaymentElement = document.getElementById('unzer-payment-unzer_googlepay');

                if (unzerPaymentElement && typeof unzerPaymentElement.setGooglePayData === 'function') {
                    unzerPaymentElement.setGooglePayData({
                        gatewayMerchantId: this._getMethodConfig('unzer_channel_id'),
                        merchantInfo: {
                            merchantId: this._getMethodConfig('merchant_id'),
                            merchantName: this._getMethodConfig('merchant_name')
                        },
                        transactionInfo: {
                            countryCode: this._getMethodConfig('country_code'),
                            currencyCode: (quote.totals() ? quote.totals() : quote)['quote_currency_code'],
                            totalPrice: String((quote.totals() ? quote.totals() : quote)['grand_total'])
                        },
                        buttonOptions: {
                            buttonColor: this._getMethodConfig('button_color'),
                            buttonRadius: this._getMethodConfig('button_border_radius'),
                            buttonSizeMode: this._getMethodConfig('button_size_mode'),
                        },
                        allowedCardNetworks: this._getMethodConfig('allowed_card_networks'),
                        allowCreditCards: this._getMethodConfig('allow_credit_cards') === "1",
                        allowPrepaidCards: this._getMethodConfig('allow_prepaid_cards') === "1"
                    });
                } else if (maxRetries > 0) {
                    console.log('Waiting for setGooglePayData function to be available...');
                    setTimeout(() => this.waitForSetGooglePayData(maxRetries - 1, interval), interval);
                } else {
                    console.error('setGooglePayData is not available after multiple retries.');
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
                            globalMessageList.addErrorMessage({
                                message: request.responseJSON.message
                            });
                            deferred.reject(request.responseJSON.message);
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
            }
        });
    }
);
