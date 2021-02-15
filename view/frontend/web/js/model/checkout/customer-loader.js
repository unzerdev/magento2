define(
    [
        'jquery',
        'ko',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
    ],
    function ($, ko, storage, errorProcessor, fullScreenLoader, quote, urlBuilder) {
        'use strict';

        var currentRequest = null,
            customerObservable = null;

        function loadCustomer() {
            fullScreenLoader.startLoader();

            customerObservable = customerObservable || ko.observable(null);

            var request = storage.post(
                urlBuilder.createUrl('/hpmgw/get-external-customer', {}),
                JSON.stringify({
                    guestEmail: quote.guestEmail,
                })
            );

            request.always(fullScreenLoader.stopLoader);
            request.fail(errorProcessor.process);
            request.done(function(customer) {
                if (currentRequest !== request ||
                    customer === null ||
                    (customer instanceof Array && customer.length === 0)) {
                    return;
                }

                currentRequest = null;

                // Magento converts camel case to snake case in API responses so we must manually map
                // the properties to be consistent with the casing for the heidelpay SDK.

                customer.billingAddress = customer.billing_address;
                delete customer.billing_address;

                customer.shippingAddress = customer.shipping_address;
                delete customer.shipping_address;

                customer.companyInfo = customer.company_info;
                delete customer.company_info;

                customerObservable(customer);
            });

            if (currentRequest) {
                currentRequest.abort();
            }

            currentRequest = request;
        }

        $(window).on('hashchange', function() {
            // if the hash changes the customer has switched between steps and possibly changed
            // his billing and/or shipping address, in which case we can not use the customer
            // that we already loaded.
            if (location.hash === '#payment') {
                loadCustomer();
            }
        });

        return {
            getCustomerObservable: function() {
                if (customerObservable === null) {
                    loadCustomer();
                }

                return customerObservable;
            },
        };
    }
);
