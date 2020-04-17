define(
    [
        'Heidelpay_MGW/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Heidelpay_MGW/payment/flexipay_direct'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.FlexiPayDirect();
            },
        });
    }
);