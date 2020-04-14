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
        heidelpay
    ) {
        'use strict';

        // Magento does not keep track of the current step anywhere so we must manually check the hash
        var currentCheckoutStep = ko.observable(document.location.hash.replace('/^#/', ''));

        $(window).on('hashchange', function() {
            currentCheckoutStep(document.location.hash.replace(/^#/, ''));
        });

        return Component.extend({
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

            initialize: function() {
                this._super();
                this.customer = customerLoader.getCustomerObservable();
            },

            initializeCustomerForm: function (fieldId, errorFieldId) {
                this.customer.subscribe(this._initializeCustomerForm.bind(this, fieldId, errorFieldId));
            },

            _initializeCustomerForm: function(fieldId, errorFieldId) {
                var self = this;

                $('#' + fieldId).empty();
                $('#' + errorFieldId).empty();

                this.customerValid = ko.observable(false);

                if (self.customerType === 'b2b') {
                    self._initializeCustomerFormForB2bCustomer(fieldId, errorFieldId, self.customer());
                } else {
                    self._initializeCustomerFormForB2cCustomer(fieldId, errorFieldId, self.customer());
                }

                self.customerProvider.addEventListener('validate', function (event) {
                    self.customerValid("success" in event && event.success);
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
                        'customer_id': this.customer !== null ? this.customer().id : null,
                        'resource_id': this.resourceId
                    }
                };
            },

            getPlaceOrderDeferredObject: function () {
                var deferred = $.Deferred(),
                    promises,
                    self = this;

                if (this.customerProvider) {
                    promises = [this.resourceProvider.createResource(), this.customerProvider.updateCustomer()];
                } else {
                    promises = [this.resourceProvider.createResource()];
                }

                // We need to wait for multiple Promises but the jQuery version used by Magento 2 (jQuery 1.x) does not
                // support non-jQuery Promises in $.when(), so we use the Promise.all method instead.
                // In case a browser has no native Promise support (IE) we fallback to the Promise implementation
                // shipped with the heidelpay SDK, by accessing the Promise constructor from one of the existing
                // promises, to avoid having to implement or load our own implementation of Promise.all.
                var Promise = window.Promise || promises[0].constructor;

                Promise.all(promises).then(
                    function (values) {
                        self.resourceId = values[0].id;

                        placeOrderAction(self.getData(), self.messageContainer)
                            .done(function () {
                                deferred.resolve.apply(deferred, arguments);
                            })
                            .fail(function (request) {
                                deferred.reject(request.responseJSON.message);
                            });
                    },
                    function () {
                        deferred.reject($t("There was an error placing your order"));
                    }
                );

                return deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });
            },
        });
    }
);