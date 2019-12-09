import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"
import { getCurrentFrontendDomain, getCurrentFrontendWebsiteId, getCurrentFrontendSite } from "../../lib/frontend_context"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function() {
  cy.contains('Create an Account').click()
  cy.get('#form-validate').within(function() {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('testuser@example.com')
    cy.get('input[name="password"]').type('blahblah123!!!')
    cy.get('input[name="password_confirmation"]').type('blahblah123!!!')
    cy.contains('Create an Account').click()
  })
})

When('I add a {string} widget to my cart', function(type) {
  cy.route('POST', 'checkout/cart/add/**').as('addToCartRequest')
  cy.visit(`${getCurrentFrontendDomain()}/widget-1.html`)
  switch (type) {
    case 'configurable':
      cy.get('#product-options-wrapper select').select('XL')
      break;
    case 'grouped':
      cy.get('#product_addtocart_form input[name="super_group[2]"]').clear().type('1')
      cy.get('#product_addtocart_form input[name="super_group[3]"]').clear().type('1')
      break;
    case 'bundle':
      // The pipe causes us to keep clicking until we get the stuff down below.
      const click = $el => $el.click()
      cy.contains('Customize and Add to Cart').should('be.visible').pipe(click).should(() => {
        const finalButton = Cypress.$('#product-addtocart-button')
        expect(finalButton).to.be.visible
      })
      break;
    case 'simple':
      // Do nothing
      break;
    default:
      throw 'Methinks thou hast forgotten something…'
  }
  cy.get('#product-addtocart-button').click()
  cy.wait('@addToCartRequest') // Make sure that the cart addition has finished before continuing.
})

// TODO: This is kind of ugly and duplicates the prior.
When('I add a different {string} widget to my cart', function(type) {
  cy.route('POST', 'checkout/cart/add/**').as('addToCartRequest')
  cy.visit(`${getCurrentFrontendDomain()}/widget-1.html`)
  switch (type) {
    case 'configurable':
      cy.get('#product-options-wrapper select').select('L')
      break;
    case 'grouped':
      cy.get('#product_addtocart_form input[name="super_group[2]"]').clear().type('1')
      cy.get('#product_addtocart_form input[name="super_group[3]"]').clear().type('1')
      break;
    case 'bundle':
      cy.contains('Customize and Add to Cart').click()
      break;
    case 'simple':
      // Do nothing
      break;
    default:
      throw 'Methinks thou hast forgotten something…'
  }
  cy.get('#product-addtocart-button').click()
  cy.wait('@addToCartRequest') // Make sure that the cart addition has finished before continuing.
})

