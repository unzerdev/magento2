define(
    [
        'ko',
        'Heidelpay_Gateway2/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                field: {valid: false},
                template: 'Heidelpay_Gateway2/payment/ideal'
            },

            initializeForm: function () {
                var self = this;

                this.resourceProvider = this.sdk.Ideal();
                this.resourceProvider.create('ideal', {
                    containerId: 'ideal-field'
                });

                this.field.valid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    self.field.valid(!!event.value);
                });
            },

            allInputsValid: function () {
                return this.field.valid;
            },

            validate: function () {
                return this.allInputsValid()();
            },
        });
    }
);