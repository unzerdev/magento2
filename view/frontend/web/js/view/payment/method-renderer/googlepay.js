define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        ko,
        $t,
        placeOrderAction,
        globalMessageList,
        Component,
        quote
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/googlepay',
                buttonNeeded: false,
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                const unzerPaymentElement = document.getElementById('unzer-payment-unzer_googlepay');
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

                const unzerCheckoutElementId = 'unzer-checkout-' + this.getCode();
                const unzerCheckout = document.getElementById(unzerCheckoutElementId);
                unzerCheckout.onPaymentSubmit = response => {
                    const result = this.placeOrder();
                    if (result) {
                        return {status: 'success'};
                    } else {
                        return {status: 'error', message: 'Unexpected error'}
                    }
                }
                return retVal;
            },

            createSpecificPaymentElement: function () {
                return $('<unzer-google-pay>');
            },

            getPlaceOrderDeferredObject: function () {
                let deferred = $.Deferred(),
                    self = this;

                Promise.all([
                    customElements.whenDefined('unzer-google-pay')
                ]).then(() => {
                    const unzerCheckoutElementId = 'unzer-checkout-' + this.getCode();
                    const unzerCheckout = document.getElementById(unzerCheckoutElementId);
                    unzerCheckout.onPaymentSubmit = response => {
                        if (response.submitResponse && response.submitResponse.success) {
                            this.resourceId = response.submitResponse.data.id;
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
                        } else {
                            globalMessageList.addErrorMessage({
                                message: 'There was an error placing your order. ' + response.submitResponse.message
                            });
                            deferred.reject($t('There was an error placing your order. ' + response.submitResponse.message));
                        }
                    };
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
