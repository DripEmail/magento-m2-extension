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

Then('A new {string} subscriber event should be sent to Drip', function(state) {
  cy.log('Validating that the subscriber call has everything we need')

  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v2/123456/subscribers'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.subscribers).to.have.lengthOf(1)

    const sub = body.subscribers[0]
    expect(sub.email).to.eq('testuser@example.com')
    expect(sub.new_email).to.eq('')

    if (state === 'subscribed') {
      expect(sub.initial_status).to.eq('active')
      expect(sub.custom_fields.accepts_marketing).to.eq('yes')
      expect(sub.status).to.eq('active')
    } else {
      expect(sub.initial_status).to.eq('unsubscribed')
      expect(sub.custom_fields.accepts_marketing).to.eq('no')
      expect(sub.status).to.be.undefined
    }

    expect(sub.custom_fields.birthday).to.be.null
    expect(sub.custom_fields.first_name).to.eq('Test')
    expect(sub.custom_fields.gender).to.eq('')
    expect(sub.custom_fields.last_name).to.eq('User')
    expect(sub.custom_fields.magento_customer_group).to.eq('General')
    expect(sub.custom_fields.magento_store).to.eq(1)
  })

  cy.log('Validating that the event calls have everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v2/123456/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const custCreatedBody = JSON.parse(recordedRequests[0].body.string)
    expect(custCreatedBody.events).to.have.lengthOf(1)
    expect(custCreatedBody.events[0].action).to.eq('Customer created')
    expect(custCreatedBody.events[0].email).to.eq('testuser@example.com')
    expect(custCreatedBody.events[0].properties.magento_source).to.eq('Storefront')
    expect(custCreatedBody.events[0].properties.source).to.eq('magento')
    expect(custCreatedBody.events[0].properties.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)

    const custLoggedInBody = JSON.parse(recordedRequests[1].body.string)
    expect(custLoggedInBody.events).to.have.lengthOf(1)
    expect(custLoggedInBody.events[0].action).to.eq('Customer logged in')
    expect(custLoggedInBody.events[0].email).to.eq('testuser@example.com')
    expect(custLoggedInBody.events[0].properties.magento_source).to.eq('Storefront')
    expect(custLoggedInBody.events[0].properties.source).to.eq('magento')
    expect(custLoggedInBody.events[0].properties.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
  })
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

Then('A {string} event should be sent to Drip', function(state) {
  cy.log('Validating that the subscriber call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v2/123456/subscribers'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.subscribers).to.have.lengthOf(1)

    const sub = body.subscribers[0]
    expect(sub.email).to.eq('testuser@example.com')
    expect(sub.new_email).to.eq('')

    if (state === 'subscribed') {
      expect(sub.initial_status).to.eq('active')
      expect(sub.custom_fields.accepts_marketing).to.eq('yes')
      expect(sub.status).to.eq('active')
    } else {
      expect(sub.initial_status).to.eq('unsubscribed')
      expect(sub.custom_fields.accepts_marketing).to.eq('no')
      expect(sub.status).to.eq('unsubscribed')
    }

    expect(sub.custom_fields.birthday).to.be.null
    expect(sub.custom_fields.first_name).to.eq('Test')
    expect(sub.custom_fields.gender).to.eq('')
    expect(sub.custom_fields.last_name).to.eq('User')
    expect(sub.custom_fields.magento_customer_group).to.eq('General')
    expect(sub.custom_fields.magento_store).to.eq(1)
  })

  cy.log('Validating that the event calls have everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v2/123456/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const custCreatedBody = JSON.parse(recordedRequests[0].body.string)
    expect(custCreatedBody.events).to.have.lengthOf(1)
    expect(custCreatedBody.events[0].action).to.eq('Customer updated')
    expect(custCreatedBody.events[0].email).to.eq('testuser@example.com')
    expect(custCreatedBody.events[0].properties.magento_source).to.eq('Storefront')
    expect(custCreatedBody.events[0].properties.source).to.eq('magento')
    expect(custCreatedBody.events[0].properties.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
  })
})

Then('A {string} {string} event should be sent to the WIS', function(subject, action) {
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/123456/integrations/abcdefg/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests.find((elm) => {
      let body = JSON.parse(elm.body.string);
      let actionMatch = body.action == action;
      let subjectMatch = body.subject == subject;
      let customerIdMatch = body.customer_id && /^\d+$/.test(body.customer_id);
      let emailMatch = body.email && body.email == 'testuser@example.com';
      return actionMatch && subjectMatch && (customerIdMatch || emailMatch);
    })).to.exist
  })
})
