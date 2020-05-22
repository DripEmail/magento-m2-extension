Feature: REST API Interactions

  I want to make plugin settings available via the REST API

  Scenario: Authorized request set integration token
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
    Then an authorized REST API request gives the correct response

  Scenario: Unauthorized request to set integration token
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
    Then an unauthorized REST API request gives the correct response
