define(
    [
        'jquery',
        'uiComponent',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
    ],
    function ($, Component, storage, errorProcessor, fullScreenLoader, quote, urlBuilder) {
        'use strict';

        var deferred = null;

        return {
            loadFromQuote: function() {
                if (deferred === null) {
                    deferred = $.Deferred();

                    fullScreenLoader.startLoader();

                    var request = storage.post(
                        urlBuilder.createUrl('/hpmgw/get-external-customer', {}),
                        JSON.stringify({
                            guestEmail: quote.guestEmail,
                        })
                    );

                    request.always(fullScreenLoader.stopLoader);
                    request.fail(errorProcessor.process);
                    request.done(function(customer) {
                        if (customer !== null) {
                            // Magento converts camel case to snake case in API responses so we must manually map
                            // the properties to be consistent with the casing for the heidelpay SDK.

                            customer.billingAddress = customer.billing_address;
                            delete customer.billing_address;

                            customer.shippingAddress = customer.shipping_address;
                            delete customer.shipping_address;

                            customer.companyInfo = customer.company_info;
                            delete customer.company_info;

                            deferred.resolve(customer);
                        } else {
                            deferred.reject();
                        }
                    })
                }

                return deferred.promise();
            }
        };
    }
);