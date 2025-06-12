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

                let self = this;
                // Subscribe to the selected payment method observable
                quote.paymentMethod.subscribe(function (newPaymentMethod) {
                    if (!newPaymentMethod || newPaymentMethod.method !== self.getCode()) {
                        self.onUnselected();
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

                return retVal;
            },

            createUnzerPaymentElement: function () {
                const unzerPaymentElementId = 'unzer-payment-' + this.getCode();

                return $('<unzer-payment>')
                    .attr('id', unzerPaymentElementId)
                    .attr('publicKey', window.checkoutConfig.payment.unzer.publicKey)
                    .attr('locale', window.checkoutConfig.payment.unzer.locale)
                    .attr('disableCTP', true);
            },

            createSpecificPaymentElement: function () {
                return '';
            },

            createUnzerCheckoutPaymentElement: function () {
                const unzerCheckoutId = 'unzer-checkout-' + this.getCode();
                const unzerCheckout = $('<unzer-checkout>')
                    .attr('id', unzerCheckoutId);

                const unzerPayButtonId = 'unzer-pay-button-' + this.getCode();
                const payButton = $('<button>')
                    .attr('id', unzerPayButtonId)
                    .addClass('button action primary checkout')
                    .attr('type', 'submit')
                    .attr('data-bind', `
                        click: placeOrder,
                        attr: {title: 'Place Order'}, // Reverting to $t directly (Magento's scope)
                        css: {disabled: !isPlaceOrderActionAllowed() || !allTermsChecked()}
                    `);

                const buttonTextSpan = $('<span>').html($t('Place Order'));
                payButton.append(buttonTextSpan);
                unzerCheckout.append(payButton);

                ko.applyBindings(this, payButton[0]);

                return unzerCheckout;
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
                        'resource_id': this.resourceId
                    }
                };

                if (this.isThreatMetrixNeeded) {
                    data['additional_data']['threat_metrix_id'] = this.customer !== null && this.customer() !== null
                        ? this.customer().threat_metrix_id : null
                }

                return data;
            },

            initializeForm: function () {
                // if payment method is initially selected
                if (quote.paymentMethod() && quote.paymentMethod().method === this.getCode()) {
                    this.selectPaymentMethod();
                }
            },
        });
    }
);
