define(
    [
        'ko',
        'Heidelpay_MGW/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                fields: {
                    cvc: {valid: null},
                    expiry: {valid: null},
                    number: {valid: null},
                },
                template: 'Heidelpay_MGW/payment/cards'
            },

            initializeForm: function () {
                var self = this;

                this.resourceProvider = this.sdk.Card();
                this.resourceProvider.create('number', {
                    containerId: 'card-element-id-number',
                    onlyIframe: false
                });
                this.resourceProvider.create('expiry', {
                    containerId: 'card-element-id-expiry',
                    onlyIframe: false
                });
                this.resourceProvider.create('cvc', {
                    containerId: 'card-element-id-cvc',
                    onlyIframe: false
                });

                this.fields.cvc.valid = ko.observable(false);
                this.fields.expiry.valid = ko.observable(false);
                this.fields.number.valid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    if ("type" in event) {
                        self.fields[event.type].valid("success" in event && event.success);
                    }
                });
            },

            allInputsValid: function () {
                var self = this;

                return ko.computed(function () {
                    return self.fields.cvc.valid() &&
                        self.fields.expiry.valid() &&
                        self.fields.number.valid();
                });
            },

            validate: function () {
                return this.allInputsValid()();
            },
        });
    }
);