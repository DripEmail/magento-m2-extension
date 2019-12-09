import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mapFrontendWebsiteId, mapFrontendStoreViewId } from "../../lib/frontend_context"

Given('I am logged into the admin interface', function() {
  cy.visit(`http://main.magento.localhost:3006/admin_123`)
  cy.get('input[name="login[username]"]').type('admin')
  cy.get('input[name="login[password]"]').type('abc1234567890')
  cy.contains('Sign in').click()
})

Given('I have set up a multi-store configuration', function() {
  cy.contains('All Stores').click({ force: true })

  // globals.js defines window.setLocation, which is loaded async. We need to wait for this to be loaded.
  cy.window().its('setLocation')

  cy.contains('Create Website').click()
  cy.window().its('setLocation')
  cy.get('input[name="website[name]"]').type('site1_website')
  cy.get('input[name="website[code]"]').type('site1_website')
  cy.contains('Save Web Site').click()

  cy.window().its('setLocation')
  cy.get('button#add_group').click()
  cy.window().its('setLocation')
  cy.get('select[name="group[website_id]"]').select('site1_website')
  cy.get('input[name="group[name]"]').type('site1_store')
  cy.get('input[name="group[code]"]').type('site1_store')
  cy.get('select[name="group[root_category_id]"]').select('Default Category')
  cy.contains('Save Store').click()

  cy.window().its('setLocation')
  cy.contains('Create Store View').click()
  cy.window().its('setLocation')
  cy.get('select[name="store[group_id]"]').select('site1_store')
  cy.get('input[name="store[name]"]').type('site1_store_view')
  cy.get('input[name="store[code]"]').type('site1_store_view')
  cy.get('select[name="store[is_active]"]').select('Enabled')
  cy.contains('Save Store View').click()
  cy.contains('OK').click()

  // cy.contains('Stores').click()
  cy.get('[data-ui-id="menu-magento-backend-stores-settings"]').within(function() {
    // Forcing since the sidebar opens sporadically at best.
    cy.contains('Configuration').click({force: true})
  })
  cy.wait(1000) // Some JS has to run before we can successfully click this.
  cy.get('div.store-switcher').within(function() {
    // cy.get('button#store-change-button').click({force: true})
    cy.contains('site1_website').trigger('click', {force: true})
  })
  cy.contains('OK').click()
  cy.get('#system_config_tabs').within(function() {
    cy.contains('Web').click()
  })
  cy.contains('Base URLs').click()
  cy.get('[name="groups[unsecure][fields][base_url][inherit]"]').uncheck()
  cy.get('[name="groups[unsecure][fields][base_url][value]"]').clear().type(`http://site1.magento.localhost:3006/`)
  cy.get('[name="groups[unsecure][fields][base_link_url][inherit]"]').uncheck()
  cy.get('[name="groups[unsecure][fields][base_link_url][value]"]').clear().type(`http://site1.magento.localhost:3006/`)
  cy.contains('Save Config').click()
})

Given('I have configured Drip to be enabled for {string}', function(site) {
  cy.get('li[data-ui-id="menu-magento-config-system-config"] a').click({force: true})
  cy.contains('Drip Connect', {timeout: 20000}).click({ force: true })
  cy.switchAdminContext(site)
  cy.contains('Module Settings').click()
  cy.contains('API Settings').click()
  if (site !== 'default') {
    cy.get('input[name="groups[module_settings][fields][is_enabled][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][account_id][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][api_key][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][url][inherit]"]').uncheck()
  }
  cy.get('select[name="groups[module_settings][fields][is_enabled][value]"]').select('1')
  cy.get('input[name="groups[api_settings][fields][account_id][value]"]').type('123456')
  cy.get('input[name="groups[api_settings][fields][api_key][value]"]').type('abc123')
  cy.get('input[name="groups[api_settings][fields][url][value]"]').clear().type('http://mock:1080/v2/')
  cy.contains('Save Config').click()
})

