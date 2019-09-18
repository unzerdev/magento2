define(
    [
        'ko',
        'mage/translate',
        'Heidelpay_MGW/js/view/payment/method-renderer/base'
    ],
    function (ko, $t, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                field: {valid: false},
                template: 'Heidelpay_MGW/payment/direct_debit'
            },

            initializeForm: function () {
                var self = this;

                this.resourceProvider = this.sdk.SepaDirectDebit();
                this.resourceProvider.create('sepa-direct-debit', {
                    containerId: 'sepa-direct-debit-iban-field'
                });

                this.field.valid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    self.field.valid("success" in event && event.success);
                });
            },

            allInputsValid: function () {
                return this.field.valid;
            },

            validate: function () {
                return this.allInputsValid()();
            },

            translate: function (text) {
                return $t(text).replace(/%1/g, window.checkoutConfig.payment.hpmgw_direct_debit.merchantName);
            }
        });
    }
);