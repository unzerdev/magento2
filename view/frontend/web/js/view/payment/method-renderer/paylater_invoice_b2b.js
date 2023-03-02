define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Unzer_PAPI/js/model/checkout/threat-metrix'
    ],
    function ($, ko, Component, threatMetrix) {
        'use strict';

        return Component.extend({
            defaults: {
                customerType: 'b2b',
                template: 'Unzer_PAPI/payment/paylater_invoice_b2b'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.PaylaterInvoice();
                this.initializeCustomerForm(
                    'unzer-paylater-invoice-b2b-customer',
                    'unzer-paylater-invoice-b2b-customer-error'
                );
            },

            _initializeCustomerFormForB2bCustomer: function (fieldId, errorFieldId, customer) {
                threatMetrix.init(customer.threat_metrix_id);

                this.resourceProvider.create({
                    containerId: fieldId+'-optin',
                    customerType: 'B2B',
                    errorHolderId: errorFieldId,
                });

                this.customerProvider = this.sdk.B2BCustomer();
                this.customerProvider.initFormFields(customer);
                this.customerProvider.create({
                    containerId: fieldId,
                    errorHolderId: errorFieldId,
                    fields: ['companyInfo'],
                    showHeader: false,
                    paymentTypeName: 'paylater-invoice'
                });

                this.hideFormFields(fieldId);
            },

            hideFormFields: function (fieldId) {
                this._super(fieldId);

                var field = $('#' + fieldId);
                field.find('.field').filter('.checkbox-billingAddress, .email').hide();
                field.find('.field').filter('.billing-name, .billing-street, .billing-zip, .billing-city, :has(.billing-country)').hide();

                field.find('.unzerUI.form>.checkboxLabel').hide();
                field.find('.unzerUI.form>.salutation-unzer-paylater-invoice-b2b-customer').hide();
            },

            allInputsValid: function () {
                return this.customerValid;
            },

            validate: function () {
                return this.allInputsValid()();
            }
        });
    }
);
