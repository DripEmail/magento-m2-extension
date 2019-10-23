Feature: Admin Order Interactions

  I want to send order events to Drip when an admin interacts with an order.

  Scenario: An admin creates an order
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'default'
      And a customer exists
      And I have configured a configurable widget
    When I create an order
    Then an order event is sent to Drip
