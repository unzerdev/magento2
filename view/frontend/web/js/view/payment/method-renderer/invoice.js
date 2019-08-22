define(
    [
        'Heidelpay_MGW/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            redirectUrl: 'checkout/onepage/success',

            defaults: {
                template: 'Heidelpay_MGW/payment/invoice'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.Invoice();
            },
        });
    }
);