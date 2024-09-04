define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/eps'
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.EPS();
            }
        });
    }
);
