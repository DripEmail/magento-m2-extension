Feature: Customer Identification

  I want to identify customers when they enter their email address

  Scenario: When a site has Client.js enabled
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1'
    When I open the 'site1' homepage
      And I create an account
    Then an identify call is made to Drip
