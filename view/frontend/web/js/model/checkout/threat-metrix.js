define([
    'uiComponent',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/quote'
], function (Component, checkoutData, quote) {
    'use strict';

    let threatMetrixExists = false;

    return {

        init: function (threatMetrixId, method) {

            if (!threatMetrixExists) {
                if (method.isThreatMetrixNeeded) {
                    // Is Paylater Invoice already preselected? Append Threat Metrix to page
                    this._appendThreatMetrix(threatMetrixId);

                } else {
                    // Paylater Invoice is not preselected? Add subscriber to add Threat Metrix only if Paylater Invoice is selected
                    let self = this;
                    self.threatMetrixSubscriber = quote.paymentMethod.subscribe(function (method) {
                        self._onChangeAppendThreatMetrix(method, self, threatMetrixId);
                    });
                }
            }
        },

        _onChangeAppendThreatMetrix: function (method, self, threatMetrixId) {
            if (!threatMetrixExists && method.isThreatMetrixNeeded) {
                self._appendThreatMetrix(threatMetrixId);

                self.threatMetrixSubscriber.dispose();
                self.threatMetrixSubscriber = null;
            }
        },

        _appendThreatMetrix: function (threatMetrixId) {
            let script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://h.online-metrix.net/fp/tags.js?org_id=363t8kgq&session_id=' + (threatMetrixId);
            document.getElementsByTagName('head')[0].appendChild(script);

            let noscript = document.createElement('noscript');
            let iframe = document.createElement('iframe');
            iframe.src = 'https://h.online-metrix.net/fp/tags?org_id=363t8kgq&session_id=' + (threatMetrixId);
            noscript.append(iframe);
            document.getElementsByTagName('body')[0].appendChild(noscript);

            threatMetrixExists = true;
        }
    };
});
