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
                        countryCode: this.quote.billingAddress()['countryId'],
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
                    allowCreditCards: this._getMethodConfig('allow_credit_cards'),
                    allowPrepaidCards: this._getMethodConfig('allow_prepaid_cards'),
                    onPaymentAuthorizedCallback: (paymentData) => {
                        //return this._onPaymentAuthorizedCallback(paymentData, this);

                        this.paymentData = paymentData;
                        console.log(paymentData);
                        return this.placeOrder();
                    },
                };
            },

            // _onPaymentAuthorizedCallback: function (paymentData, self) {
            //
            //     return self.resourceProvider.createResource(paymentData)
            //         .then(result => {
            //             this.paymentData = paymentData;
            //             this.placeOrder();
            //             return { status: 'success' }
            //         })
            //         .catch(function (error) {
            //             let errorMessage = error.customerMessage || error.message || 'Error';
            //             if (error.data && Array.isArray(error.data.errors) && error.data.errors[0]) {
            //                 errorMessage = error.data.errors[0].customerMessage || 'Error'
            //             }
            //
            //             document.getElementById(self.errorFieldId).innerHTML = errorMessage;
            //
            //             return {
            //                 status: 'error',
            //                 message: errorMessage || 'Unexpected error'
            //             }
            //         });
            // },

            allInputsValid: function () {
                return true;
            },

            validate: function () {
                return this.allInputsValid();
            }
        });
    }
);
