define(
    [
        'Heidelpay_Gateway2/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            redirectUrl: 'checkout/onepage/success',

            defaults: {
                template: 'Heidelpay_Gateway2/payment/invoice'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.Invoice();
            },
        });
    }
);