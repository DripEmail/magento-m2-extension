# Magento 2 Drip Connect Changelog

## Next

## 1.8.6

* Fetch email for identification call via ajax so that sites with certain page caching configurations (such as Varnish) will not cache and identify customer emails across users.

## 1.8.5

* Fixed a bug that would cause order sync'ing to fail. If an order item has no product name associated with it, we will generate a default product name of "[Missing Product <x>-<y> Name]" where <x> and <y> are the parent product ID, and associated product ID respectively. If the product is not associated, both ids will be the same.

## 1.8.4

 * Guard against syncing orders with invalid information. It seems there is a possibility that Magento will hand us an invalid order -- one with no information attached. In this case we simply will not sync the order to Drip from the scheduled job, instead logging an error.

## 1.8.3

* Fixed a bug that caused shipping address to be queried for orders that contained only virtual products
* Fix bug that caused abandoned carts to appear empty

## 1.8.2

* Send order created events when a guest completes checkout.
* Send customers to Drip on newsletter signup.

## 1.8.0

* Properly support multi-site. What makes this tricky is that customers are associated with websites, whereas orders are associated with store views. When an order event occurs, the order's store view is interrogated to determine which Drip configuration should be utilized. When a frontend customer event occurs, the currently used store view is utilized; however for an admin customer event, the first store view for that website is used. This means that trying to configure Drip at a store view level when there is more than one store view per website may result in unexpected behavior.
* Set occurred_at time for cart events based on time the relevant Quote was updated.
* When a configurable product has children than are not visible individually, use the parent's url and image_url.
* Only allow setting the memory limit override globally

## 1.7.6

* Send `product_variant_id` correctly for all product types
* Backend: Introduce the beginnings of a thorough Cypress.io based test suite.
* When a product isn't in any categories, stop sending an array with a single empty string. Send an empty array instead.
* Order batch sync now properly sends the frontend product URL.
* Disable product event based product syncing since it's very broken for configurable products. Product data is still sent as part of order and cart events.

## 1.7.4

* Skip sending order items on after_save with invalid emails.
* Prevent observer exceptions from causing customer site errors.
* Improve observer logging.
* Previously, if syncing customers failed, we wouldn't finish the job and attempt to sync guest subscribers. This was dumb. We now try both of them.
* We now send subscription status with every call, and successfully switch from unsubscribed to active when a user subscribes, or from subscribed to unsubscribed when a user changes their subscription status.
* We also send initial status with orders, so that if an unsubscribed customer places an order and are created in Drip, then they are still unsubscribed in Drip.

## 1.7.3

* Improved logger so that all output from the extension is in one place. This enables us to identify and solve customer issues more quickly.
* Skip sending events with invalid emails.
* Extend the meaning of "invalid emails" from just blank to also include things like extended character sets.

## 1.7.2

* Improved support for multi-store Magento accounts.
* Fixed an issue where orders with discounts did not sync correctly in Drip.
* Added better handling of the JS snippet when the Drip extension is enabled but account ID is not provided.
* Added fixes to increase the speed of the order sync between Magento and Drip.

## 1.7.1

Added a series of improvements to the initial sync process between Magento and Drip including:
* Orders with no email addresses will be skipped during the sync process and will not prevent a store from successfully syncing.
* Orders with no SKU’s will will not prevent a store from successfully syncing.
* The default API timeout setting has been increased to 30 seconds. This will reduce the chance of a timeout during the sync process.
* Added memory configuration with default setting to 4096MB to reduce the chance of syncs failing because of low memory.
* Added a sync reset button so if a sync is stuck processing, the button can get pressed to restart the sync.
* There was an issue where a successful customer sync would always show the status; Ready with Errors, even if the sync was successful. This issue has been resolved.

## 1.6.0

*Note that the released version mistakenly didn't include any of these changes, and these actually went out with 1.7.1.*

* Product price and inventory changes will now be sent to Drip’s Product Activity endpoint. This enables the ability to notify customers when items in their cart drop in price.
* When sending Cart Abandonment emails through Drip, links to the cart used to only work if the customer was using the same browser they created the cart on. Now, they will work in all cases.
* “Refunded an order” events will now successfully update a person’s Lifetime Value (LTV).
* Minor cosmetic fixes to the extension admin interface.

## 1.5.0

* Orders will now leverage Drip's Order Activity endpoint. This will enable advanced segmentation in Drip based on product details within orders (e.g. all customers who placed an order in a specific category).
* "Created a cart" and "Updated a cart" events will now fire for Magento 2 guest customers after the first stage of checkout. Previously, this only worked if a customer was logged in to Magento before getting to the checkout phase.
* Magento accounts that are removed from the Magento Newsletter will now be unsubscribed in Drip, as well.
* Two event properties have been added to "Cart" and "Order" events. "items_count" represents the number of unique line items in a cart or order, and "magento_source" indicates whether the order was submitted via Magento's storefront, admin, or API.
* Added ability to see the extension version in the Magento admin panel. This data will also be passed to Drip on events to assist with Drip's troubleshooting.
* Minor cosmetic fixes to the extension admin interface.

## 1.4.3

* Added support for Drip's Cart Activity endpoint, which enables Cart Abandonment Dynamic Content.
* Magento "Viewed a Product" automation triggers now fire properly.
* Stores with >25,000 customers can now fully sync to Drip.
* Email addresses subscribed to Magento's Newsletter will now be synced to Drip.
* Email addresses on Magento's Newsletter who checkout as Guest will keep an "accepts_marketing" custom field of "yes". Previously, this was set to "no".

## 1.2.5

* Sync customers and orders to Drip
* Automatically set up site tracking with Drip's JavaScript snippet
* Trigger custom events to Drip to enable ecommerce automation
