define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/basev2',
        'Magento_Checkout/js/model/quote'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            isThreatMetrixNeeded: true,
            customersBirthDayNeeded: true,
            defaults: {
                template: 'Unzer_PAPI/payment/paylater_installment',
                paymentCode: 'unzer-paylater-installment'
            },
        });
    }
);
