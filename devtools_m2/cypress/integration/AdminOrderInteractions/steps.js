import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Then('an order event is sent to Drip for the {string} widget', function(widgetType) {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    path: "/123456/integrations/abcdefg/events",
    body: {
      "type": "JSON_PATH",
      "jsonPath": "$[?(@.order_id)]"
    }
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    expect(recordedRequests[0].headers["X-Drip-Connect-Plugin-Version"]).to.exist
    const body = recordedRequests[0].body.json
    expect(body.action).to.eq('placed')
    expect(body.order_id).to.eq('000000001')

    if(isVirtualProduct(widgetType)) {
      expect(Object.keys(body)).to.not.contain('shipping_address')
      expect(body.total_shipping).to.eq(0)
    }
  })
})

const isVirtualProduct = function(type) {
  return type === 'virtual'
}
