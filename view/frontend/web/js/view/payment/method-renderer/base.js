define(
    [
        'jquery',
        'ko',
        'mage/storage',
        'mage/translate',
        'mage/url',
        'Heidelpay_MGW/js/model/checkout/customer-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
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
        customerLoader,
        placeOrderAction,
        fullScreenLoader,
        Component,
        globalMessageList,
        heidelpay,
        Promise
    ) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: 'hpmgw/payment/redirect',
            sdk: new heidelpay(window.checkoutConfig.payment.hpmgw.publicKey),
            sdkConfig: window.checkoutConfig.payment.hpmgw,

            defaults: {
                config: null,
                customerId: null,
                customerProvider: null,
                customerType: 'b2c',
                customerValid: null,
                resourceId: null,
                resourceProvider: null,
                template: null
            },

            initialize: function () {
                var self = this;
                this._super();

                customerLoader.loadFromQuote().done(function (customer) {
                    self.customerId = customer.id;
                })
            },

            initializeCustomerForm: function (fieldId, errorFieldId) {
                var self = this;
                this.customerValid = ko.observable(false);

                customerLoader.loadFromQuote().done(function (customer) {
                    if (self.customerType === 'b2b') {
                        self._initializeCustomerFormForB2bCustomer(fieldId, errorFieldId, customer);
                    } else {
                        self._initializeCustomerFormForB2cCustomer(fieldId, errorFieldId, customer);
                    }

                    self.customerProvider.addEventListener('validate', function (event) {
                        self.customerValid("success" in event && event.success);
                    });
                });
            },

            _initializeCustomerFormForB2bCustomer: function (fieldId, errorFieldId, customer) {
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

                // The SDK currently always shows these fields, although we don't specify them in the options above.
                // Hide them manually since users are not allowed to change them anyways.
                var field = $('#' + fieldId);
                field.find('.field').filter('.city, .company, :has(.country), .street, .zip').hide();
                field.find('.heidelpayUI.divider-horizontal:eq(0)').hide();
            },

            _initializeCustomerFormForB2cCustomer: function (fieldId, errorFieldId, customer) {
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