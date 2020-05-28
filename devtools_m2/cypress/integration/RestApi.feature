Feature: REST API Interactions

  I want to make plugin settings available via the REST API

  Scenario: Authorized request for order_url
    Given I am logged into the admin interface
      And I have set up Drip via the API
      And a customer exists for website 'main'
      And I have configured a simple widget for 'main'
    When I create an order for a 'simple' widget
    Then an authorized order details request gives the correct response

  Scenario: Authorized request to set integration token
    Given I am logged into the admin interface
    Then an authorized integration request gives the correct response

  Scenario: Authorized request to set integration token for default scope
    Given I am logged into the admin interface
    Then an authorized integration request with no websiteId gives the correct response

  Scenario: Authorized request to set integration token for non-existent website
    Given I am logged into the admin interface
    Then an authorized integration request for a non-existent site gives the correct response

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
