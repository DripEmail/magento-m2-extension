import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"
import { getCurrentFrontendDomain, getCurrentFrontendStoreViewId, getCurrentFrontendSite } from "../../lib/frontend_context"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function() {
  cy.visit('/customer/account/create/')
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
    path: '/123456/integrations/abcdefg/events',
    body: {
      "type": "JSON_PATH",
      "jsonPath": "$[?(@.cart_id)]"
    }
  })).then(function(recordedRequests) {
    expect(recordedRequests[0].headers["X-Drip-Connect-Plugin-Version"]).to.exist
    const body1 = recordedRequests[0].body.json
    expect(body1.action).to.eq('created')
    expect(body1.cart_id).to.eq('1')

    if (recordedRequests[1] !== undefined) {
      const body2 = recordedRequests[1].body.json
      expect(body2.action).to.eq('updated')
      expect(body2.cart_id).to.eq('1')
      expect(body2.items).to.have.lengthOf(1)
      expect(body2.items[0]['item_id']).to.eq('1')
      expect(body2.items[0]['product_id']).to.eq('1')
      expect(body2.items[0]['product_parent_id']).to.eq(undefined)
    }
  })
})

Then('A configurable cart event should be sent to Drip', function() {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    path: '/123456/integrations/abcdefg/events',
    body: {
      "type": "JSON_PATH",
      "jsonPath": "$[?(@.cart_id)]"
    }
  })).then(function(recordedRequests) {
    expect(recordedRequests[0].headers["X-Drip-Connect-Plugin-Version"]).to.exist
    const body1 = recordedRequests[0].body.json
    expect(body1.action).to.eq('created')
    expect(body1.cart_id).to.eq('1')

    if (recordedRequests[1] !== undefined) {
      expect(recordedRequests[1].headers["X-Drip-Connect-Plugin-Version"]).to.exist
      const body2 = recordedRequests[1].body.json
      expect(body2.action).to.eq('updated')
      expect(body2.cart_id).to.eq('1')
      expect(body2.items).to.have.lengthOf(1)
      expect(body2.items[0]['item_id']).to.eq('1')
      expect(body2.items[0]['product_id']).to.eq('1')
      expect(body2.items[0]['product_parent_id']).to.eq('3')
    }
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

Then('An order event should be sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    path: "/123456/integrations/abcdefg/events",
    body: {
      "type": "JSON_PATH",
      "jsonPath": "$[?(@.order_id)]"
    }
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = recordedRequests[0].body.json
    expect(body.action).to.eq('placed')
    expect(body.order_id).to.eq('000000001')
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
