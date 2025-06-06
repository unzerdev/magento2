define(
    [
        'jquery',
        'ko',
        'mage/storage',
        'mage/translate',
        'mage/url',
        'Unzer_PAPI/js/model/checkout/customer-loader',
        'Unzer_PAPI/js/model/checkout/terms-checked',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'Magento_Ui/js/lib/view/utils/dom-observer'
    ],
    function (
        $,
        ko,
        storage,
        $t,
        url,
        customerLoader,
        termsChecked,
        placeOrderAction,
        fullScreenLoader,
        Component,
        globalMessageList
    ) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: 'unzer/payment/redirect',
            allTermsChecked: termsChecked.allTermsChecked,
            isThreatMetrixNeeded: false,

            defaults: {
                config: null,
                customer: null,
                customerProvider: null,
                customerSubscription: null,
                customerType: 'b2c',
                customerValid: null,
                resourceId: null,
                resourceProvider: null,
                template: null
            },

            initialize: function () {
                this._super();
                import('//static-v2.unzer.com/v2/ui-components/index.js')
                    .then(module => {
                    })
                    .catch(error => {
                        console.error('Failed to load Unzer scripts: ' + error)
                    });

                return this;
            },

            _getMethodConfig: function (configFieldName) {
                if (!window.checkoutConfig.payment[this.item.method]) {
                    return false;
                }
                if (!window.checkoutConfig.payment[this.item.method].hasOwnProperty(configFieldName)) {
                    return false;
                }
                return window.checkoutConfig.payment[this.item.method][configFieldName];
            },

            _getMethodOverrideApiKey: function () {
                return this._getMethodConfig('publicKey');
            },

            initializeCustomerForm: function (fieldId, errorFieldId) {
                let self = this;

                if (this.customer() !== null) {
                    self._initializeCustomerForm(fieldId, errorFieldId);
                }

                // if the customer changes, e.g. the billing address is changed,
                // we need to reinitialize the Unzer Customer form fields
                self.customerSubscription = this.customer.subscribe(function (customer) {
                    if (customer === null) {
                        return;
                    }

                    self._initializeCustomerForm(fieldId, errorFieldId);
                });
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                termsChecked.init(this);

                return retVal;
            },

            initializeForm: function () {
                const unzerPaymentElement = document.querySelector('unzer-payment');

                if (unzerPaymentElement) {
                    // Inject or update the publicKey and locale attributes
                    unzerPaymentElement.setAttribute('publicKey', window.checkoutConfig.payment.unzer.publicKey);
                    unzerPaymentElement.setAttribute('locale', window.checkoutConfig.payment.unzer.locale);
                } else {
                    console.error('Unzer payment element not found in the DOM.');
                }

                const unzerPayButton = document.getElementById('unzer-pay-button');

                if (unzerPayButton) {
                    unzerPayButton.classList.add('disabled');
                }

            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                window.location.replace(url.build(this.redirectUrl));
            },

            getData: function () {
                let data = {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        'customer_id': this.customer !== null && this.customer() !== null ? this.customer().id : null,
                        'resource_id': this.resourceId
                    }
                };

                if (this.isThreatMetrixNeeded) {
                    data['additional_data']['threat_metrix_id'] = this.customer !== null && this.customer() !== null
                        ? this.customer().threat_metrix_id : null
                }

                return data;
            },


            getPlaceOrderDeferredObject: function () {
                let deferred = $.Deferred(),
                    promises,
                    self = this;

                Promise.all([customElements.whenDefined('unzer-payment'), customElements.whenDefined('unzer-card')]).then(() => {
                    const unzerPaymentElement = document.getElementById('unzer-payment');
                    const unzerCheckout = document.getElementById('unzer-checkout');
                    unzerCheckout.onPaymentSubmit = response => {
                        if (response.submitResponse && response.submitResponse.status === 'SUCCESS') {
                            this.resourceId = response.submitResponse.data.id;
                            placeOrderAction(self.getData(), self.messageContainer)
                                .done(function () {
                                    deferred.resolve.apply(deferred, arguments);
                                })
                                .fail(function (request) {
                                    deferred.reject(request.responseJSON.message);
                                });
                        } else {
                            deferred.reject($t('There was an error placing your order. ' + response.submitResponse.message));
                        }
                    };
                }).catch(error => {
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
