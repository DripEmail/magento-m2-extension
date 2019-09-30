import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function() {
  cy.contains('Create an Account').click()
  cy.get('#form-validate').within(function() {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('testuser@example.com')
    cy.get('input[name="password"]').type('blahblah123!!!')
    cy.get('input[name="password_confirmation"]').type('blahblah123!!!')
    cy.contains('Create an Account').click()
  })
})

When('I add something to my cart', function() {
  cy.server()
  cy.route('POST', 'checkout/cart/add/**').as('addToCartRequest')
  cy.visit(`/widget-1.html`)
  cy.contains('Add to Cart').click()
  cy.wait('@addToCartRequest')
})

Then('A cart event should be sent to Drip', function() {
  cy.log('Validating subscriber mocks were called')
  cy.then(function() {
    return Mockclient.verify({
      'path': '/v2/123456/subscribers'
    }, 1, 1);
  })
  cy.log('Validating event mocks were called')
  cy.then(function() {
    return Mockclient.verify({
      'path': '/v2/123456/events'
    }, 2, 2);
  })
  cy.log('Validating cart mock was called')
  cy.then(function() {
    return Mockclient.verify({
      'path': '/v3/123456/shopper_activity/cart'
    }, 1, 1);
  })
  cy.log('Validating that the cart call has everything we need')
  cy.then(function() {
    let mockPromise = Mockclient.retrieveRecordedRequests({
      'path': '/v3/123456/shopper_activity/cart'
    })

    return new Promise((resolve, reject) => {
      mockPromise.then(function(recordedRequests) {
        try {
          const body = JSON.parse(recordedRequests[0].body.string)
          if (
            body.email == 'testuser@example.com' &&
            body.product_variant_id == '1234'
          ) {
            resolve()
          } else {
            reject('missing a param')
          }
        } catch(err) {
          reject(err)
        }
      }, reject);
    })
  })
})
