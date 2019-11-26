Feature: Wishlist Interactions

  I want wishlist events sent to Drip when a customer changes their wishlist

  Scenario: Adding a product to the wishlist
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
      And I have configured a simple widget for 'main'
    When I open the 'main' homepage
      And I create an account
      And I add a 'simple' widget to my wishlist
    Then A wishlist 'add' event should be sent to Drip

  Scenario: Removing a wishlist item by using the trashcan
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
      And I have configured a simple widget for 'main'
      And I open the 'main' homepage
      And I create an account
      And I add a 'simple' widget to my wishlist
    When I remove the 'simple' widget from my wishlist
    Then A wishlist 'remove' event should be sent to Drip
