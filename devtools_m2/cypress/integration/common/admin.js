import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mapFrontendWebsiteId, mapFrontendStoreViewId } from "../../lib/frontend_context"

Given('I am logged into the admin interface', function() {
  cy.visit(`http://main.magento.localhost:3006/admin_123`)
  cy.get('input[name="login[username]"]').type('admin')
  cy.get('input[name="login[password]"]').type('abc1234567890')
  cy.contains('Sign in').click()
})

When('I have set up Drip via the API for {string}', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    let requestBody = {
      "accountParam":"123456",
      "integrationToken": "abcdefg",
      "testMode": "1"
    }

    if (site !== 'default') {
      requestBody["websiteId"] = mapFrontendWebsiteId(site)
    }

    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/integration",
      method: "POST",
      auth: {
        bearer: token_response.body
      },
      body: requestBody
    })
  })
})

Given('I have set up a multi-store configuration', function() {
  cy.createScopes({})

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
    cy.get('input[name="groups[api_settings][fields][account_param][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][integration_token][inherit]"]').uncheck()
  }
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

// Virtual product
Given('I have configured a virtual widget for {string}', function(site) {
  cy.createProduct({
    "sku": "virt-1",
    "name": "Virtual Widget 1",
    "description": "This is not really a widget. Any resemblance between this an a real widget is the misuse of imagination.",
    "shortDescription": "This is a non-real virtual widget.",
    "typeId": "virtual",
    "storeId": mapFrontendStoreViewId(site),
    "websiteIds": [mapFrontendWebsiteId(site)]
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

When('I create an order for a {string} widget', function(widgetType) {
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

  if(widgetType != 'virtual') {
    cy.route('POST', '/admin_123/sales/order_create/loadBlock/block/shipping_method,totals,billing_method?isAjax=true').as('loadShipping')
    cy.route('POST', '/admin_123/sales/order_create/loadBlock/block/shipping_method,totals?isAjax=true').as('loadShippingData')
    // Why the second click is required, I haven't a clue... I tried a lot of ways to make this work, and this was the only one that did.
    cy.get('#order-shipping-method-summary a').click()
    cy.get('#order-shipping-method-summary a').click()
    cy.wait('@loadShippingData')

    cy.get('input[name="order[shipping_method]"]').click()
    cy.wait('@loadShipping')
  }

  cy.contains('Submit Order').click({ force: true })

  // cy.contains('Order # 000000001')
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
