Feature: Order Batch Sync

  I want to send all orders to Drip.

  Scenario: An admin syncs an order
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'default'
      And a customer exists
      And I have configured a configurable widget
    When I create an order
      And I click order sync
    Then an order event is sent to Drip
