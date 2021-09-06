define(
    [
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/invoice_guaranteed'
            },

            initializeForm: function () {
                this.initializeCustomerForm(
                    'invoice-guaranteed-customer',
                    'invoice-guaranteed-customer-error'
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