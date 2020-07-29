Feature: Customer Cart Interactions

  I want to send cart and order events to Drip when a customer interacts with their cart.

  Scenario: A customer adds a simple product to their cart
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'main'
    Given I have configured a simple widget for 'main'
    When I open the 'main' homepage
      And I create an account
      And I add a 'simple' widget to my cart
    Then A simple cart event should be sent to Drip
    When I check out
    Then An order event should be sent to Drip

  Scenario: A customer adds a configurable product to their cart
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'main'
    Given I have configured a configurable widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'configurable' widget to my cart
    Then A configurable cart event should be sent to Drip
    When I check out
    Then An order event should be sent to Drip

  Scenario: A customer adds a simple product to their cart, and checks out as a guest.
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'main'
      And I have configured a simple widget for 'main'
    When I open the 'main' homepage
      And I logout
      And I add a 'simple' widget to my cart
    Then A simple cart event should be sent to Drip
    When I check out as a guest
    Then An order event should be sent to Drip
