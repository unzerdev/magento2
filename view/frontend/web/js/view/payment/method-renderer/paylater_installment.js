define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Unzer_PAPI/js/model/checkout/threat-metrix',
        'Unzer_PAPI/js/model/checkout/address-updater',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, ko, Component, threatMetrix, addressUpdater, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/paylater_installment',
                quote: quote,
                paymentDataValid: ko.observable(false),
                fieldId: 'unzer-paylater-installment-customer',
                errorFieldId: 'unzer-paylater-installment-customer-error'
            },

            initializeForm: function () {

                let self = this;

                this.resourceProvider = this.sdk.PaylaterInstallment();
                this.initializeCustomerForm(this.fieldId, this.errorFieldId);

                addressUpdater.registerSubscribers(this);

                this.quote.totals.subscribe(this._updatePlans.bind(this));

                this.resourceProvider.addEventListener('paylaterInstallmentEvent', function (event) {
                    self.paymentDataValid(false);
                    if (event.action === 'validate') {
                        self.paymentDataValid('success' in event && event.success);
                    }
                });
            },

            _initializeCustomerFormForB2cCustomer: function (fieldId, errorFieldId, customer) {
                threatMetrix.init(customer.threat_metrix_id);

                this.resourceProvider.create({
                    country: this.quote.billingAddress().countryId,
                    containerId: fieldId + '-plan',
                    customerType: 'B2C',
                    amount: (this.quote.totals() ? this.quote.totals() : this.quote)['grand_total'],
                    currency: (this.quote.totals() ? this.quote.totals() : this.quote)['quote_currency_code'],
                    errorHolderId: errorFieldId
                });

                this.customerProvider = this.sdk.Customer();
                this.customerProvider.initFormFields(customer);
                this.customerProvider.create({
                    containerId: fieldId,
                    errorHolderId: errorFieldId,
                    showHeader: false,
                    paymentTypeName: 'paylater-installment'
                });

                this.hideFormFields(fieldId);
            },

            _updatePlans: function () {
                if (document.getElementById(this.fieldId + '-plan') && document.getElementById(this.fieldId + '-plan').hasChildNodes()) {
                    let params = {
                        country: this.quote.billingAddress()['countryId'],
                        customerType: 'B2C',
                        amount: this.quote.totals()['grand_total'],
                        currency: this.quote.totals()['quote_currency_code']
                    };

                    this.resourceProvider.fetchPlans(params);
                }
            },

            hideFormFields: function (fieldId) {
                this._super(fieldId);

                let field = $('#' + fieldId);

                field.find('.field').filter('.checkbox-billingAddress, .email').hide();
                field.find('.field').filter(
                    '.billing-name, .billing-street, .billing-zip, .billing-city, :has(.billing-country)'
                ).hide();

                field.find('.unzerUI.form>.checkboxLabel').hide();
                field.find('.unzerUI.form>.salutation-unzer-paylater-installment-customer').hide();
            },

            allInputsValid: function () {
                return this.customerValid() && this.paymentDataValid();
            },

            validate: function () {
                return this.allInputsValid();
            }
        });
    }
);