Then('A simple cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
    expect(item.name).to.eq('Widget 1') // TODO: Figure out whether this is correct.
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable cart event with parent image and url should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/parent_image.png`)
    expect(item.name).to.eq('Widget 1') // TODO: Figure out whether this is correct.
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('Configurable cart events should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const body = JSON.parse(recordedRequests[recordedRequests.length - 1].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.items).to.have.lengthOf(2)
    const item1 = body.items[0]
    expect(item1.product_id).to.eq('3')
    expect(item1.product_variant_id).to.eq('1')
    expect(item1.sku).to.eq('widg-1-xl')
    const item2 = body.items[1]
    expect(item2.product_id).to.eq('3')
    expect(item2.product_variant_id).to.eq('2')
    expect(item2.sku).to.eq('widg-1-l')
  })
})

Then('A grouped cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(22.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(2)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(2)

    // These may be in any order, so we'll loop and assert based on SKU.
    body.items.forEach(item => {
      switch (item.sku) {
        case 'widg-1-sub1':
          expect(item.product_id).to.eq('2')
          expect(item.product_variant_id).to.eq('2')
          expect(item.name).to.eq('Widget 1 Sub 1')
          expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-1.html`)
          break;
        case 'widg-1-sub2':
          expect(item.product_id).to.eq('3')
          expect(item.product_variant_id).to.eq('3')
          expect(item.name).to.eq('Widget 1 Sub 2')
          expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-2.html`)
          break;
        default:
          expect.fail(`Unknown SKU: ${item.sku}`)
          break;
      }
      expect(item.categories).to.be.empty
      expect(item.discounts).to.eq(0)
      expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
      expect(item.price).to.eq(11.22)
      expect(item.quantity).to.eq(1)
      expect(item.total).to.eq(11.22)
    });
  })
})

Then('A bundle cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(22.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    // We don't send anything unique for the child products right now.
    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('3')
    expect(item.sku).to.eq('widg-1')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(22.44)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(22.44)
  })
})

When('I check out', function() {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  // TODO: Extract this into the frontend_context file.
  const currentSite = getCurrentFrontendSite()
  let storeViewCode;
  switch (currentSite) {
    case 'site1':
      storeViewCode = 'site1_store_view'
      break;
    case 'main':
      storeViewCode = 'default'
      break;
    default:
      throw 'Methinks thou hast forgotten something…'
  }
  cy.route('POST', `rest/${storeViewCode}/V1/carts/**`).as('cartBuilder')
  cy.visit(`${getCurrentFrontendDomain()}/checkout/cart`)
  cy.wait('@cartBuilder', { requestTimeout: 10000 })
  cy.get('button[data-role="proceed-to-checkout"]').click()

  cy.contains('Shipping Address', {timeout: 20000})
  cy.get('input[name="street[0]"]').type('123 Main St.')
  cy.get('input[name="city"]').type('Centerville')
  cy.get('select[name="region_id"]').select('Minnesota')
  cy.get('input[name="postcode"]').type('12345')
  cy.get('input[name="telephone"]').type('999-999-9999')
  cy.contains('Next').click()

  cy.get('input[name="billing-address-same-as-shipping"]').check()

  cy.contains('Place Order').click()
  cy.contains('Thank you for your purchase!')
})

function basicOrderBodyAssertions(body) {
  let websiteId = getCurrentFrontendWebsiteId()
  if (websiteId == 1) {
    websiteId = ''
  }

  expect(body.currency).to.eq('USD')
  expect(body.magento_source).to.eq('Storefront')
  expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
  expect(body.order_id).to.eq(`${websiteId}000000001`)
  expect(body.order_public_id).to.eq(`${websiteId}000000001`)
  expect(body.provider).to.eq('magento')
  expect(body.total_discounts).to.eq(0)
  expect(body.total_taxes).to.eq(0)
  expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)

  expect(body.billing_address.address_1).to.eq('123 Main St.')
  expect(body.billing_address.address_2).to.eq('')
  expect(body.billing_address.city).to.eq('Centerville')
  expect(body.billing_address.company).to.eq('')
  expect(body.billing_address.country).to.eq('US')
  expect(body.billing_address.first_name).to.eq('Test')
  expect(body.billing_address.last_name).to.eq('User')
  expect(body.billing_address.phone).to.eq('999-999-9999')
  expect(body.billing_address.postal_code).to.eq('12345')
  expect(body.billing_address.state).to.eq('Minnesota')

  expect(body.shipping_address.address_1).to.eq('123 Main St.')
  expect(body.shipping_address.address_2).to.eq('')
  expect(body.shipping_address.city).to.eq('Centerville')
  expect(body.shipping_address.company).to.eq('')
  expect(body.shipping_address.country).to.eq('US')
  expect(body.shipping_address.first_name).to.eq('Test')
  expect(body.shipping_address.last_name).to.eq('User')
  expect(body.shipping_address.phone).to.eq('999-999-9999')
  expect(body.shipping_address.postal_code).to.eq('12345')
  expect(body.shipping_address.state).to.eq('Minnesota')
}

Then('A simple order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(16.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(16.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})

Then('A grouped order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(32.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(2)
    expect(body.total_shipping).to.eq(10)
    expect(body.items).to.have.lengthOf(2)

    basicOrderBodyAssertions(body)

    const item1 = body.items[0]
    expect(item1.categories).to.be.empty
    expect(item1.discounts).to.eq(0)
    expect(item1.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item1.name).to.eq('Widget 1 Sub 1')
    expect(item1.price).to.eq(11.22)
    expect(item1.product_id).to.eq('2')
    expect(item1.product_variant_id).to.eq('2')
    expect(item1.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-1.html`)
    expect(item1.quantity).to.eq(1)
    expect(item1.sku).to.eq('widg-1-sub1')
    expect(item1.taxes).to.eq(0)
    expect(item1.total).to.eq(11.22)

    const item2 = body.items[1]
    expect(item2.categories).to.be.empty
    expect(item2.discounts).to.eq(0)
    expect(item2.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item2.name).to.eq('Widget 1 Sub 2')
    expect(item2.price).to.eq(11.22)
    expect(item2.product_id).to.eq('3')
    expect(item2.product_variant_id).to.eq('3')
    expect(item2.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-2.html`)
    expect(item2.quantity).to.eq(1)
    expect(item2.sku).to.eq('widg-1-sub2')
    expect(item2.taxes).to.eq(0)
    expect(item2.total).to.eq(11.22)
  })
})

Then('A bundle order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(27.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(22.44)
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('3')
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(22.44)
  })
})
