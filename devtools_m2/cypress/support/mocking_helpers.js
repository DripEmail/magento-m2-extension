import { mockServerClient } from "mockserver-client"

// const Mockclient = mockServerClient("localhost", 1080);

// Cypress.Commands.add("mockAnyResponse", (params) => {
//   Mockclient.mockAnyResponse(params)
//   .then(
//     function () {
//       console.log("expectation created");
//     },
//     function (error) {
//       throw error;
//     }
//   )
// })

beforeEach(function() {
  cy.log('resetting mocks')
  cy.then(function() {
    return mockServerClient("localhost", 1080).reset()
  })
})
