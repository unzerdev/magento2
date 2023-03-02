define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function ($, ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                customerType: 'b2b',
                template: 'Unzer_PAPI/payment/invoice_secured_b2b'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.InvoiceSecured();
                this.initializeCustomerForm(
                    'unzer-invoice-secured-b2b-customer',
                    'unzer-invoice-secured-b2b-customer-error'
                );
            },

            hideFormFields: function (fieldId) {
                var field = $('#' + fieldId);
                field.find('.field').filter('.city, .company, :has(.country), .street, .zip').hide();
                field.find('.unzerUI.divider-horizontal:eq(0)').hide();
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
