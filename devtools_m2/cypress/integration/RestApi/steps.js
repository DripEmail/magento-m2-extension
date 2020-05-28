import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I have set up Drip via the API', function(site) {
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
    })
  })
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
      const body = JSON.parse(response.body)
      expect(body["account_param"]).to.eq('123456')
      expect(body["integration_token"]).to.eq('abcdefg')
      expect(body["magento_version"]).to.eq("2.3.2")
      expect(body["plugin_version"]).to.eq("1.8.5")
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
      const body = JSON.parse(response.body)
      expect(body["order_url"]).to.eq('http://main.magento.localhost:3006/sales/order/view/order_id/1/')
    })
  })
})
