define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Unzer_PAPI/js/model/checkout/threat-metrix',
        'Unzer_PAPI/js/model/checkout/address-updater',
    ],
    function ($, ko, Component, threatMetrix, addressUpdater) {
        'use strict';

        return Component.extend({
            isThreatMetrixNeeded: true,

            defaults: {
                isIbanHolderValid: ko.observable(false),
                template: 'Unzer_PAPI/payment/paylater_direct_debit',
                fieldId: 'unzer-paylater-direct-debit-customer',
                errorFieldId: 'unzer-paylater-direct-debit-customer-error'
            },

            initializeForm: function () {
                let self = this;

                this.resourceProvider = this.sdk.PaylaterDirectDebit();
                this.initializeCustomerForm(
                    this.fieldId,
                    this.errorFieldId
                );

                addressUpdater.registerSubscribers(this);

                self.isIbanHolderValid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    self.isIbanHolderValid("success" in event && event.success);
                });
            },

            _initializeCustomerFormForB2cCustomer: function (fieldId, errorFieldId, customer) {
                threatMetrix.init(customer.threat_metrix_id, this);

                this.resourceProvider.create('paylater-direct-debit', {
                    containerId: fieldId + '-ibanholder',
                    resourceId: ''
                });

                this.customerProvider = this.sdk.Customer();
                this.customerProvider.initFormFields(customer);
                this.customerProvider.create({
                    containerId: fieldId,
                    errorHolderId: errorFieldId,
                    showHeader: false,
                    paymentTypeName: 'paylater-direct-debit'
                });

                this.hideFormFields(fieldId);
            },

            hideFormFields: function (fieldId) {
                this._super(fieldId);

                let field = $('#' + fieldId);

                field.find('.field').filter('.checkbox-billingAddress, .email').hide();
                field.find('.field').filter(
                    '.billing-name, .billing-street, .billing-zip, .billing-city, :has(.billing-country)'
                ).hide();

                field.find('.unzerUI.form>.checkboxLabel').hide();
                field.find('.unzerUI.form>.salutation-unzer-paylater-direct-debit-customer').hide();
            },

            allInputsValid: function () {
                return this.customerValid() && this.isIbanHolderValid();
            },

            validate: function () {
                return this.allInputsValid();
            }
        });
    }
);
