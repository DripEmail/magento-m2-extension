Feature: Admin Order Interactions

  I want to send order events to Drip when an admin interacts with an order.

@focus
  Scenario: An admin creates an order
    Given I am logged into the admin interface
      And I have set up Drip via the API
      And a customer exists for website 'main'
      And I have configured a simple widget for 'main'
    When I create an order for a 'simple' widget
    Then an order event is sent to Drip for the 'simple' widget

  Scenario: An admin creates an order with only a virtual product
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'default'
      And a customer exists for website 'main'
      And I have configured a virtual widget for 'main'
    When I create an order for a 'virtual' widget
    Then an order event is sent to Drip for the 'virtual' widget
