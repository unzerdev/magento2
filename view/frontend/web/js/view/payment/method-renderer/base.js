define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/view/payment/default',
        '//static.heidelpay.com/v1/heidelpay.js'
    ],
    function ($, ko, $t, url, placeOrderAction, fullScreenLoader, Component, heidelpay) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: 'hpg2/payment/redirect',
            sdk: new heidelpay(window.checkoutConfig.payment.hpg2.publicKey),
            sdkConfig: window.checkoutConfig.payment.hpg2,

            defaults: {
                config: null,
                resourceId: null,
                resourceProvider: null,
                template: null
            },

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                this.initializeForm();
                return this;
            },

            initializeForm: function () {
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                window.location.replace(url.build(this.redirectUrl));
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        'resource_id': this.resourceId
                    }
                };
            },

            getPlaceOrderDeferredObject: function () {
                var self = this;
                var d = $.Deferred();

                this.resourceProvider.createResource()
                    .then(function (data) {
                        self.resourceId = data.id;

                        placeOrderAction(self.getData(), self.messageContainer)
                            .done(function () {
                                d.resolve.apply(d, arguments);
                            })
                            .fail(function () {
                                d.reject.apply(d, arguments);
                            });
                    })
                    .catch(function (error) {
                        d.reject($t("There was an error placing your order"));
                    });

                return $.when(d);
            },
        });
    }
);