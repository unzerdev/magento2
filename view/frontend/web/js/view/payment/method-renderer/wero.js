define([
    'jquery',
    'ko',
    'mage/translate',
    'Magento_Checkout/js/action/place-order',
    'Magento_Ui/js/model/messageList',
    'Unzer_PAPI/js/view/payment/method-renderer/basev2'
], function ($, ko, $t, placeOrderAction, globalMessageList, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            isActivePaymentTokenEnabler: false,
            template: 'Unzer_PAPI/payment/wero'
        },

        createSpecificPaymentElement: function () {
            return $('<unzer-wero>');
        },

        selectPaymentMethod: function () {
            var ret = this._super();
            this.waitForSetBasketData();
            return ret;
        },

        getPlaceOrderDeferredObject: function () {
            var deferred = $.Deferred(), self = this;

            Promise.all([
                customElements.whenDefined('unzer-wero')
            ]).then(function () {
                var unzerCheckout = document.getElementById('unzer-checkout-' + self.getCode());

                unzerCheckout.onPaymentSubmit = function (response) {
                    if (response.submitResponse && response.submitResponse.success)  {
                        self.resourceId = response.submitResponse.data.id;
                        placeOrderAction(self.getData(), self.messageContainer)
                            .done(function () { deferred.resolve.apply(deferred, arguments); })
                            .fail(function (request) {
                                globalMessageList.addErrorMessage({ message: request.responseJSON.message });
                                deferred.reject(request.responseJSON.message);
                            });
                    } else {
                        globalMessageList.addErrorMessage({
                            message: 'There was an error placing your order. ' + response.submitResponse.message
                        });
                        deferred.reject($t('There was an error placing your order. ' + response.submitResponse.message));
                    }
                };
            }).catch(function (error) {
                globalMessageList.addErrorMessage({ message: 'There was an error placing your order. ' + error });
                deferred.reject($t('There was an error placing your order. ' + error));
            });

            return deferred.fail(function (error) {
                globalMessageList.addErrorMessage({ message: error });
            });
        }
    });
});
