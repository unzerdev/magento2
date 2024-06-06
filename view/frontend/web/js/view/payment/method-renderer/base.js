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
        'Magento_Ui/js/lib/view/utils/dom-observer',
        '//static.unzer.com/v1/unzer.js'
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
        globalMessageList,
        domObserver,
        unzer
    ) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: 'unzer/payment/redirect',
            sdk: new unzer(window.checkoutConfig.payment.unzer.publicKey, {
                locale: window.checkoutConfig.payment.unzer.locale
            }),
            sdkConfig: window.checkoutConfig.payment.unzer,
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
                this.customer = customerLoader.getCustomerObservable('default');
                this.customerValid = ko.observable(false);

                if (this.isChecked() === this.item.method) {
                    termsChecked.init(this);
                }

                let overridePublicKey = this._getMethodOverrideApiKey();

                if (overridePublicKey) {
                    this.sdk = new unzer(overridePublicKey, {
                        locale: window.checkoutConfig.payment.unzer.locale
                    });
                    this.customer = customerLoader.getCustomerObservable(this.item.method);
                }

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

            _initializeCustomerForm: function (fieldId, errorFieldId) {
                let self = this;

                $('#' + fieldId).empty();
                $('#' + errorFieldId).empty();

                this.customerValid(false);

                if (self.customerType === 'b2b') {
                    self._initializeCustomerFormForB2bCustomer(fieldId, errorFieldId, self.customer());
                } else {
                    self._initializeCustomerFormForB2cCustomer(fieldId, errorFieldId, self.customer());
                }

                self.customerProvider.addEventListener('validate', function (event) {
                    self.customerValid('success' in event && event.success);
                });

                self.customerProvider.validateAllFields();
            },

            _initializeCustomerFormForB2bCustomer: function (fieldId, errorFieldId, customer) {
                this.customerProvider = this.sdk.B2BCustomer();
                this.customerProvider.initFormFields(customer);
                this.customerProvider.create({
                    containerId: fieldId,
                    errorHolderId: errorFieldId,
                    fields: ['companyInfo'],
                    showHeader: false
                });

                this.hideFormFields(fieldId);
            },

            _initializeCustomerFormForB2cCustomer: function (fieldId, errorFieldId, customer) {
                this.customerProvider = this.sdk.Customer();
                this.customerProvider.initFormFields(customer);
                this.customerProvider.create({
                    containerId: fieldId,
                    errorHolderId: errorFieldId,
                    showHeader: false
                });

                this.hideFormFields(fieldId);
            },

            hideFormFields: function (fieldId) {
                const field = $('#' + fieldId);

                field.find('.field').filter(
                    '.city, .company, :has(.country), .street, .zip, .firstname, .lastname'
                ).hide();
                field.find('.unzerUI.divider-horizontal:eq(0)').hide();
                field.find('.unzerUI.message.downArrow').hide();
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                termsChecked.init(this);

                return retVal;
            },

            initializeForm: function () {
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


                if (this.customerProvider) {
                    promises = [this.resourceProvider.createResource(), this.customerProvider.createCustomer()];
                } else if (this.paymentData) {
                    promises = [this.resourceProvider.createResource(this.paymentData)];
                } else {
                    promises = [this.resourceProvider.createResource()];
                }

                // We need to wait for multiple Promises but the jQuery version used by Magento 2 (jQuery 1.x) does not
                // support non-jQuery Promises in $.when(), so we use the Promise.all method instead.
                // In case a browser has no native Promise support (IE) we fall back to the Promise implementation
                // shipped with the Unzer SDK, by accessing the Promise constructor from one of the existing
                // promises, to avoid having to implement or load our own implementation of Promise.all.
                const Promise = window.Promise || promises[0].constructor;

                Promise.all(promises).then(
                    function (values) {
                        self.resourceId = values[0].id;
                        if (self.customer() && values[1]) {
                            self.customer().id = values[1].id;
                        }

                        placeOrderAction(self.getData(), self.messageContainer)
                            .done(function () {
                                deferred.resolve.apply(deferred, arguments);
                            })
                            .fail(function (request) {
                                deferred.reject(request.responseJSON.message);
                            });
                    },
                    function (error) {
                        let customerMessage = '';

                        try {
                            customerMessage = error.message;
                        } catch (e) {
                        }

                        deferred.reject($t('There was an error placing your order. ' + customerMessage));
                    }
                );

                return deferred.fail(function (error) {
                    globalMessageList.addErrorMessage({
                        message: error
                    });
                });
            }
        });
    }
);
