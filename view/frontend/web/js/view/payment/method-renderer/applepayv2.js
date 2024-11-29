define(
    [
        'jquery',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote',
        '//applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js'
    ],
    function ($, Component, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/applepayv2'
            },

            initialize: function () {
                this._super();

                return this;
            },

            initializeForm: function () {
                const self = this;
                if (!this.isApplePayAvailable()) {
                    this.handleError("This device does not support Apple Pay!");
                }
                self.resourceProvider = this.sdk.ApplePay();
            },

            isApplePayAvailable: function () {
                return window.ApplePaySession && ApplePaySession.canMakePayments();
            },

            startApplePaySession: function () {
                let self = this;
                window.checkoutConfig.quoteData.trigger_reload = new Date().getTime();

                const supportedNetworks = window.checkoutConfig.payment.unzer_applepayv2.supportedNetworks.map((network) => network.toLowerCase())

                const applePayPaymentRequest = {
                    countryCode: quote.billingAddress().countryId,
                    currencyCode: window.checkoutConfig.quoteData.quote_currency_code,
                    totalLabel: window.checkoutConfig.payment.unzer_applepayv2.label, //display_name
                    totalAmount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2),
                    supportedNetworks: supportedNetworks,
                    merchantCapabilities: window.checkoutConfig.payment.unzer_applepayv2.merchantCapabilities,
                    requiredShippingContactFields: [],
                    requiredBillingContactFields: [],
                    total: {
                        label: window.checkoutConfig.payment.unzer_applepayv2.label,
                        amount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2)
                    },
                };

                const session = this.resourceProvider.initApplePaySession(applePayPaymentRequest, 6);

                session.onpaymentauthorized = function(event) {
                    const paymentData = event.payment.token.paymentData;
                    session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});
                    self.paymentData = paymentData;

                    self.placeOrder();
                };

                session.onpaymentmethodselected = function(event) {
                    let update = {
                        newTotal: {
                            label: window.checkoutConfig.payment.unzer_applepayv2.label,
                            type: "final",
                            amount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2)
                        }
                    };

                    session.completePaymentMethodSelection(update);
                };

                session.begin();
            },

            handleError: function (message) {
                $('#unzer-applepay-error').html(message);
            },
        });
    },
);

