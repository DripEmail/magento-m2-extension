Feature: Customer Identification

  I want to identify customers when they enter their email address

  Scenario: When a site has Client.js enabled
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'main'
    When I open the 'main' homepage
      And I create an account
    Then an identify call is made to Drip
