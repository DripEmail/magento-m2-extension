import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Then('an authorized REST API request gives the correct response', function(site) {
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

Then('an unauthorized REST API request gives the correct response', function(site) {
  cy.request({
    url: "http://main.magento.localhost:3006/rest/V1/drip/integration",
    method: "POST",
    failOnStatusCode: false,
    body: {"websiteId":"1", "accountParam":"123456", "integrationToken": "abcdefg"} }).then((response) => {
    expect(response.status).to.eq(401)
  })
})
