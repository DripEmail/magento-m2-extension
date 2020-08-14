import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"
import { getCurrentFrontendDomain } from "../../lib/frontend_context"

const Mockclient = mockServerClient("localhost", 1080);

Given('I have disabled email communications', function() {
  cy.setConfig({
    path: "system/smtp/disable",
    value: "1",
  })
})

When('I create a {string} account', function(state) {
  cy.contains('Create an Account').click()
  cy.get('#form-validate').within(function() {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('testuser@example.com')
    cy.get('input[name="password"]').type('blahblah123!!!')
    cy.get('input[name="password_confirmation"]').type('blahblah123!!!')
    if (state === 'subscribed') {
      cy.get('input[name="is_subscribed"]').check()
    }
    cy.contains('Create an Account').click()
  })

  cy.contains('Thank you for registering')
})

When('I {string} from the general newsletter', function(state) {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.visit(`${getCurrentFrontendDomain()}/newsletter/manage/`)

  if (state === 'unsubscribe') {
    cy.get('input[name="is_subscribed"]').uncheck()
  } else {
    cy.get('input[name="is_subscribed"]').check()
  }
  cy.contains('Save').click()
})

When('I subscribe on the homepage', function(state) {
  cy.get('#newsletter').type("testuser@example.com")
  cy.contains('Subscribe').click()
  cy.contains('Thank you for your subscription.')
})

Then('A {string} {string} event should be sent to the WIS', function(subject, action) {
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/123456/integrations/abcdefg/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests.find((elm) => {
      let versionHeader = elm.headers["X-Drip-Connect-Plugin-Version"]
      let body = JSON.parse(elm.body.string);
      let actionMatch = body.action == action;
      let subjectMatch = body.subject == subject;
      let customerIdMatch = body.customer_id && /^\d+$/.test(body.customer_id);
      let emailMatch = body.email && body.email == 'testuser@example.com';
      return versionHeader && actionMatch && subjectMatch && (customerIdMatch || emailMatch);
    })).to.exist
  })
})
