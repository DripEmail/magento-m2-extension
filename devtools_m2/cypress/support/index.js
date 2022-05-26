// ***********************************************************
// support/index.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

import chaiString from 'chai-string';
import 'cypress-pipe'

// Import commands.js using ES2015 syntax:
import './commands'
import './docker_helpers'
import './mocking_helpers'
import './product_management'

chai.use(chaiString)

// Alternatively you can use CommonJS syntax:
// require('./commands')

// We don't really care about Magento JS exceptions, since we aren't testing Magento JS code.
// The specific error that caused this: https://github.com/magento/magento2/issues/35325
Cypress.on('uncaught:exception', (err, runnable) => {
  // returning false here prevents Cypress from
  // failing the test
  return false
})
