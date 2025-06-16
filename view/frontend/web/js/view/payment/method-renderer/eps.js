define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2'
    ],
    function (
        $,
        ko,
        $t,
        placeOrderAction,
        globalMessageList,
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Unzer_PAPI/payment/eps'
            },

            createSpecificPaymentElement: function () {
                return $('<unzer-eps>');
            },

            getPlaceOrderDeferredObject: function () {
                let deferred = $.Deferred(),
                    self = this;

                Promise.all([
                    customElements.whenDefined('unzer-eps')
                ]).then(() => {
                    const unzerCheckoutElementId = 'unzer-checkout-' + this.getCode();
                    const unzerCheckout = document.getElementById(unzerCheckoutElementId);
                    unzerCheckout.onPaymentSubmit = response => {
                        if (response.submitResponse && response.submitResponse.success)  {
                            this.resourceId = response.submitResponse.data.id;
                            placeOrderAction(self.getData(), self.messageContainer)
                                .done(function () {
                                    deferred.resolve.apply(deferred, arguments);
                                })
                                .fail(function (request) {
                                    globalMessageList.addErrorMessage({
                                        message: request.responseJSON.message
                                    });
                                    deferred.reject(request.responseJSON.message);
                                });
                        } else {
                            globalMessageList.addErrorMessage({
                                message: 'There was an error placing your order. ' + response.submitResponse.message
                            });
                            deferred.reject($t('There was an error placing your order. ' + response.submitResponse.message));
                        }
                    };
                }).catch(error => {
                    globalMessageList.addErrorMessage({
                        message: 'There was an error placing your order. ' + error
                    });
                    deferred.reject($t('There was an error placing your order. ' + error));
                });

                return deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });
            }
        });
    }
);
