Feature: Customer Subscription Interactions

  I want to send login and subscription change events

  Scenario: A customer creates a subscribed account and then unsubscribes
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
    When I open the 'main' homepage
      And I create a 'subscribed' account
    Then A new 'subscribed' subscriber event should be sent to Drip
    When I 'unsubscribe' from the general newsletter
    Then A 'unsubscribed' event should be sent to Drip

  @focus
  Scenario: A customer creates an unsubscribed account and then subscribes
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
    When I open the 'main' homepage
      And I create a 'unsubscribed' account
    Then A new 'unsubscribed' subscriber event should be sent to Drip
    When I 'subscribe' from the general newsletter
    Then A 'subscribed' event should be sent to Drip
