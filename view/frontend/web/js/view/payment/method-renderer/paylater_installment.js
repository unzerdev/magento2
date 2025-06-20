define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        Component,
        quote
    ) {
        'use strict';

        return Component.extend({
            isThreatMetrixNeeded: true,
            customersBirthDayNeeded: true,
            defaults: {
                template: 'Unzer_PAPI/payment/paylater_installment',
                paymentCode: 'unzer-paylater-installment'
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                this.waitForSetBasketData();

                return retVal;
            },

            waitForSetBasketData: function (maxRetries = 10, interval = 500) {
                const unzerCheckoutElementId = 'unzer-payment-' + this.getCode();
                const unzerPayment = document.getElementById(unzerCheckoutElementId);

                if (unzerPayment && typeof unzerPayment.setBasketData === 'function') {
                    unzerPayment.setBasketData({
                        country: quote.billingAddress().countryId,
                        amount: (quote.totals() ? quote.totals() : quote)['grand_total'],
                        currencyType: (quote.totals() ? quote.totals() : quote)['quote_currency_code']
                    })
                } else if (maxRetries > 0) {
                    console.log('Waiting for setBasketData function to be available...');
                    setTimeout(() => this.waitForSetBasketData(maxRetries - 1, interval), interval);
                } else {
                    console.error('setBasketData is not available after multiple retries.');
                }
            },
        });
    }
);
