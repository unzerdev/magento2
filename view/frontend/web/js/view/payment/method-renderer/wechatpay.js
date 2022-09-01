define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/wechatpay'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.Wechatpay();
            },
        });
    }
);
