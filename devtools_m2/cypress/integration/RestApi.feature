Feature: REST API Interactions

  I want to make plugin settings available via the REST API

  Scenario: Authorized request to set integration token
    Given I am logged into the admin interface
    Then an authorized integration request gives the correct response

  Scenario: Unauthorized request to set integration token
    Given I am logged into the admin interface
    Then an unauthorized integration request gives the correct response

    Scenario: Authorized request for status
      Given I am logged into the admin interface
        And I have set up Drip via the API
      Then an authorized status request gives the correct response

    Scenario: Unauthorized request for status
      Given I am logged into the admin interface
        And I have set up Drip via the API
      Then an unauthorized status request gives the correct response
