define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'GumNet_AME/payment/ame'
            },
            getLogoImagePath: function () {
                return window.checkoutConfig.payment.ame.logo_url;
            },
            getCashbackTxt: function() {
                return window.checkoutConfig.payment.ame.cashback_text;
            }
        });
    }
);
