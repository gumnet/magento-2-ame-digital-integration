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
                return document.getElementById('ameLogoHtml').innerHTML;
            },
            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            setCashbackTxt: function() {
                return document.getElementById('cashbackTxtHtml').innerHTML;
            }
        });
    }
);
