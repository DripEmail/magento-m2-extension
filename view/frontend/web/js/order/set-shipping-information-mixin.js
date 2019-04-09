define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'mage/url',
], function ($, wrapper, quote, url) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {

            var guestEmail = quote.guestEmail;

            if (guestEmail) {
                if (typeof cartSent !== "undefined") {
                    if (guestEmail == sentForEmail) {
                        // this cart has already been sent for this email. do nothing.
                        return originalAction();
                    }
                }
                $.ajax({
                   url: url.build('drip/ajax/checkoutsendcart'),
                   data: {
                     'ajax': 1,
                     'email': guestEmail
                   },
                   type: "POST",
                   dataType: 'json'
                }).done(function (data) {
                    if (data.error) {
                        console.log('drip: '+data.error_message);
                    } else {
                        // cart has been successfully sent for given email
                        // save some data globally to prevent repeat sending the cart if customer get back to step 1
                        // but if he changed the email, we still need to send new api call (b/c it is another user in drip)
                        window.cartSent = 1;
                        window.sentForEmail = guestEmail;
                    }
                });
            } else {
                // logged in user, do not need this ajax, b/c we've already sent this data before
            }

            return originalAction();
        });
    };
});
