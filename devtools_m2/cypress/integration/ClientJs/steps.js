import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

Then('clientjs is inserted', function() {
  cy.window().then(function(win) {
    expect(win._dcq).to.not.be.undefined
  })
})

Then('clientjs is not inserted', function() {
  cy.window().then(function(win) {
    expect(win._dcq).to.be.undefined
  })
})
