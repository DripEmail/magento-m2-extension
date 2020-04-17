import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"
import { getCurrentFrontendDomain, getCurrentFrontendStoreViewId, getCurrentFrontendSite } from "../../lib/frontend_context"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function() {
  cy.contains('Create an Account').click()
  cy.get('#form-validate').within(function() {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('testuser@example.com')
    cy.get('input[name="password"]').type('blahblah123!!!')
    cy.get('input[name="password_confirmation"]').type('blahblah123!!!')
    cy.get('input[name="is_subscribed"]').check()
    cy.contains('Create an Account').click()
  })
  cy.get('.message-success > div').contains('Thank you for registering with')
  cy.visit('/lib/web/blank.html')
})

When('I add a {string} widget to my cart', function(type) {
  cy.route('POST', 'checkout/cart/add/**').as('addToCartRequest')
  cy.visit(widgetUrl(type))
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
    case 'virtual':
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

When('I check out as a guest', function() {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.visit('/checkout/#shipping')
  cy.contains('Flat Rate', {timeout: 30000})  // wait for the page to render

  cy.get('input[id="customer-email"]').first().type('testuser@example.com')
  cy.get('input[name="firstname"]').type('Test')
  cy.get('input[name="lastname"]').type('User')
  cy.get('input[name="street[0]"]').type('123 Main St.')
  cy.get('input[name="city"]').type('Centerville')
  cy.get('select[name="region_id"]').select('Minnesota')
  cy.get('input[name="postcode"]').type('12345')
  cy.get('input[name="telephone"]').type('999-999-9999')
  //cy.get('button[onclick="billing.save()"]').click()
  cy.get('#shipping-method-buttons-container').contains('Next').click()

  cy.contains('Check / Money order')
  cy.get('input[name="billing-address-same-as-shipping"]').check()
  cy.contains('Place Order', {timeout: 30000}).click()

  cy.contains('Thank you for your purchase!')
})

When('I logout', function() {
  cy.visit('/customer/account/logout')
})

Then('A simple cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/123456/integrations/xyz123/events'
  })).then(function(recordedRequests) {
    // First when logged in and second with actual cart.
    expect(recordedRequests).to.have.lengthOf(2)

    const emptyBody = JSON.parse(recordedRequests[0].body.string)
    expect(emptyBody.base_object.fields.items_count).to.eq(0)
    expect(emptyBody.related_objects).to.have.lengthOf(1)
    expect(emptyBody.related_objects).to.have.lengthOf(1)
    expect(emptyBody.related_objects[0].fields.subscriber_status).to.eq('1')

    const body = JSON.parse(recordedRequests[1].body.string)
    expect(body.event_name).to.eq('saved_quote')
    expect(body.base_object.fields.customer_email).to.eq('testuser@example.com')
    expect(body.related_objects).to.have.lengthOf(3)
    expect(emptyBody.related_objects[0].fields.subscriber_status).to.eq('1')

    // Cucumber runs scenarios in a World object. Step definitions are run in the context of the current World instance. Data can be used between steps using the self prefix.
    self.carturl = body.base_object.ancillary_data.cart_url
    self.item = body.related_objects[1].fields.name
  })
})

Then('A configurable cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/123456/integrations/xyz123/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const body = JSON.parse(recordedRequests[1].body.string)
    expect(body.related_objects).to.have.lengthOf(5)
  })
})

Then('A grouped cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/123456/integrations/xyz123/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const body = JSON.parse(recordedRequests[1].body.string)
    expect(body.related_objects).to.have.lengthOf(5)
  })
})

