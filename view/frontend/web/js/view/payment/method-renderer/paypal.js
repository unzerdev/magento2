define(
    [
        'Heidelpay_Gateway2/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Heidelpay_Gateway2/payment/paypal'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.Paypal();
                this.resourceProvider.create('email', {
                    containerId: 'paypal-email-element-id'
                });
            },
        });
    }
);