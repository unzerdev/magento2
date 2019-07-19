define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/view/payment/default',
        '//static.heidelpay.com/v1/heidelpay.js'
    ],
    function ($, ko, $t, placeOrderAction, Component, heidelpay) {
        'use strict';

        return Component.extend({
            defaults: {
                card: null,
                cardFields: {
                    cvc: {valid: null},
                    expiry: {valid: null},
                    number: {valid: null},
                },
                config: window.checkoutConfig.payment.hpg2_creditcard,
                resourceId: null,
                template: 'Heidelpay_Gateway2/payment/creditcard'
            },

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                this.heidelpay = new heidelpay(this.config.publicKey);
                this.initializeForm();
                return this;
            },

            initializeForm: function () {
                var self = this;

                this.card = this.heidelpay.Card();
                this.card.create('number', {
                    containerId: 'card-element-id-number',
                    onlyIframe: false
                });
                this.card.create('expiry', {
                    containerId: 'card-element-id-expiry',
                    onlyIframe: false
                });
                this.card.create('cvc', {
                    containerId: 'card-element-id-cvc',
                    onlyIframe: false
                });

                this.cardFields.cvc.valid = ko.observable(false);
                this.cardFields.expiry.valid = ko.observable(false);
                this.cardFields.number.valid = ko.observable(false);

                this.card.addEventListener('change', function (event) {
                    if ("type" in event) {
                        self.cardFields[event.type].valid("success" in event && event.success);
                    }
                });
            },

            allInputsValid: function () {
                var self = this;

                return ko.computed(function () {
                    return self.cardFields.cvc.valid() &&
                        self.cardFields.expiry.valid() &&
                        self.cardFields.number.valid();
                });
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

                this.card.createResource()
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

            validate: function () {
                return this.allInputsValid()();
            },
        });
    }
);