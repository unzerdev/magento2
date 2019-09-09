define(
    [
        'jquery',
        'ko',
        'mage/storage',
        'mage/translate',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        '//static.heidelpay.com/v1/heidelpay.js',
        '//cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js'
    ],
    function (
        $,
        ko,
        storage,
        $t,
        url,
        placeOrderAction,
        errorProcessor,
        fullScreenLoader,
        quote,
        urlBuilder,
        Component,
        globalMessageList,
        heidelpay,
        Promise
    ) {
        'use strict';

        return Component.extend({
            customerIdPromise: null,
            redirectAfterPlaceOrder: false,
            redirectUrl: 'hpmgw/payment/redirect',
            sdk: new heidelpay(window.checkoutConfig.payment.hpmgw.publicKey),
            sdkConfig: window.checkoutConfig.payment.hpmgw,

            defaults: {
                config: null,
                customerId: null,
                customerProvider: null,
                customerValid: null,
                resourceId: null,
                resourceProvider: null,
                template: null
            },

            fetchCustomerIdFromQuote: function() {
                if (this.customerIdPromise === null) {
                    if (this.customerId !== null) {
                        this.customerIdPromise = $.Deferred().resolve(this.customerId);
                    } else {
                        this.customerIdPromise = storage.post(
                            urlBuilder.createUrl('/hpmgw/get-external-customer-id', {}),
                            JSON.stringify({
                                guestEmail: quote.guestEmail,
                            })
                        );

                        fullScreenLoader.startLoader();
                        this.customerIdPromise.always(fullScreenLoader.stopLoader);
                        this.customerIdPromise.fail(errorProcessor.process);
                    }
                }

                return this.customerIdPromise;
            },

            initializeCustomerForm: function (fieldId, errorFieldId) {
                var self = this;

                this.customerValid = ko.observable(false);

                this.fetchCustomerIdFromQuote().done(function(customerId) {
                    if (customerId !== null) {
                        self.initializeCustomerFormForCustomerId(fieldId, errorFieldId, customerId);
                    }
                });
            },

            initializeCustomerFormForCustomerId: function (fieldId, errorFieldId, customerId) {
                var self = this;

                this.customerProvider = this.sdk.Customer();
                this.customerProvider.update(
                    customerId,
                    {
                        infoBoxText: $t('Your date of birth'),
                        containerId: fieldId,
                        errorHolderId: errorFieldId,
                        fields: ['birthdate'],
                        showHeader: false
                    }
                );

                this.customerProvider.addEventListener('validate', function (event) {
                    self.customerValid("success" in event && event.success);
                });
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
                    promises.push(this.customerProvider.updateCustomer());
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
                            .fail(function (request) {
                                deferred.reject(request.responseJSON.message);
                            });
                    })
                    .catch(function (error) {
                        deferred.reject($t("There was an error placing your order"));
                    });

                deferred.fail(function(error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });

                return $.when(deferred);
            },
        });
    }
);