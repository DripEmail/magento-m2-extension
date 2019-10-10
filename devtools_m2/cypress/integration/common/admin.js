import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

Given('I am logged into the admin interface', function() {
  cy.visit(`http://main.magento.localhost:3006/admin_123`)
  cy.get('input[name="login[username]"]').type('admin')
  cy.get('input[name="login[password]"]').type('abc1234567890')
  cy.contains('Sign in').click()
})

Given('I have set up a multi-store configuration', function() {
  cy.contains('All Stores').click({ force: true })

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

  // cy.contains('Stores').click()
  cy.get('[data-ui-id="menu-magento-backend-stores-settings"]').within(function() {
    // Forcing since the sidebar opens sporadically at best.
    cy.contains('Configuration').click({force: true})
  })
  cy.wait(1000) // Some JS has to run before we can successfully click this.
  cy.get('div.store-switcher').within(function() {
    // cy.get('button#store-change-button').click({force: true})
    cy.contains('site1_website').trigger('click', {force: true})
  })
  cy.contains('OK').click()
  cy.get('#system_config_tabs').within(function() {
    cy.contains('Web').click()
  })
  cy.contains('Base URLs').click()
  cy.get('[name="groups[unsecure][fields][base_url][inherit]"]').uncheck()
  cy.get('[name="groups[unsecure][fields][base_url][value]"]').clear().type(`http://site1.magento.localhost:3006/`)
  cy.get('[name="groups[unsecure][fields][base_link_url][inherit]"]').uncheck()
  cy.get('[name="groups[unsecure][fields][base_link_url][value]"]').clear().type(`http://site1.magento.localhost:3006/`)
  cy.contains('Save Config').click()
})

Given('I have configured Drip to be enabled for {string}', function(site) {
  cy.get('li[data-ui-id="menu-magento-config-system-config"] a').click({force: true})
  cy.contains('Drip Connect', {timeout: 20000}).click({ force: true })
  let websiteKey
  if (site == 'main') {
    websiteKey = 'Main Website'
  } else {
    websiteKey = `${site}_website`
  }
  cy.get('div.store-switcher').within(function() {
    cy.contains(websiteKey).trigger('click', {force: true})
  })
  cy.contains('OK').click()
  cy.contains('Module Settings').click()
  cy.contains('API Settings').click()
  cy.get('input[name="groups[module_settings][fields][is_enabled][inherit]"]').uncheck()
  cy.get('select[name="groups[module_settings][fields][is_enabled][value]"]').select('Yes')
  cy.get('input[name="groups[api_settings][fields][account_id][inherit]"]').uncheck()
  cy.get('input[name="groups[api_settings][fields][account_id][value]"]').type('123456')
  cy.get('input[name="groups[api_settings][fields][api_key][inherit]"]').uncheck()
  cy.get('input[name="groups[api_settings][fields][api_key][value]"]').type('abc123')
  cy.get('input[name="groups[api_settings][fields][url][inherit]"]').uncheck()
  cy.get('input[name="groups[api_settings][fields][url][value]"]').clear().type('http://mock:1080/v2/')
  cy.contains('Save Config').click()
})

Given('I have configured a widget', function() {
  cy.get('[data-ui-id="menu-magento-catalog-catalog-products"] a').click({ force: true })
  // TODO: This is not good. Find a better way to know when the product listing page has finished loading.
  cy.wait(4000)
  cy.get('#add_new_product-button').click()
  cy.get('input[name="product[name]"]', {timeout: 10000}).type('Widget 1')
  cy.get('input[name="product[sku]"]').clear().type('widg-1')
  cy.get('input[name="product[price]"]').type('120')
  cy.get('input[name="product[quantity_and_stock_status][qty]"]').type('120')
  cy.contains('Save').click()
})
