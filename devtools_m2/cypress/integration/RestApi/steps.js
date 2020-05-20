import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Then('a REST API request gives the correct response', function(site) {
  cy.request("http://main.magento.localhost:3006/rest/V1/drip/account_id?websiteId=1").then((response) => {
    expect(response.status).to.eq(200)
    expect(JSON.parse(response.body)["account_id"]).to.eq('123456')
  })
})
