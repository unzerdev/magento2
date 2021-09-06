define(
    [
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                customerType: 'b2b',
                template: 'Unzer_PAPI/payment/invoice_guaranteed_b2b'
            },

            initializeForm: function () {
                this.initializeCustomerForm(
                    'invoice-guaranteed-b2b-customer',
                    'invoice-guaranteed-b2b-customer-error'
                );
                this.resourceProvider = this.sdk.InvoiceSecured();
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