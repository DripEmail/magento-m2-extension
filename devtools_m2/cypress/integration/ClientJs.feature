Feature: Client.JS

  I want to have client.js inserted into the site

  Scenario: When a site has Client.js enabled
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for site1
    When I open the site1 homepage
    Then clientjs is inserted
    When I open the main homepage
    Then clientjs is not inserted
