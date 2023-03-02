define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Unzer_PAPI/js/model/checkout/threat-metrix',
    ],
    function ($, ko, Component, threatMetrix) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/paylater_invoice'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.PaylaterInvoice();
                this.initializeCustomerForm(
                    'unzer-paylater-invoice-customer',
                    'unzer-paylater-invoice-customer-error'
                );
            },

            _initializeCustomerFormForB2cCustomer: function (fieldId, errorFieldId, customer) {
                threatMetrix.init(customer.threat_metrix_id);

                this.resourceProvider.create({
                    containerId: fieldId+'-optin',
                    customerType: 'B2C',
                    errorHolderId: errorFieldId,
                });

                this.customerProvider = this.sdk.Customer();
                this.customerProvider.initFormFields(customer);
                this.customerProvider.create({
                    containerId: fieldId,
                    errorHolderId: errorFieldId,
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
                field.find('.unzerUI.form>.salutation-unzer-paylater-invoice-customer').hide();
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