Given('I have configured a widget', function() {
  cy.get('[data-ui-id="menu-magento-catalog-catalog-products"] a').click({ force: true })
  // TODO: This is not good. Find a better way to know when the product listing page has finished loading.
  cy.wait(4000)
  cy.get('#add_new_product-button').click()
  cy.get('input[name="product[name]"]', {timeout: 10000}).type('Widget 1')
  cy.get('input[name="product[sku]"]').clear().type('widg-1')
  cy.get('input[name="product[price]"]').type('120')
  cy.get('input[name="product[quantity_and_stock_status][qty]"]').type('120')
  cy.contains('Save').click()
})

// Simple Product
Given('I have configured a simple widget for {string}', function(site) {
  cy.createProduct({
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    // This is to set the context for the product save, so that rewrites and such get generated correctly.
    "storeId": mapFrontendStoreViewId(site),
    "websiteIds": [mapFrontendWebsiteId(site)]
  })
})

Given('I have configured a different simple widget for {string}', function(site) {
  cy.createProduct({
    "sku": "widg-2",
    "name": "Widget 2",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    // This is to set the context for the product save, so that rewrites and such get generated correctly.
    "storeId": mapFrontendStoreViewId(site),
    "websiteIds": [mapFrontendWebsiteId(site)]
  })
})

// Configurable Product
Given('I have configured a configurable widget', function() {
  cy.createProduct({
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "configurable",
    "image": "parent_image.png",
    "attributes": {
      "widget_size": {
        "XL": {
          "sku": "widg-1-xl",
          "name": "Widget 1 XL",
          "description": "This is really an XL widget. There are many like it, but this one is mine.",
          "shortDescription": "This is really an XL widget.",
        },
        "L": {
          "sku": "widg-1-l",
          "name": "Widget 1 L",
          "description": "This is really an L widget. There are many like it, but this one is mine.",
          "shortDescription": "This is really an L widget.",
        }
      }
    }
  })
})

Given('I have configured a configurable widget with an invisible child', function() {
  cy.createProduct({
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "configurable",
    "image": "parent_image.png",
    "attributes": {
      "widget_size": {
        "XL": {
          "sku": "widg-1-xl",
          "name": "Widget 1 XL",
          "description": "This is really an XL widget. There are many like it, but this one is mine.",
          "shortDescription": "This is really an XL widget.",
          "visibility": 1,
        },
        "L": {
          "sku": "widg-1-l",
          "name": "Widget 1 L",
          "description": "This is really an L widget. There are many like it, but this one is mine.",
          "shortDescription": "This is really an L widget.",
          "visibility": 1,
        }
      }
    }
  })
})

// Grouped Product
Given('I have configured a grouped widget', function() {
  cy.createProduct({
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "grouped",
    "associated": [
      {
        "sku": "widg-1-sub1",
        "name": "Widget 1 Sub 1",
        "description": "This is really a sub1 widget. There are many like it, but this one is mine.",
        "shortDescription": "This is really a sub1 widget.",
      },
      {
        "sku": "widg-1-sub2",
        "name": "Widget 1 Sub 2",
        "description": "This is really a sub2 widget. There are many like it, but this one is mine.",
        "shortDescription": "This is really a sub2 widget.",
      }
    ]
  })
})

// Bundle Product
Given('I have configured a bundle widget', function() {
  // skuType of 1 is a fixed sku rather than generating a composite SKU.
  cy.createProduct({
    "sku": "widg-1",
    "skuType": 1,
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "bundle",
    "bundle_options": [
      {
        "title": "item01",
        "default_title": "item01",
        "product_options": [
          {
            "sku": "widg-1-sub1",
            "name": "Widget 1 Sub 1",
            "description": "This is really a sub1 widget. There are many like it, but this one is mine.",
            "shortDescription": "This is really a sub1 widget.",
          }
        ]
      },
      {
        "title": "item02",
        "default_title": "item02",
        "product_options": [
          {
            "sku": "widg-1-sub2",
            "name": "Widget 1 Sub 2",
            "description": "This is really a sub2 widget. There are many like it, but this one is mine.",
            "shortDescription": "This is really a sub2 widget.",
          }
        ]
      }
    ]
  })
})

Given('a customer exists for website {string}', function(site) {
  cy.createCustomer({
    websiteId: mapFrontendWebsiteId(site),
    storeId: mapFrontendStoreViewId(site),
  })
})

