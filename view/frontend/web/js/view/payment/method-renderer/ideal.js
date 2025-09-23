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
                template: 'Unzer_PAPI/payment/ideal',
                paymentCode: 'unzer-ideal'
            },
        });
    }
);
