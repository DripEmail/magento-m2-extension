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

Cypress.Commands.add("createCustomer", (desc) => {
  cy.log('Creating magento customer')
  const str = JSON.stringify(desc)
  cy.exec(`echo '${str}' | ./docker_compose.sh exec -u www-data -T web bin/magento drip_testutils:createcustomer`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("createScopes", (desc) => {
  cy.log('Creating magento scopes')
  const str = JSON.stringify(desc)
  cy.exec(`echo '${str}' | ./docker_compose.sh exec -u www-data -T web bin/magento drip_testutils:createscopes`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("setConfig", (desc) => {
  cy.log('Setting Magento config')

  let cmd = 'bin/magento config:set'
  if (desc['scope']) {
    cmd += ` --scope="${desc['scope']}"`
  }
  if (desc['scopeCode']) {
    cmd += ` --scope-code="${desc['scopeCode']}"`
  }
  cmd += ` "${desc['path']}" "${desc['value']}"`

  cy.exec(`./docker_compose.sh exec -u www-data -T web ${cmd}`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("runCron", (desc) => {
  cy.log('Running Magento Cron')
  const str = JSON.stringify(desc)
  cy.exec(`./cron.sh`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})
