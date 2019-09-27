import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

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
  cy.visit(`/widget-1.html`)
  // server.on({
  //   method: 'GET',
  //   path: '/resource',
  //   reply: {
  //     status:  200,
  //     headers: { "content-type": "application/json" },
  //     body:    JSON.stringify({ hello: "world" })
  //   }
  // });
  cy.contains('Add to Cart').click()
})
