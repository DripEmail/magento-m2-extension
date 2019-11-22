Cypress.Commands.add("flushCaches", () => {
  cy.log('flushing Magento caches')
  cy.exec('DRIP_COMPOSE_ENV=test ./flush_caches.sh')
})

beforeEach(function() {
  cy.log('resetting docker for test')
  cy.exec('DRIP_COMPOSE_ENV=test ./reset.sh')
})
