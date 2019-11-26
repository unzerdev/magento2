define(
    [
        'ko',
        'Heidelpay_MGW/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                customerType: 'b2b',
                template: 'Heidelpay_MGW/payment/invoice_guaranteed_b2b'
            },

            initializeForm: function () {
                this.initializeCustomerForm(
                    'invoice-guaranteed-b2b-customer',
                    'invoice-guaranteed-b2b-customer-error'
                );
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