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
        'Magento_Checkout/js/model/quote'
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
        quote
    ) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: 'unzer/payment/redirect',
            allTermsChecked: termsChecked.allTermsChecked,
            isThreatMetrixNeeded: false,
            buttonNeeded: true,
            paymentCode: null,
            customerNeeded: false,
            customerType: null,
            threatMetrixId: null,
            lastGrandTotal: null,

            defaults: {
                config: null,
                customer: null,
                customerProvider: null,
                customerSubscription: null,
                customerType: 'b2c',
                customerValid: null,
                resourceId: null,
                resourceProvider: null,
                template: null,
                customersBirthDay: null,
                threatMetrixId: null
            },

            initialize: function () {
                this._super();
                import('//static-v2.unzer.com/v2/ui-components/index.js')
                    .then(module => {
                    })
                    .catch(error => {
                        console.error('Failed to load Unzer scripts: ' + error)
                    });

                let self = this;
                // Subscribe to the selected payment method observable
                quote.paymentMethod.subscribe(function (newPaymentMethod) {
                    if (!newPaymentMethod || newPaymentMethod.method !== self.getCode()) {
                        self.onUnselected();
                    }
                });

                this.lastGrandTotal = quote.totals() && typeof quote.totals()['base_grand_total'] !== 'undefined'
                    ? quote.totals()['base_grand_total']
                    : null;

                quote.totals.subscribe((newTotals) => {
                    if (quote.paymentMethod() && quote.paymentMethod().method === this.getCode()) {
                        let currentTotal = newTotals['base_grand_total'];

                        if (this.lastGrandTotal !== currentTotal) {
                            this.lastGrandTotal = currentTotal;

                            self.onUnselected();
                            this.selectPaymentMethod();
                        }
                    }
                });

                return this;
            },

            selectPaymentMethod: function () {
                let retVal = this._super();

                termsChecked.init(this);

                const componentContainer = $('#unzer-component-' + this.getCode());
                componentContainer.empty();
                const unzerPayment = this.createUnzerPaymentElement();
                const specificPaymentElement = this.createSpecificPaymentElement();
                unzerPayment.append(specificPaymentElement);
                const unzerCheckout = this.createUnzerCheckoutPaymentElement();

                componentContainer.append(unzerPayment);
                componentContainer.append(unzerCheckout);

                if (this.customerNeeded) {
                    this.waitForSetBasketData();
                }

                return retVal;
            },

            createUnzerPaymentElement: function () {
                const unzerPaymentElementId = 'unzer-payment-' + this.getCode();

                return $('<unzer-payment>')
                    .attr('id', unzerPaymentElementId)
                    .attr('publicKey', this.getPublicKey())
                    .attr('locale', window.checkoutConfig.payment.unzer.locale);
            },

            createSpecificPaymentElement: function () {
                if (!this.paymentCode) {
                    console.error("Payment code is null or undefined");

                    return null;
                }

                return $(`<${this.paymentCode}>`);
            },

            createUnzerCheckoutPaymentElement: function () {
                const unzerCheckoutId = 'unzer-checkout-' + this.getCode();
                const unzerCheckout = $('<unzer-checkout>')
                    .attr('id', unzerCheckoutId);

                if (this.buttonNeeded) {
                    const unzerPayButtonId = 'unzer-pay-button-' + this.getCode();
                    const payButton = $('<button>')
                        .attr('id', unzerPayButtonId)
                        .addClass('button action primary checkout')
                        .addClass('unzerPlaceOrderPadding')
                        .attr('type', 'submit')
                        .attr('data-bind', `
                        click: placeOrder,
                        attr: {title: 'Place Order'},
                        css: {disabled: !isPlaceOrderActionAllowed() || !allTermsChecked()}
                    `);

                    const buttonTextSpan = $('<span>').html($t('Place Order'));
                    payButton.append(buttonTextSpan);
                    unzerCheckout.append(payButton);
                    ko.applyBindings(this, payButton[0]);
                }

                return unzerCheckout;
            },

            getPublicKey: function () {
                let overridePublicKey = this._getMethodOverrideApiKey();

                if (overridePublicKey) {
                    return overridePublicKey;
                }

                return window.checkoutConfig.payment.unzer.publicKey;
            },

            /**
             * Triggered when this payment method is unselected
             */
            onUnselected: function () {
                const componentContainer = $('#unzer-component-' + this.getCode());

                // Clear the container div for any previous components
                componentContainer.empty();
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

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                window.location.replace(url.build(this.redirectUrl));
            },

            getData: function () {
                let data = {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        'customer_id': this.customer,
                        'resource_id': this.resourceId,
                        'birthDate': this.customersBirthDay,
                        'customer_type': this.customerType
                    }
                };

                if (this.isThreatMetrixNeeded) {
                    data['additional_data']['threat_metrix_id'] = this.threatMetrixId;
                }

                return data;
            },

            initializeForm: function () {
                // if payment method is initially selected
                if (quote.paymentMethod() && quote.paymentMethod().method === this.getCode()) {
                    this.selectPaymentMethod();
                }
            },

            waitForSetBasketData: function (maxRetries = 10, interval = 500) {
                const unzerCheckoutElementId = 'unzer-payment-' + this.getCode();
                const unzerPayment = document.getElementById(unzerCheckoutElementId);

                if (!unzerPayment || typeof unzerPayment.setBasketData !== 'function') {
                    if (maxRetries > 0) {
                        setTimeout(() => this.waitForSetBasketData(maxRetries - 1, interval), interval);
                    } else {
                        console.error('setBasketData is not available after multiple retries.');
                    }
                    return;
                }

                unzerPayment.setBasketData({
                    amount: (quote.totals() ? quote.totals() : quote)['base_grand_total'],
                    currencyType: (quote.totals() ? quote.totals() : quote)['base_currency_code']
                })

                const methodConfig = window.checkoutConfig.payment[this.getCode()] || {};
                const unzerCustomerId = methodConfig.unzerCustomerId || null;

                const email = quote.guestEmail
                    ? quote.guestEmail
                    : (window.customerData ? window.customerData.email : '');

                const shop = window.checkoutConfig?.quoteData.store_id || '';

                const billing = quote.billingAddress();
                const shipping = quote.shippingAddress();

                const customerId = window.checkoutConfig?.customerData.id || '';
                const uniqueCustomerId = `${customerId}_${email}_${shop}`;

                if (unzerCustomerId) {
                    this.customer = unzerCustomerId;
                }

                const customer = {
                    id: unzerCustomerId || '',
                    customerId: uniqueCustomerId,
                    firstname: billing ? billing.firstname : '',
                    lastname: billing ? billing.lastname : '',
                    email: email,
                    ...(customerData?.dob ? {birthDate: customerData.dob.split('T')[0]} : {}),
                    billingAddress: billing ? {
                        name: (billing.firstname || '') + ' ' + (billing.lastname || ''),
                        street: Array.isArray(billing.street) ? billing.street.join(' ') : billing.street,
                        zip: billing.postcode,
                        city: billing.city,
                        country: billing.countryId
                    } : {},

                    shippingAddress: shipping ? {
                        name: (shipping.firstname || '') + ' ' + (shipping.lastname || ''),
                        street: Array.isArray(shipping.street) ? shipping.street.join(' ') : shipping.street,
                        zip: shipping.postcode,
                        city: shipping.city,
                        country: shipping.countryId
                    } : {},
                    ...(billing?.company && billing.company.trim() !== ''
                        ? {company: billing.company.trim()}
                        : {}),
                    customerSettings: {
                        type: billing?.company && billing.company.trim() !== '' ? 'B2B' : 'B2C'
                    }
                };
                unzerPayment.setCustomerData(customer);
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

                            if (response.customerResponse && response.customerResponse.success) {
                                this.customer = response.customerResponse.data.id;
                            }
                            this.resourceId = response.submitResponse.data.id;

                            if (response.threatMetrixId) {
                                this.threatMetrixId = response.threatMetrixId;
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
            },

            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vault_code;
            },
        });
    }
);
