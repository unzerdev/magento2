define(
    [
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                field: {valid: false},
                template: 'Unzer_PAPI/payment/ideal'
            },

            initializeForm: function () {
                const self = this;

                this.resourceProvider = this.sdk.Ideal();
                this.resourceProvider.create('ideal', {
                    containerId: 'unzer-ideal-field'
                });

                this.field.valid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    self.field.valid(!!event.value);
                });
            },

            allInputsValid: function () {
                return this.field.valid();
            },

            validate: function () {
                return this.allInputsValid();
            },
        });
    }
);
