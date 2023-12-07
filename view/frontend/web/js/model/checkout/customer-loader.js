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

        let currentRequest = [],
            customerObservableList = [];

        function loadCustomer(index)
        {
            fullScreenLoader.startLoader();

            customerObservableList[index] = customerObservableList[index] || ko.observable(null);

            let request = storage.post(
                urlBuilder.createUrl('/unzer/get-external-customer', {}),
                JSON.stringify({
                    guestEmail: quote.guestEmail
                }),
                true,
                'application/json',
                {}
            );

            request.always(fullScreenLoader.stopLoader);
            request.fail(errorProcessor.process);
            request.done(function (customer) {
                if (currentRequest[index] !== request ||
                    customer === null ||
                    (customer instanceof Array && customer.length === 0)) {
                    return;
                }

                currentRequest[index] = null;

                // Magento converts camel case to snake case in API responses so we must manually map
                // the properties to be consistent with the casing for the Unzer SDK.

                customer.billingAddress = customer.billing_address;
                delete customer.billing_address;

                customer.shippingAddress = customer.shipping_address;
                delete customer.shipping_address;

                customer.companyInfo = customer.company_info;
                delete customer.company_info;

                customer.birthDate = customer.birth_date;
                delete customer.birth_date;

                customerObservableList[index](customer);
            });

            if (currentRequest[index]) {
                currentRequest[index].abort();
            }

            currentRequest[index] = request;
        }

        return {
            getCustomerObservable: function (index) {
                if (!(index in customerObservableList)) {
                    loadCustomer(index);
                }
                return customerObservableList[index];
            },
        };
    }
);
