# Magento 2 Drip Connect Changelog

## NEXT
* Skip sending order items on after_save with invalid emails.

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
