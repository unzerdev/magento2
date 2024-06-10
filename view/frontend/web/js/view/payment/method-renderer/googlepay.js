define(
    [
        'jquery',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote',
        '//pay.google.com/gp/p/js/pay.js'
    ],
    function ($, Component, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/googlepay',
                fieldId: 'unzer-googlepay-container',
                errorFieldId: 'unzer-googlepay-error-holder',
                quote: quote
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.Googlepay();
                this._initializeGooglePay();

                this.allTermsChecked.subscribe((allTermsChecked) => {
                    $('#gpay-button-online-api-id').prop('disabled', !allTermsChecked);
                });
            },

            _initializeGooglePay: function () {
                let self = this;

                const paymentData = this.resourceProvider.initPaymentDataRequestObject(this._buildPaymentData());

                this.resourceProvider.create(
                    {
                        containerId: self.fieldId,
                    },
                    paymentData
                );
            },

            _buildPaymentData: function () {
                return {
                    gatewayMerchantId: this._getMethodConfig('unzer_channel_id'),
                    merchantInfo: {
                        merchantId: this._getMethodConfig('merchant_id'),
                        merchantName: this._getMethodConfig('merchant_name')
                    },
                    transactionInfo: {
                        countryCode: this._getMethodConfig('country_code'),
                        totalPrice: String((this.quote.totals() ? this.quote.totals() : this.quote)['grand_total']),
                        currencyCode: (this.quote.totals() ? this.quote.totals() : this.quote)['quote_currency_code'],
                    },
                    buttonOptions: {
                        buttonColor: this._getMethodConfig('button_color'),
                        // border radius is not possible to set at the moment with unzer.js
                        buttonRadius: this._getMethodConfig('button_border_radius'),
                        buttonSizeMode: this._getMethodConfig('button_size_mode'),
                    },
                    allowedCardNetworks: this._getMethodConfig('allowed_card_networks'),
                    allowCreditCards: this._getMethodConfig('allow_credit_cards') === "1",
                    allowPrepaidCards: this._getMethodConfig('allow_prepaid_cards') === "1",
                    onPaymentAuthorizedCallback: (paymentData) => {
                        this.paymentData = paymentData;
                        const result = this.placeOrder();
                        if (result) {
                            return { status: 'success' };
                        } else {
                            return { status: 'error', message: 'Unexpected error' }
                        }
                    },
                };
            },

            allInputsValid: function () {
                return true;
            },

            validate: function () {
                return this.allInputsValid();
            }
        });
    }
);
