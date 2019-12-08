
import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I click order sync', function() {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.get('li[data-ui-id="menu-magento-config-system-config"] a').click({force: true})
  cy.contains('Drip Connect', {timeout: 20000}).click({ force: true })
  cy.contains('Drip Actions').click()
  cy.contains('Sync All Orders To Drip').click({force: true})
  cy.contains('Queued')
  cy.runCron()
})

Then('an order event is sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order/batch'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.orders).to.have.lengthOf(1)
    const order = body.orders[0]
    expect(order.action).to.eq('placed')
    expect(order.email).to.eq('jd1@example.com')
    expect(order.grand_total).to.eq(16.22)
    expect(order.initial_status).to.eq('unsubscribed')
    expect(order.items_count).to.eq(1)
    expect(order.total_shipping).to.eq(5)
    expect(order.items).to.have.lengthOf(1)

    expect(order.currency).to.eq('USD')
    // TODO: This needs to be figured out.
    // expect(order.magento_source).to.eq('Admin')
    expect(order.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(order.order_id).to.eq('000000001')
    expect(order.order_public_id).to.eq('000000001')
    expect(order.provider).to.eq('magento')
    expect(order.total_discounts).to.eq(0)
    expect(order.total_taxes).to.eq(0)
    // TODO: Undecided as to whether I want to add this.
    // expect(order.version).to.match(/^Magento 1\.9\.4\.2, Drip Extension \d+\.\d+\.\d+$/)

    expect(order.billing_address.address_1).to.eq('123 Main St.')
    expect(order.billing_address.address_2).to.eq('')
    expect(order.billing_address.city).to.eq('Centerville')
    expect(order.billing_address.company).to.eq('')
    expect(order.billing_address.country).to.eq('US')
    expect(order.billing_address.first_name).to.eq('John')
    expect(order.billing_address.last_name).to.eq('Doe')
    expect(order.billing_address.phone).to.eq('999-999-9999')
    expect(order.billing_address.postal_code).to.eq('12345')
    expect(order.billing_address.state).to.eq('Minnesota')

    expect(order.shipping_address.address_1).to.eq('123 Main St.')
    expect(order.shipping_address.address_2).to.eq('')
    expect(order.shipping_address.city).to.eq('Centerville')
    expect(order.shipping_address.company).to.eq('')
    expect(order.shipping_address.country).to.eq('US')
    expect(order.shipping_address.first_name).to.eq('John')
    expect(order.shipping_address.last_name).to.eq('Doe')
    expect(order.shipping_address.phone).to.eq('999-999-9999')
    expect(order.shipping_address.postal_code).to.eq('12345')
    expect(order.shipping_address.state).to.eq('Minnesota')

    const item = order.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq('http://main.magento.localhost:3006/pub/media/catalog/product/')
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq('http://main.magento.localhost:3006/widget-1.html')
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})
