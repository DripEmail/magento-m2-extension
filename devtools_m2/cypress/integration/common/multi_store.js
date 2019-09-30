import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

When('I open the site1 homepage', function() {
  cy.visit(`http://site1.magento.localhost:3006/`)
})

When('I open the main homepage', function() {
  cy.visit(`http://main.magento.localhost:3006/`)
})
