define([
  "Magento_Customer/js/customer-data"
], function(customerData) {
  "use strict";

  function identifyCustomer(email) {
    if (email) {
      window._dcq.push(["identify", {
        email: email
      }]);
    }
  }

  return function (config, element) {
    var email = customerData.get("customer")().email;

    // This data is cached in localstorage. It may have changed, so refetch if we don't have it.
    if (typeof (email) === "undefined") {
      customerData.reload("customer").then(function() {
        email = customerData.get("customer")().email;
        identifyCustomer(email);
      });
    } else {
      identifyCustomer(email);
    }
  };
});
