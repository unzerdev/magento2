define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/view/payment/default',
        '//static.heidelpay.com/v1/heidelpay.js',
        '//cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js'
    ],
    function ($, ko, $t, url, placeOrderAction, fullScreenLoader, Component, heidelpay, Promise) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: 'hpg2/payment/redirect',
            sdk: new heidelpay(window.checkoutConfig.payment.hpg2.publicKey),
            sdkConfig: window.checkoutConfig.payment.hpg2,

            defaults: {
                config: null,
                customerId: null,
                customerProvider: null,
                resourceId: null,
                resourceProvider: null,
                template: null
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
                        'customer_id': this.customerId,
                        'resource_id': this.resourceId
                    }
                };
            },

            getPlaceOrderDeferredObject: function () {
                var deferred = $.Deferred(),
                    promises = [],
                    self = this;

                promises.push(this.resourceProvider.createResource());

                if (this.customerProvider) {
                    promises.push(this.customerProvider.createCustomer());
                }

                Promise.all(promises)
                    .then(function (values) {
                        self.resourceId = values[0].id;
                        if (values.length > 1) {
                            self.customerId = values[1].id;
                        }

                        placeOrderAction(self.getData(), self.messageContainer)
                            .done(function () {
                                deferred.resolve.apply(deferred, arguments);
                            })
                            .fail(function () {
                                deferred.reject.apply(deferred, arguments);
                            });
                    })
                    .catch(function (error) {
                        deferred.reject($t("There was an error placing your order"));
                    });

                return $.when(deferred);
            },
        });
    }
);