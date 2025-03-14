define(
    [
        'ko',
        'Unzer_PAPI/js/view/payment/method-renderer/base',
        'Magento_Vault/js/view/payment/vault-enabler',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko, Component, VaultEnabler, url, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                isActivePaymentTokenEnabler: false,
                fields: {
                    cvc: {valid: null},
                    expiry: {valid: null},
                    number: {valid: null},
                    holder: {valid: null},
                },
                template: 'Unzer_PAPI/payment/cards',
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.isActivePaymentTokenEnabler(this.isActivePaymentTokenEnabler);
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                // Check if the message is sent from the shop URL with the specific route and Redirect.
                window.addEventListener('message', function (event) {
                    // Check if the message is sent from the shop URL with the specific route
                    if (!event.origin.startsWith(window.location.origin) || !event.data.url.endsWith('/unzer/payment/callback/')) return;

                    //Process Reload:
                    if (event.data && event.data.action === 'redirect') {
                        if (event.data.url.endsWith('/unzer/payment/callback/')) {
                            window.location.href = event.data.url;
                        }
                    }
                })

                return this;
            },

            afterPlaceOrder: function () {
                if(this.is3dsiFrameEnabled() === true){

                    // iFrame Placeholder
                    let placeholderIframe = document.getElementById('3dsIframe');
                    let buttons = document.querySelectorAll('button.unzerUI.primary.button.fluid');

                    if (placeholderIframe) {
                        // Create new iFrame
                        const newIframe = document.createElement('iframe');
                        newIframe.src = window.location.origin+'/'+ this.redirectUrl;
                        newIframe.id = '3dsIframe';
                        newIframe.width = '100%';
                        newIframe.height = '200';
                        newIframe.title = '3DS Check';
                        newIframe.style.visibility = 'visible';
                        newIframe.style.border = '1px solid black';

                        //Change current iFrame with the new one
                        placeholderIframe.parentNode.replaceChild(newIframe, placeholderIframe);

                        //Debug if necessary:
                        newIframe.onload = function () {
                        };

                        //deactivate order now Buttons.
                        buttons.forEach(button => {
                            button.disabled = true;
                        });

                    } else {
                        //Fallback:
                        fullScreenLoader.startLoader();
                        window.location.replace(url.build(this.redirectUrl));
                    }
                }
                else{
                    fullScreenLoader.startLoader();
                    window.location.replace(url.build(this.redirectUrl));
                }
            },



            initializeForm: function () {
                const self = this;

                this.resourceProvider = this.sdk.Card();
                this.resourceProvider.create('number', {
                    containerId: 'unzer-card-element-id-number',
                    onlyIframe: false
                });
                this.resourceProvider.create('expiry', {
                    containerId: 'unzer-card-element-id-expiry',
                    onlyIframe: false
                });
                this.resourceProvider.create('cvc', {
                    containerId: 'unzer-card-element-id-cvc',
                    onlyIframe: false
                });
                this.resourceProvider.create('holder', {
                    containerId: 'unzer-card-element-id-holder',
                    onlyIframe: false
                });

                this.fields.cvc.valid = ko.observable(false);
                this.fields.expiry.valid = ko.observable(false);
                this.fields.number.valid = ko.observable(false);
                this.fields.holder.valid = ko.observable(false);

                this.resourceProvider.addEventListener('change', function (event) {
                    if ("type" in event) {
                        self.fields[event.type].valid("success" in event && event.success);
                    }
                });
            },

            allInputsValid: function () {
                const self = this;

                return ko.computed(function () {
                    return self.fields.cvc.valid() &&
                        self.fields.expiry.valid() &&
                        self.fields.number.valid() &&
                        self.fields.holder.valid();
                })();
            },

            validate: function () {
                return this.allInputsValid();
            },

            /**
             * @returns {Boolean}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @returns {Boolean}
             */
            is3dsiFrameEnabled: function () {
                return window.checkoutConfig.payment.unzer_cards.three_ds_iframe_enabled;
            },

            /**
             * Returns vault code.
             *
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vault_code;
            },

            getData: function () {
                const data = this._super();

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            }
        });
    }
);
