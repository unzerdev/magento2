define(
    [
        'jquery',
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/direct_debit'
    ],
    function ($, ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                ibanValid: false,
                template: 'Unzer_PAPI/payment/direct_debit_secured'
            },

            initializeForm: function () {
                var self = this;

                this.initializeCustomerForm(
                    'sepa-direct-debit-secured-customer',
                    'sepa-direct-debit-secured-customer-error'
                );

                this.resourceProvider = this.sdk.SepaDirectDebit();
                this.resourceProvider.create('sepa-direct-debit-secured', {
                    containerId: 'sepa-direct-debit-secured-iban-field'
                });

                this.ibanValid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    self.ibanValid("success" in event && event.success);
                });

                var sepaMandateElement = document.querySelector('.sepa-direct-debit-secured-mandate'),
                    sepaMandateTexts = [
                        $.mage.__('By signing this mandate form, you authorise %1 to send instructions to '
                            + 'your bank to debit your account and your bank to debit your account in accordance with the '
                            + 'instructions from %1.'),
                        $.mage.__('Note: As part of your rights, you are entitled to a refund from your '
                            + 'bank under the terms and conditions of your agreement with your bank. A refund must be claimed '
                            + 'within 8 weeks starting from the date on which your account was debited. Your rights regarding '
                            + 'this SEPA mandate are explained in a statement that you can obtain from your bank.'),
                        $.mage.__('In case of refusal or rejection of direct debit payment I instruct my '
                            + 'bank irrevocably to inform %1 or any third party upon request about my name, address and date '
                            + 'of birth.'),
                    ];

                sepaMandateTexts.forEach(function(text) {
                    var p = document.createElement("p");
                    p.innerText = text.replace(/%1/g, window.checkoutConfig.payment.unzer_direct_debit.merchantName);
                    sepaMandateElement.appendChild(p);
                });
            },

            allInputsValid: function () {
                var self = this;

                return ko.computed(function () {
                    return self.customerValid() && self.ibanValid();
                });
            }
        });
    }
);