import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I add a widget to my cart', function(type) {
  cy.route('POST', 'checkout/cart/add/**').as('addToCartRequest')
  cy.visit("http://main.magento.localhost:3006/widget-1.html")

  cy.get('#product-addtocart-button').click()
  cy.wait('@addToCartRequest') // Make sure that the cart addition has finished before continuing.
  self.item = "Widget 1"
})

Then('an authorized integration request with no websiteId gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/integration",
      method: "POST",
      auth: {
        bearer: token_response.body
      },
      body: {"accountParam":"123456", "integrationToken": "abcdefg"}
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body["account_param"]).to.eq('123456')
      expect(response.body["integration_token"]).to.eq('abcdefg')
    })
  })
})

Then('an authorized integration request gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/integration",
      method: "POST",
      auth: {
        bearer: token_response.body
      },
      body: {"websiteId":"1", "accountParam":"123456", "integrationToken": "abcdefg"}
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body["account_param"]).to.eq('123456')
      expect(response.body["integration_token"]).to.eq('abcdefg')
    })
  })
})

Then('an authorized delete integration request gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/integration?websiteId=1",
      method: "DELETE",
      auth: {
        bearer: token_response.body
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body["account_param"]).to.be.null
      expect(response.body["integration_token"]).to.be.null
    })
  })
})

Then('an authorized integration request for a non-existent site gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/integration",
      method: "POST",
      auth: {
        bearer: token_response.body
      },
      body: {"websiteId":"99", "accountParam":"123456", "integrationToken": "abcdefg"},
      failOnStatusCode: false
    }).then((response) => {
      expect(response.status).to.eq(404)
    })
  })
})

Then('an unauthorized integration request gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/drip/integration",
    method: "POST",
    failOnStatusCode: false,
    body: {"websiteId":"1", "accountParam":"123456", "integrationToken": "abcdefg"} }).then((response) => {
    expect(response.status).to.eq(401)
  })
})

Then('an authorized status request gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/status?websiteId=1",
      method: "GET",
      auth: {
        bearer: token_response.body
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      const body = response.body
      expect(body["account_param"]).to.eq('123456')
      expect(body["integration_token"]).to.eq('abcdefg')
      expect(body["magento_version"]).to.eq("2.3.2")
      expect(body["plugin_version"]).to.eq("2.0.0")
    })
  })
})

Then('an unauthorized status request gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/drip/status?websiteId=1",
    method: "GET",
    failOnStatusCode: false}).then((response) => {
    expect(response.status).to.eq(401)
  })
})

Then('an authorized order details request gives the correct response', function() {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/order/1",
      method: "GET",
      auth: {
        bearer: token_response.body
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      const body = response.body
      expect(body["order_url"]).to.eq('http://main.magento.localhost:3006/sales/order/view/order_id/1/')
    })
  })
})

Then('an authorized product details request gives the correct response', function() {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/product/1",
      method: "GET",
      auth: {
        bearer: token_response.body
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      const body = response.body
      expect(body.product_url).to.eq('http://main.magento.localhost:3006/widget-1.html')
      expect(body.image_url).to.eq('http://main.magento.localhost:3006/media/catalog/product/my_image.png')
    })
  })
})


Then('an authorized cart details request gives the correct response', function() {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/integration/admin/token",
    method: "POST",
    body: {"username":"admin", "password":"abc1234567890"}
  }).then((token_response) => {
    cy.request({
      url: "http://main.magento.localhost:3006/rest/V1/drip/cart/1",
      method: "GET",
      auth: {
        bearer: token_response.body
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      const body = response.body
      expect(body.cart_url).to.startWith("http://main.magento.localhost:3006/drip/cart/index/q/1/s/1/k/")
      self.cart_url = body.cart_url
    })
  })
})

Then('I open the abandoned cart url', function(){
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())
  // To use this step, a previous step has to fill the abandonedCartUrl property. See 'A simple cart event should be sent to Drip' for an example.
  cy.visit(self.cart_url)
})

Then("my item is there", function(){

  // To use this step, a previous step has to set the item property. See 'A simple cart event should be sent to Drip' for an example.
  cy.contains(self.item)
})