Then('A bundle cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/123456/integrations/xyz123/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const body = JSON.parse(recordedRequests[1].body.string)
    expect(body.related_objects).to.have.lengthOf(7)
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
  cy.wait('@cartBuilder', { timeout: 20000 })
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

When('I check out with only a virtual product', function() {
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
  cy.wait('@cartBuilder', { timeout: 20000 })
  cy.get('button[data-role="proceed-to-checkout"]').click()

  cy.get('.billing-address-form > form > .fieldset > .street ', {timeout: 20000}).should('be.visible')
  cy.get('div[name="billingAddresscheckmo.street.0"] input[name="street[0]"]').type('123 Main St.')
  cy.get('div[name="billingAddresscheckmo.city"] input[name="city"]').type('Centerville')
  cy.get('div[name="billingAddresscheckmo.region_id"] select[name="region_id"]').select('Minnesota')
  cy.get('div[name="billingAddresscheckmo.postcode"] input[name="postcode"]').type('12345')
  cy.get('div[name="billingAddresscheckmo.country_id"] select[name="country_id"').select('United States')
  cy.get('div[name="billingAddresscheckmo.telephone"] input[name="telephone"]').type('999-999-9999')
  cy.contains("Update").click().wait(1000)

  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())
  cy.contains('Place Order').click()
  cy.contains('Thank you for your purchase!')
})

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
    expect(body.initial_status).to.eq('active')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)
    validateSimpleProduct(body.items[0])
  })
})

Then('A virtual order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(0.01)
    expect(body.initial_status).to.eq('active')
    expect(body.items_count).to.eq(1)
    expect(body.items).to.have.lengthOf(1)
    expect(body.total_shipping).to.eq(0)

    expect(Object.keys(body)).to.not.contain('shipping_address')

    basicOrderBodyAssertions(body, false)

    validateVirtualProduct(body.items[0])
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
    expect(body.initial_status).to.eq('active')
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
    expect(body.initial_status).to.eq('active')
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
    expect(body.initial_status).to.eq('active')
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

Then('A mixed order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(16.23)
    expect(body.initial_status).to.eq('active')
    expect(body.items_count).to.eq(2)
    expect(body.items).to.have.lengthOf(2)

    basicOrderBodyAssertions(body)

    body.items.forEach(item => {
      switch(item.name) {
        case 'Virtual Widget 1':
          validateVirtualProduct(item)
          break;
        case 'Widget 1':
          validateSimpleProduct(item)
          break;
        default:
          throw 'Errr... What?'
      }
    })
  })
})

Then('I open the abandoned cart url', function(){
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())
  // To use this step, a previous step has to fill the abandonedCartUrl property. See 'A simple cart event should be sent to Drip' for an example.
  cy.visit(self.carturl)
})

Then("my item is there", function(){

  // To use this step, a previous step has to set the item property. See 'A simple cart event should be sent to Drip' for an example.
  cy.contains(self.item)
})

function basicOrderBodyAssertions(body, hasPhysicalProduct=true) {
  let storeViewId = getCurrentFrontendStoreViewId()
  if (storeViewId == 1) {
    storeViewId = ''
  }

  expect(body.currency).to.eq('USD')
  expect(body.magento_source).to.eq('Storefront')
  expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
  expect(body.order_id).to.eq(`${storeViewId}000000001`)
  expect(body.order_public_id).to.eq(`${storeViewId}000000001`)
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

  if(hasPhysicalProduct) {
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
}

const widgetUrl = function(type) {
  if(type === 'virtual') {
    return `${getCurrentFrontendDomain()}/virtual-widget-1.html`

  }
  return `${getCurrentFrontendDomain()}/widget-1.html`
}

const validateSimpleProduct = function(item) {
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
}

const validateVirtualProduct = function(item) {
  expect(item.categories).to.be.empty
  expect(item.discounts).to.eq(0)
  expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
  expect(item.name).to.eq('Virtual Widget 1')
  expect(item.price).to.eq(0.01)
  expect(item.product_id).to.be.finite
  expect(item.product_variant_id).to.be.finite
  expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/virtual-widget-1.html`)
  expect(item.quantity).to.eq(1)
  expect(item.sku).to.eq('virt-1')
  expect(item.taxes).to.eq(0)
  expect(item.total).to.eq(0.01)
}
