import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Then('an order event is sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('jd1@example.com')
    expect(body.grand_total).to.eq(16.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    expect(body.currency).to.eq('USD')
    expect(body.magento_source).to.eq('Admin')
    expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(body.order_id).to.eq('000000001')
    expect(body.order_public_id).to.eq('000000001')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.total_taxes).to.eq(0)
    expect(body.version).to.match(/^Magento 2\.3\.2, Drip Extension \d+\.\d+\.\d+$/)

    expect(body.billing_address.address_1).to.eq('123 Main St.')
    expect(body.billing_address.address_2).to.eq('')
    expect(body.billing_address.city).to.eq('Centerville')
    expect(body.billing_address.company).to.eq('')
    expect(body.billing_address.country).to.eq('US')
    expect(body.billing_address.first_name).to.eq('John')
    expect(body.billing_address.last_name).to.eq('Doe')
    expect(body.billing_address.phone).to.eq('999-999-9999')
    expect(body.billing_address.postal_code).to.eq('12345')
    expect(body.billing_address.state).to.eq('Minnesota')

    expect(body.shipping_address.address_1).to.eq('123 Main St.')
    expect(body.shipping_address.address_2).to.eq('')
    expect(body.shipping_address.city).to.eq('Centerville')
    expect(body.shipping_address.company).to.eq('')
    expect(body.shipping_address.country).to.eq('US')
    expect(body.shipping_address.first_name).to.eq('John')
    expect(body.shipping_address.last_name).to.eq('Doe')
    expect(body.shipping_address.phone).to.eq('999-999-9999')
    expect(body.shipping_address.postal_code).to.eq('12345')
    expect(body.shipping_address.state).to.eq('Minnesota')

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq('http://main.magento.localhost:3006/media/catalog/product/')
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
