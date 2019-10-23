Cypress.Commands.add("createProduct", (desc) => {
  // You must provide at least the following:
  // "sku"
  // "name"
  // "description"
  // "shortDescription"

  cy.log('Creating magento product')
  const str = JSON.stringify(desc)
  cy.exec(`echo '${str}' | ./docker_compose.sh exec -u www-data -T web bin/magento drip_testutils:createproduct`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})
