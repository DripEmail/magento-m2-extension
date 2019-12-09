Feature: Order Batch Sync

  I want to send all orders to Drip.

  Scenario: An admin syncs an order
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'default'
      And a customer exists for website 'main'
      And I have configured a simple widget for 'main'
    When I create an order
      And I click order sync
    Then an order event is sent to Drip

  Scenario: An admin syncs an order with a multi-store configuration
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'main'
      And a customer exists for website 'main'
      And a different customer exists for website 'site1'
      And I have configured a simple widget for 'main'
      And I have configured a different simple widget for 'site1'
      And I create an order for 'main'
      And I create an order for 'site1'
    When I click order sync
    Then an order event is sent to Drip
