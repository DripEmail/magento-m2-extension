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
// Pending first-party support: https://github.com/cypress-io/cypress/issues/1313
import 'cypress-plugin-retries'

// Import commands.js using ES2015 syntax:
import './commands'
import './docker_helpers'
import './mocking_helpers'
import './product_management'

chai.use(chaiString)

// Alternatively you can use CommonJS syntax:
// require('./commands')
