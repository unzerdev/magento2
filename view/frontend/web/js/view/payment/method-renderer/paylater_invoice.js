define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Unzer_PAPI/js/model/checkout/threat-metrix',
        'Unzer_PAPI/js/model/checkout/address-updater'
    ],
    function ($, ko, Component, threatMetrix, addressUpdater) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/paylater_invoice',
                fieldId: 'unzer-paylater-invoice-customer',
                errorFieldId: 'unzer-paylater-invoice-customer-error'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.PaylaterInvoice();
                this.initializeCustomerForm(
                    this.fieldId,
                    this.errorFieldId
                );

                addressUpdater.registerSubscribers(this);
            },

            _initializeCustomerFormForB2cCustomer: function (fieldId, errorFieldId, customer) {
                threatMetrix.init(customer.threat_metrix_id);

                this.resourceProvider.create({
                    containerId: fieldId + '-optin',
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

                const field = $('#' + fieldId);
                field.find('.field').filter('.checkbox-billingAddress, .email').hide();
                field.find('.field').filter('.billing-name, .billing-street, .billing-zip, .billing-city, :has(.billing-country)').hide();

                field.find('.unzerUI.form>.checkboxLabel').hide();
                field.find('.unzerUI.form>.salutation-unzer-paylater-invoice-customer').hide();
            },

            allInputsValid: function () {
                return this.customerValid();
            },

            validate: function () {
                return this.allInputsValid();
            }
        });
    }
);
