import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I create a simple product', function () {
  cy.contains('Products').click({ force: true })
  cy.wait(300)
  cy.contains('Add Product').click({ force: true })

  cy.get('input[name="product[name]"]').type('Tropical Plant')
  cy.get('input[name="product[sku]"]').clear().type('TROP')
  cy.get('input[name="product[price]"]').type('45.00')
  cy.get('input[name="product[quantity_and_stock_status][qty]"]').type('200')
  cy.contains('Save').click({ force:true })

  cy.contains('You saved the product.')
})

When('I update the simple widget', function() {
  cy.contains('Products').click({ force: true })
  cy.wait(500)
  cy.contains('Edit').click({ force: true })

  cy.get('input[name="product[price]"]').clear().type('500.00')
  cy.contains('Save').click({ force:true })

  cy.contains('You saved the product.')
})

When('I delete the simple widget', function() {
  cy.contains('Products').click({ force: true })
  cy.get('input[type="checkbox"]').check({ force: true })
  cy.get('div[class="action-menu-items"]').contains('Delete').click({ force: true })

  cy.contains('OK').click()

  cy.contains('A total of 1 record(s) have been deleted.')
})

Then('a product {string} event is sent to the WIS', function(action) {
  cy.log('Validating that the product call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    // path: "/123456/integrations/abcdefg/events",
    // body: {
    //   "type": "JSON_PATH",
    //   "jsonPath": "$[?(@.product_id)]"
    // }
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq(action)
    expect(body.product_id).to.eq('1')
  })
})