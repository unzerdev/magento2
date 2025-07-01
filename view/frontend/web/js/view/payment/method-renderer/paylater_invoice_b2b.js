define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/place-order',
        'Unzer_PAPI/js/view/payment/method-renderer/basev2'
    ],
    function (
        $,
        ko,
        $t,
        globalMessageList,
        placeOrderAction,
        Component
    ) {
        'use strict';

        return Component.extend({
            isThreatMetrixNeeded: true,

            defaults: {
                customerType: 'b2b',
                template: 'Unzer_PAPI/payment/paylater_invoice_b2b',
                paymentCode: 'unzer-paylater-invoice',
                customersBirthDayNeeded: true,
            },

            getPlaceOrderDeferredObject: function () {
                let deferred = $.Deferred(),
                    self = this;

                Promise.all([
                    customElements.whenDefined(this.paymentCode)
                ]).then(() => {
                    const unzerCheckoutElementId = 'unzer-checkout-' + this.getCode();
                    const unzerCheckout = document.getElementById(unzerCheckoutElementId);

                    unzerCheckout.onPaymentSubmit = response => {
                        if (response.submitResponse && response.submitResponse.success) {
                            this.resourceId = response.submitResponse.data.id;
                            const dropdown = document.getElementById('unzer-dropdown-field');
                            this.customerType = dropdown.value;

                            if (this.customersBirthDayNeeded) {
                                this.customersBirthDay = document.querySelector(this.paymentCode).shadowRoot?.querySelector('uds-input-date[name="birthDate"]').value;
                            }

                            placeOrderAction(self.getData(), self.messageContainer)
                                .done(function () {
                                    deferred.resolve.apply(deferred, arguments);
                                })
                                .fail(function (request) {
                                    if (request.responseJSON && request.responseJSON.message) {
                                        globalMessageList.addErrorMessage({
                                            message: request.responseJSON.message
                                        });
                                        deferred.reject(request.responseJSON.message);
                                    } else {
                                        globalMessageList.addErrorMessage({
                                            message: 'An unknown error occurred. Please try again.'
                                        });
                                        deferred.reject('An unknown error occurred.');
                                    }
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
