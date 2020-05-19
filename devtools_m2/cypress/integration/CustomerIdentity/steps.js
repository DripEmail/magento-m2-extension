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

Then('an identify call is made to Drip', function() {
  cy.log('Validating that an identify call was made')
  cy.get('script').should((scriptTags) => {
    const identifyTags = scriptTags.toArray().filter(function(tag) {
      return tag.src.match(/https:\/\/api.getdrip.com\/client\/identify\?time_zone=[a-zA-Z0-9_%]+&visitor_uuid=\w+&email=testuser%40example.com&drip_account_id=123456&callback=/)
    })
    expect(identifyTags).to.not.be.empty
  })
})
