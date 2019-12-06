import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I click customer sync', function() {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())
  cy.get('li[data-ui-id="menu-magento-config-system-config"] a').click({force: true})
  cy.contains('Drip Connect', {timeout: 20000}).click({force: true})
  cy.contains('Drip Actions').click()
  cy.contains('Sync All Customers To Drip').click({force: true})
  cy.contains('Queued')
  cy.runCron()
})

Then('a customer is sent to Drip', function(state) {
  cy.log('Validating that the subscriber call has everything we need')

  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v2/123456/subscribers/batches'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.batches).to.have.lengthOf(1)
    const sub = body.batches[0].subscribers[0]
    expect(sub.email).to.eq('jd1@example.com')
    expect(sub.new_email).to.eq('')
    expect(sub.initial_status).to.eq('unsubscribed')
    expect(sub.custom_fields.accepts_marketing).to.eq('no')
  })
})
