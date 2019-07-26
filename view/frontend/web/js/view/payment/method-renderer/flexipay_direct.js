define(
    [
        'Heidelpay_Gateway2/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Heidelpay_Gateway2/payment/flexipay_direct'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.FlexiPayDirect();
            },
        });
    }
);