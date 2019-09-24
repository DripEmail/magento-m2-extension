import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

Given('I am logged into the admin interface', function() {
  cy.visit(`http://main.magento.localhost:3006/admin_123`)
  cy.get('input[name="login[username]"]').type('admin')
  cy.get('input[name="login[password]"]').type('abc1234567890')
  cy.contains('Sign in').click()
})

Given('I have set up a multi-store configuration', function() {
  cy.contains('Stores').click()
  cy.contains('All Stores').click()

  cy.contains('Create Website').click()
  cy.get('input[name="website[name]"]').type('site1_website')
  cy.get('input[name="website[code]"]').type('site1_website')
  cy.contains('Save Web Site').click()

  cy.get('button#add_group').click()
  cy.get('select[name="group[website_id]"]').select('site1_website')
  cy.get('input[name="group[name]"]').type('site1_store')
  cy.get('input[name="group[code]"]').type('site1_store')
  cy.get('select[name="group[root_category_id]"]').select('Default Category')
  cy.contains('Save Store').click()

  cy.contains('Create Store View').click()
  cy.get('select[name="store[group_id]"]').select('site1_store')
  cy.get('input[name="store[name]"]').type('site1_store_view')
  cy.get('input[name="store[code]"]').type('site1_store_view')
  cy.get('select[name="store[is_active]"]').select('Enabled')
  cy.contains('Save Store View').click()
  cy.contains('OK').click()

  cy.contains('Stores').click()
  cy.get('[data-ui-id="menu-magento-backend-stores-settings"]').within(function() {
    cy.contains('Configuration').click()
  })
  cy.wait(1000) // Some JS has to run before we can successfully click this.
  cy.get('div.store-switcher').within(function() {
    // cy.get('button#store-change-button').click({force: true})
    cy.contains('site1_website').trigger('click', {force: true})
  })
  cy.contains('OK').click()
  // There's a race condition of sorts here...
  // cy.wait(2000) // Some JS has to run before we can successfully click this.
  cy.contains('General').click()
  cy.contains('Web').click()
  cy.contains('Base URLs').click()
  cy.get('groups[unsecure][fields][base_url][inherit]"]').uncheck()
  cy.get('groups[unsecure][fields][base_url][value]').clear().type(`http://site1.magento.localhost:3006/`)
  cy.get('groups[unsecure][fields][base_link_url][inherit]"]').uncheck()
  cy.get('groups[unsecure][fields][base_link_url][value]').clear().type(`http://site1.magento.localhost:3006/`)
  cy.contains('Save Config').click()
})

// Given('I have configured Drip to be enabled for site1', function() {
//   cy.contains('System').trigger('mouseover')
//   cy.contains('Configuration').click()
//   cy.contains('Drip Connect Configuration').click()
//   cy.get('select#store_switcher').select('site1_website')
//   cy.contains('Module Settings').click()
//   cy.contains('API Settings').click()
//   cy.get('input[name="groups[module_settings][fields][is_enabled][inherit]"]').uncheck()
//   cy.get('select[name="groups[module_settings][fields][is_enabled][value]"]').select('Yes')
//   cy.get('input[name="groups[api_settings][fields][account_id][inherit]"]').uncheck()
//   cy.get('input[name="groups[api_settings][fields][account_id][value]"]').type('123456')
//   cy.get('input[name="groups[api_settings][fields][api_key][inherit]"]').uncheck()
//   cy.get('input[name="groups[api_settings][fields][api_key][value]"]').type('abc123')
//   cy.contains('Save Config').click()
// })

// When('I open the site1 homepage', function() {
//   cy.visit(`http://site1.magento.localhost:3006/`)
// })

// When('I open the main homepage', function() {
//   cy.visit(`http://main.magento.localhost:3006/`)
// })

// Then('clientjs is inserted', function() {
//   cy.window().then(function(win) {
//     expect(win._dcq).to.not.be.undefined
//   })
// })

// Then('clientjs is not inserted', function() {
//   cy.window().then(function(win) {
//     expect(win._dcq).to.be.undefined
//   })
// })
