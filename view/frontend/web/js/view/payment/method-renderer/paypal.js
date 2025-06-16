define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/basev2'
    ],
    function (
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                isActivePaymentTokenEnabler: false,
                template: 'Unzer_PAPI/payment/paypal',
                paymentCode: 'unzer-paypal',
            }
        });
    }
);
