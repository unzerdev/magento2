define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'hpg2_creditcard',
                component: 'Heidelpay_Gateway2/js/view/payment/method-renderer/creditcard'
            }
        );
        return Component.extend({});
    }
);