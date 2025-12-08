define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/basev2'
    ],
    function (
        Component
    ) {
        'use strict';

        return Component.extend({
            isThreatMetrixNeeded: true,
            defaults: {
                template: 'Unzer_PAPI/payment/paylater_direct_debit',
                paymentCode: 'unzer-paylater-direct-debit',
                customerNeeded: true,
            }
        });
    }
);
