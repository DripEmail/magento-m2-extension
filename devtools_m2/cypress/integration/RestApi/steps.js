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
      url: "http://main.magento.localhost:3006/rest/V1/drip/account_id?websiteId=1",
      auth: {
        bearer: token_response.body
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(JSON.parse(response.body)["account_id"]).to.eq('123456')
    })
  })
})

Then('an unauthorized REST API request gives the correct response', function(site) {
  cy.request({ url: "http://main.magento.localhost:3006/rest/V1/drip/account_id?websiteId=1", failOnStatusCode: false }).then((response) => {
    expect(response.status).to.eq(401)
  })
})
