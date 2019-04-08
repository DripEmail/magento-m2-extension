define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var guestEmail = quote.guestEmail;

            if (guestEmail) {
                // ajax to send drip api call
            } else {
                // logged in user, do not need this ajax, b/c we've already sent this data before
            }


            return originalAction();
        });
    };
});
