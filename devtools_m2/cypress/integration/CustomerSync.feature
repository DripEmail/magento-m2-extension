Feature: Customer Batch Sync

  I want to send all customers to Drip.

  Scenario: An admin syncs a customer
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1'
      And a customer exists for website id '2'
      And a different customer exists for website id '1'
    When I click customer sync
    Then a customer is sent to Drip
