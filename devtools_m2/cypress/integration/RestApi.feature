Feature: REST API Interactions

  I want to make plugin settings available via the REST API

  Scenario: Request for account_id
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
    Then a REST API request gives the correct response
