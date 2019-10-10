import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

When('I open the {string} homepage', function(site) {
  cy.visit(`http://${site}.magento.localhost:3006/`)
})
