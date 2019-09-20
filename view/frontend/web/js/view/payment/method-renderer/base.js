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
            customerPromise: null,
            redirectAfterPlaceOrder: false,
            redirectUrl: 'hpmgw/payment/redirect',
            sdk: new heidelpay(window.checkoutConfig.payment.hpmgw.publicKey),
            sdkConfig: window.checkoutConfig.payment.hpmgw,

            defaults: {
                config: null,
                customer: null,
                customerProvider: null,
                customerType: 'b2c',
                customerValid: null,
                resourceId: null,
                resourceProvider: null,
                template: null
            },

            fetchCustomerFromQuote: function () {
                if (this.customerPromise === null) {
                    this.customerPromise = storage.post(
                        urlBuilder.createUrl('/hpmgw/get-external-customer', {}),
                        JSON.stringify({
                            guestEmail: quote.guestEmail,
                        })
                    );

                    fullScreenLoader.startLoader();
                    this.customerPromise.always(fullScreenLoader.stopLoader);
                    this.customerPromise.fail(errorProcessor.process);
                }

                return this.customerPromise;
            },

            initializeCustomerForm: function (fieldId, errorFieldId) {
                var self = this;

                this.customerValid = ko.observable(false);

                this.fetchCustomerFromQuote().done(function (customer) {
                    if (customer !== null) {
                        // Magento converts camel case to snake case in API responses so we must manually map
                        // the properties to be consistent with the casing for the heidelpay SDK.

                        customer.billingAddress = customer.billing_address;
                        delete customer.billing_address;

                        customer.shippingAddress = customer.shipping_address;
                        delete customer.shipping_address;

                        customer.companyInfo = customer.company_info;
                        delete customer.company_info;

                        self.initializeCustomerFormForid(fieldId, errorFieldId, customer);
                    }
                });
            },

            initializeCustomerFormForid: function (fieldId, errorFieldId, customer) {
                var self = this;

                if (this.customerType === 'b2b') {
                    this.customerProvider = this.sdk.B2BCustomer();
                    this.customerProvider.initFormFields(customer);
                    this.customerProvider.update(
                        customer.id,
                        {
                            containerId: fieldId,
                            errorHolderId: errorFieldId,
                            fields: ['companyInfo'],
                            showHeader: false
                        }
                    );
                } else {
                    this.customerProvider = this.sdk.Customer();
                    this.customerProvider.initFormFields(customer);
                    this.customerProvider.update(
                        customer.id,
                        {
                            infoBoxText: $t('Your date of birth'),
                            containerId: fieldId,
                            errorHolderId: errorFieldId,
                            fields: ['birthdate'],
                            showHeader: false
                        }
                    );
                }

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
                        'customer_id': this.customer !== null ? this.customer.id : null,
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
                            self.customer = self.customer || {};
                            self.customer.id = values[1].id;
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

                deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });

                return $.when(deferred);
            },
        });
    }
);