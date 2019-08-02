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
                customer: {valid: false},
                template: 'Heidelpay_Gateway2/payment/invoice_guaranteed'
            },

            initializeForm: function () {
                var self = this;

                this.customerProvider = this.sdk.Customer();
                this.customerProvider.create({
                    containerId: 'invoice-customer'
                });

                this.customer.valid = ko.observable(false);

                this.customerProvider.addEventListener('validate', function (event) {
                    self.customer.valid("success" in event && event.success);
                });

                this.resourceProvider = this.sdk.InvoiceGuaranteed();
            },

            allInputsValid: function () {
                return this.customer.valid;
            },

            validate: function () {
                return this.allInputsValid()();
            },
        });
    }
);