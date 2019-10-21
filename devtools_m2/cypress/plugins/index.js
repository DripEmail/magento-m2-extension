// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

const cucumber = require('cypress-cucumber-preprocessor').default

module.exports = (on, config) => {
  // `on` is used to hook into various events Cypress emits
  // `config` is the resolved Cypress config

  on('file:preprocessor', cucumber())

  // Magento is slow. We're going to generally bump up timeouts
  config.defaultCommandTimeout = 10000 // Default: 4000
  // config.execTimeout = 60000 // Default: 60000
  // config.taskTimeout = 60000 // Default: 60000
  config.pageLoadTimeout = 100000 // Default: 60000
  config.requestTimeout = 10000 // Default: 5000
  config.responseTimeout = 10000 // Default: 3000
}
