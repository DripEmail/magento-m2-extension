import { mockServerClient } from "mockserver-client"

beforeEach(function() {
  cy.log('resetting mocks')
  cy.then(function() {
    return mockServerClient("localhost", 1080).reset()
  })
})
