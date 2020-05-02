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
                type: 'ame',
                component: 'GumNet_AME/js/view/payment/method-renderer/ame-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);