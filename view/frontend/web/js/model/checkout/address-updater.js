define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        ko,
        quote
    ) {
        'use strict';
        let methods = [],
            prevShippingAddress,
            prevBillingAddress;

        function _updateShippingAddress(newAddress)
        {
            if (newAddress === null) {
                return
            }

            if (prevShippingAddress && newAddress.getKey() === prevShippingAddress.getKey()) {
                return;
            }
            prevShippingAddress = newAddress;

            _updateUnzerAddress('shipping', newAddress);
        }

        function _updateBillingAddress(newAddress)
        {
            if (newAddress === null) {
                return
            }

            if (prevBillingAddress && newAddress.getKey() === prevBillingAddress.getKey()) {
                return;
            }
            prevBillingAddress = newAddress;

            _updateUnzerAddress('billing', newAddress);
        }

        function _updateUnzerAddress(addressType, newAddress)
        {
            for (let key in methods) {
                let method = methods[key];
                let customer = method.customer();

                if (addressType === 'shipping') {
                    customer.shippingAddress = _mapAddressData(newAddress);
                    customer.billingAddress = _mapAddressData(quote.billingAddress());
                    customer.firstname = newAddress.firstname;
                    customer.lastname = newAddress.lastname;
                } else if (addressType === 'billing') {
                    customer.shippingAddress = _mapAddressData(quote.shippingAddress());
                    customer.billingAddress = _mapAddressData(newAddress);
                    customer.firstname = quote.shippingAddress().firstname;
                    customer.lastname = quote.shippingAddress().lastname;
                }
                method.customer(customer);
            }
        }

        function _mapAddressData(newAddress)
        {
            let address = {};
            address.city = newAddress.city;
            address.country = newAddress.countryId;
            address.name = newAddress.firstname + ' ' + newAddress.lastname;
            address.street = newAddress.street.map(function (x) {
                return x.trim();
            }).join(' ').trim();
            address.zip = newAddress.postcode;

            return address;
        }

        $(window).on('hashchange', function () {
            // if the hash changes the customer has switched between steps and possibly changed
            // his billing and/or shipping address, in which case we need to definitely update the addresses
            if (location.hash === '#shipping') {
                prevBillingAddress = null;
                prevShippingAddress = null;
            }
        });

        return {
            registerSubscribers: function (method) {
                methods[method.getCode()] = method;
                quote.billingAddress.subscribe(_updateBillingAddress);
                quote.shippingAddress.subscribe(_updateShippingAddress);
            }
        }
    }
);
