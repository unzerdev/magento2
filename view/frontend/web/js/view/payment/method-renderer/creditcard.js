define(
    [
        'Magento_Checkout/js/view/payment/default',
        '//static.heidelpay.com/v1/heidelpay.js'
    ],
    function (Component, heidelpay) {
        'use strict';

        return Component.extend({
            defaults: {
                config: window.checkoutConfig.payment.hpg2_creditcard,
                template: 'Heidelpay_Gateway2/payment/creditcard'
            },
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();

                this.heidelpay = new heidelpay(this.config.publicKey);

                return this;
            },
        });
    }
);