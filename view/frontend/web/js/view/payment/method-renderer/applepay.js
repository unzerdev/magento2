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
                template: 'Unzer_PAPI/payment/applepay'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.ApplePay();
            },
            setupApplePaySession() {
                this.startApplePaySession(this.resourceProvider)
            },

            startApplePaySession: function (resourceProvider) {
                var self = this;
                //console.log(window.checkoutConfig.quoteData);
                window.checkoutConfig.quoteData.trigger_reload = new Date().getTime();
                let applePayPaymentRequest = {
                    countryCode: quote.billingAddress().countryId,
                    currencyCode: window.checkoutConfig.quoteData.quote_currency_code,
                    totalLabel: window.checkoutConfig.payment.unzer_applepay.label, //display_name
                    totalAmount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2),
                    supportedNetworks: window.checkoutConfig.payment.unzer_applepay.supportedNetworks,
                    merchantCapabilities: window.checkoutConfig.payment.unzer_applepay.merchantCapabilities,
                    requiredShippingContactFields: [],
                    requiredBillingContactFields: [],
                    total: {
                        label: window.checkoutConfig.payment.unzer_applepay.label,
                        amount: Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2)
                    }
                };

                if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
                    const session = new ApplePaySession(6, applePayPaymentRequest);

                    session.onvalidatemerchant = function (event) {
                        self.merchantValidationCallback(session, event);
                    };

                    session.onpaymentauthorized = function (event) {
                        self.applePayAuthorizedCallback(resourceProvider, event, session);
                    };

                    session.onpaymentmethodselected = function (event) {
                        const update = {
                            "newTotal": {
                                "label": window.checkoutConfig.payment.unzer_applepay.label,
                                "type": "final",
                                "amount": Number(window.checkoutConfig.quoteData.base_grand_total).toFixed(2)
                            }
                        };
                        session.completePaymentMethodSelection(update);
                    };

                    session.oncancel = function (event) {

                    };

                    session.begin();

                } else {
                    self.handleError("This device does not support Apple Pay!");
                }
            },

            handleError: function (message) {
                jQuery('#unzer-applepay-error').html(message);
            },

            merchantValidationCallback: function (session, event) {
                jQuery.post('/unzer/applepay/merchantvalidation', JSON.stringify({"merchantValidationUrl": event.validationURL}), null, 'json')
                    .done(function (validationResponse) {
                        try {
                            session.completeMerchantValidation(validationResponse);
                        } catch (e) {
                            self.handleError(JSON.stringify(e.message));
                            session.abort();
                        }

                    })
                    .fail(function (error) {
                        self.handleError(JSON.stringify(error.statusText));
                        session.abort();
                    });
            },

            applePayAuthorizedCallback: function (resourceProvider, event, session) {
                var self = this;
                // Get payment data from event. "event.payment" also contains contact information, if they were set via Apple Pay.
                const paymentData = event.payment.token.paymentData;
                session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});
                this.paymentData = paymentData;

                self.placeOrder();
            }
        });
    },
);

