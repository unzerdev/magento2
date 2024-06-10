define(
    [
        'ko',
        'jquery',
        'Magento_CheckoutAgreements/js/model/agreement-validator',
        'Magento_Ui/js/lib/view/utils/dom-observer',
    ],
    function (ko, $, agreementValidator, domObserver) {
        'use strict';

        let agreementsInputPath = '.payment-method._active div.checkout-agreements input',
            allTermsChecked = ko.observable(true),
            termsCheckedSubscription = null;

        function registerOnChangeEvent()
        {
            domObserver.get(agreementsInputPath, (elem) => {
                allTermsChecked(agreementValidator.validate(true));
                $(elem).off('change');
                $(elem).on('change', () => {
                    allTermsChecked(agreementValidator.validate(true));
                });
            });
        }

        function initSubscriber(paymentMethod)
        {
            paymentMethod.allTermsChecked(allTermsChecked());

            termsCheckedSubscription ? termsCheckedSubscription.dispose() : false;
            termsCheckedSubscription = allTermsChecked.subscribe(
                (allTermsChecked) => {
                    paymentMethod.allTermsChecked(allTermsChecked);
                }
            );
        }

        return {
            init: function (currentPaymentMethod) {

                registerOnChangeEvent();

                initSubscriber(currentPaymentMethod);
            },
            allTermsChecked
        };
    }
)
