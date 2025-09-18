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
                        amount: (quote.totals() ? quote.totals() : quote)['grand_total'],
                        currencyType: (quote.totals() ? quote.totals() : quote)['base_currency_code']
                    })

                    const billing = quote.billingAddress();
                    const shipping = quote.shippingAddress();

                    const customer = {
                        firstname: billing ? billing.firstname : '',
                        lastname: billing ? billing.lastname : '',
                        email: quote.guestEmail ? quote.guestEmail : (window.customerData ? window.customerData.email : ''),
                        birthDate: customerData.dob.split('T')[0],

                        billingAddress: billing ? {
                            name: (billing.firstname || '') + ' ' + (billing.lastname || ''),
                            street: Array.isArray(billing.street) ? billing.street.join(' ') : billing.street,
                            zip: billing.postcode,
                            city: billing.city,
                            country: billing.countryId
                        } : {},

                        shippingAddress: shipping ? {
                            name: (shipping.firstname || '') + ' ' + (shipping.lastname || ''),
                            street: Array.isArray(shipping.street) ? shipping.street.join(' ') : shipping.street,
                            zip: shipping.postcode,
                            city: shipping.city,
                            country: shipping.countryId
                        } : {}
                    };

                    unzerPayment.setCustomerData(customer);
                } else if (maxRetries > 0) {
                    console.log('Waiting for setBasketData function to be available...');
                    setTimeout(() => this.waitForSetBasketData(maxRetries - 1, interval), interval);
                } else {
                    console.error('setBasketData is not available after multiple retries.');
                }
            }
            ,
        });
    }
)
;
