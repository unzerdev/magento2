define(
    [
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/invoice_secured'
            },

            initializeForm: function () {
                this.initializeCustomerForm(
                    'unzer-invoice-secured-customer',
                    'unzer-invoice-secured-customer-error'
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