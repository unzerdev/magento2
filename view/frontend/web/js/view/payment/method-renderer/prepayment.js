define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/basev2'
    ],
    function (
        $,
        ko,
        $t,
        placeOrderAction,
        globalMessageList,
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/prepayment',
                paymentCode: 'unzer-prepayment',
            }
        });
    }
);
