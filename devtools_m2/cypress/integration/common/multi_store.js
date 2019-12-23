import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { setCurrentFrontendSite, getCurrentFrontendDomain } from "../../lib/frontend_context"

When('I open the {string} homepage', function(site) {
  setCurrentFrontendSite(site)
  cy.visit(getCurrentFrontendDomain(), {timeout: 15000})
})
