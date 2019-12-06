import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I create a {string} user in the admin', function(site) {
  cy.contains('All Customers').click({force: true})

  cy.contains('Add New Customer').click()

  cy.get('select[name="customer[website_id]"]').select(site)
  cy.get('input[name="customer[firstname]"]').type('Test')
  cy.get('input[name="customer[lastname]"]').type('User')
  cy.get('input[name="customer[email]"]').type('testuser@example.com')
  // cy.get('input[type="text"][name="customer[password]"]').type('blahblah')
  let storeView;
  switch (site) {
    case 'site1_website':
      storeView = 'site1_store_view'
      break;
    default:
      throw 'Unsupported site'
  }
  cy.get('select[name="customer[sendemail_store_id]"]').select(storeView)
  cy.contains('Save Customer').click()

  cy.contains('You saved the customer')
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
    expect(sub.custom_fields.magento_store).to.eq(300)
  })

  cy.log('Validating that the event calls have everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v2/123456/events'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const custCreatedBody = JSON.parse(recordedRequests[0].body.string)
    expect(custCreatedBody.events).to.have.lengthOf(1)
    expect(custCreatedBody.events[0].action).to.eq('Customer created')
    expect(custCreatedBody.events[0].email).to.eq('testuser@example.com')
    expect(custCreatedBody.events[0].properties.magento_source).to.eq('Admin')
    expect(custCreatedBody.events[0].properties.source).to.eq('magento')
    expect(custCreatedBody.events[0].properties.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
  })
})

When('An admin subscribes to the general newsletter', function() {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.contains('testuser@example.com', {timeout: 30000})
  cy.get('.data-grid-actions-cell').contains('Edit', {timeout: 30000}).click()
  cy.get('.admin__page-nav').contains('Newsletter').click()
  cy.get('input[name="subscription"]').check()
  cy.contains('Save Customer').click()

  cy.contains('You saved the customer')
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
    expect(sub.custom_fields.magento_store).to.eq(300)
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
    expect(custCreatedBody.events[0].properties.magento_source).to.eq('Admin')
    expect(custCreatedBody.events[0].properties.source).to.eq('magento')
    expect(custCreatedBody.events[0].properties.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)
  })
})
