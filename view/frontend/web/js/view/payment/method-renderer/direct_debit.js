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
                template: 'Unzer_PAPI/payment/direct_debit',
                paymentCode: 'unzer-sepa-direct-debit',
            }
        });
    }
);
