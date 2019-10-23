// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This is will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })

afterEach(function() {
  // https://github.com/cypress-io/cypress/issues/216
  // https://github.com/cypress-io/cypress/issues/686
  cy.log('Reset web interface to avoid bleed-over')
  cy.visit('/lib/web/blank.html', { failOnStatusCode: false })
})

beforeEach(function() {
  // Let's just start the stupid thing.
  cy.server()
})

Cypress.Commands.add("switchAdminContext", (site) => {
  let websiteKey
  switch (site) {
    case 'main':
      websiteKey = 'Main Website'
      break;
    case 'default':
      websiteKey = 'Default Config'
      break;
    default:
      websiteKey = `${site}_website`
      break;
  }
  cy.get('div.store-switcher ul[data-role="stores-list"]').contains(websiteKey).click({force: true})
  cy.get('div.store-switcher ul[data-role="stores-list"]').contains(websiteKey).then(function(link) {
    // We assume that if the link is disabled, we're already in that context.
    if (!link.parent().hasClass('disabled')) {
      cy.contains('OK').click()
    }
  })
})
