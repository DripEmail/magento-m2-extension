Feature: Customer Cart Interactions

  I want to send cart events to Drip when a customer interacts with their cart.

  Scenario: When a customer adds something to their cart
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for main
      And I have configured a widget
    When I open the main homepage
      And I create an account
      And I add something to my cart
    Then A cart event should be sent to Drip
