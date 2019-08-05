define(
    [
        'ko',
        'Heidelpay_Gateway2/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            redirectUrl: 'checkout/onepage/success',

            defaults: {
                template: 'Heidelpay_Gateway2/payment/invoice_guaranteed'
            },

            initializeForm: function () {
                this.initializeCustomerForm('invoice-customer');
                this.resourceProvider = this.sdk.InvoiceGuaranteed();
            },

            allInputsValid: function () {
                return this.customerValid;
            },

            validate: function () {
                return this.allInputsValid()();
            },
        });
    }
);