Given('a different customer exists for website {string}', function(site) {
  cy.createCustomer({
    websiteId: mapFrontendWebsiteId(site),
    storeId: mapFrontendStoreViewId(site),
    email: "jd2@example.com",
    firstname: "John2"
  })
})

When('I create an order', function() {
  cy.contains('Orders').click({force: true})
  cy.contains('Create New Order').click()

  // Select customer
  cy.contains('John Doe').click()

  // Add product to order
  cy.contains('Add Products').click()
  cy.contains('Widget 1').click()

  cy.contains('Add Selected Product(s) to Order').click({ force: true })

  // Fill out shipping/billing addresses
  cy.get('input[name="order[billing_address][firstname]"]').type('John')
  cy.get('input[name="order[billing_address][lastname]"]').type('Doe')
  cy.get('input[name="order[billing_address][street][0]"]').type('123 Main St.')
  cy.get('input[name="order[billing_address][city]"]').type('Centerville')
  cy.get('select[name="order[billing_address][region_id]"]').select('Minnesota')
  cy.get('input[name="order[billing_address][postcode]"]').type('12345')
  cy.get('input[name="order[billing_address][telephone]"]').type('999-999-9999')

  cy.route('POST', '/admin_123/sales/order_create/loadBlock/block/shipping_method,totals,billing_method?isAjax=true').as('loadShipping')
  cy.route('POST', '/admin_123/sales/order_create/loadBlock/block/shipping_method,totals?isAjax=true').as('loadShippingData')
  // Why the second click is required, I haven't a clue... I tried a lot of ways to make this work, and this was the only one that did.
  cy.get('#order-shipping-method-summary a').click()
  cy.get('#order-shipping-method-summary a').click()
  cy.wait('@loadShippingData')

  cy.get('input[name="order[shipping_method]"]').click()
  cy.wait('@loadShipping')

  cy.contains('Submit Order').click({ force: true })

  cy.contains('Order # 000000001')
})

When('I create an order for {string}', function(site) {
  cy.visit("http://main.magento.localhost:3006/admin_123/admin/dashboard/")
  cy.contains('Orders').click({force: true})
  cy.contains('Create New Order').click()

  let storeSelector
  let productName
  let customerName
  let orderNumber

  switch (site) {
    case 'site1':
      storeSelector = '#store_300'
      productName = 'Widget 2'
      customerName = 'John2 Doe'
      orderNumber = '300000000001'
      break;
    case 'main':
      storeSelector = '#store_1'
      productName = 'Widget 1'
      customerName = 'John Doe'
      orderNumber = '000000001'
      break;
    default:
      throw 'Unsupported site'

  }

  // Select customer
  cy.contains(customerName).click()

  // Select store
  cy.get(storeSelector).check()

  // Add product to order
  cy.contains('Add Products').click()
  cy.contains(productName).click()

  cy.contains('Add Selected Product(s) to Order').click({ force: true })

  // Fill out shipping/billing addresses
  cy.get('input[name="order[billing_address][firstname]"]').type('John')
  cy.get('input[name="order[billing_address][lastname]"]').type('Doe')
  cy.get('input[name="order[billing_address][street][0]"]').type('123 Main St.')
  cy.get('input[name="order[billing_address][city]"]').type('Centerville')
  cy.get('select[name="order[billing_address][region_id]"]').select('Minnesota')
  cy.get('input[name="order[billing_address][postcode]"]').type('12345')
  cy.get('input[name="order[billing_address][telephone]"]').type('999-999-9999')

  cy.route('POST', '/admin_123/sales/order_create/loadBlock/block/shipping_method,totals,billing_method?isAjax=true').as('loadShipping')
  cy.route('POST', '/admin_123/sales/order_create/loadBlock/block/shipping_method,totals?isAjax=true').as('loadShippingData')
  // Why the second click is required, I haven't a clue... I tried a lot of ways to make this work, and this was the only one that did.
  cy.get('#order-shipping-method-summary a').click()
  cy.get('#order-shipping-method-summary a').click()
  cy.wait('@loadShippingData')

  cy.get('input[name="order[shipping_method]"]').click()
  cy.wait('@loadShipping')

  cy.contains('Submit Order').click({ force: true })

  cy.contains('Order # ' + orderNumber)
})
