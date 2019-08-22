define(
    [
        'ko',
        'mage/translate',
        'Heidelpay_MGW/js/view/payment/method-renderer/direct_debit'
    ],
    function (ko, $t, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                ibanValid: false,
                template: 'Heidelpay_MGW/payment/direct_debit_guaranteed'
            },

            initializeForm: function () {
                var self = this;

                this.initializeCustomerForm(
                    'sepa-direct-debit-guaranteed-customer',
                    'sepa-direct-debit-guaranteed-customer-error'
                );

                this.resourceProvider = this.sdk.SepaDirectDebitGuaranteed();
                this.resourceProvider.create('sepa-direct-debit', {
                    containerId: 'sepa-direct-debit-guaranteed-iban-field'
                });

                this.ibanValid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    self.ibanValid("success" in event && event.success);
                });
            },

            allInputsValid: function () {
                var self = this;

                return ko.computed(function () {
                    return self.customerValid() && self.ibanValid();
                });
            },

            translate: function (text) {
                return $t(text).replace(/%1/g, window.checkoutConfig.payment.hpmgw_direct_debit_guaranteed.merchantName);
            }
        });
    }
);