define(
    [
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote'
    ],
    function (Component, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/open_banking',
                quote: quote,
            },

            initializeForm: function () {
                this.resourceProvider = this.sdk.OpenBanking();
            },
        });
    }
);
