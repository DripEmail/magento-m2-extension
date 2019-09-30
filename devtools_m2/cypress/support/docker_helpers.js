beforeEach(function() {
  cy.log('resetting docker for test')
  cy.exec('DRIP_COMPOSE_ENV=test ./reset.sh')
})
