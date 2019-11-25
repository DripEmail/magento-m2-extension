import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Then('No web requests are sent', function() {
  cy.log('Validating that we got nothing. Absolutely nothing.')
  cy.wrap(Mockclient.retrieveRecordedRequests({})).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(0)
  })
})
