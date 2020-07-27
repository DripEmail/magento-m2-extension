Feature: Customer Subscription Interactions

  I want to send login and subscription change events

  @focus
  Scenario: A customer creates a subscribed account and then unsubscribes
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'main'
    When I open the 'main' homepage
      And I create a 'subscribed' account
    Then A 'updated' event should be sent to the WIS
    When I 'unsubscribe' from the general newsletter
    Then A 'updated' event should be sent to the WIS

  Scenario: A customer creates an unsubscribed account and then subscribes
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'main'
    When I open the 'main' homepage
      And I create a 'unsubscribed' account
    Then A 'updated' event should be sent to the WIS
    When I 'subscribe' from the general newsletter
    Then A 'updated' event should be sent to the WIS

  Scenario: A person subscribes from the homepage
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'main'
      And I have disabled email communications
    When I open the 'main' homepage
      And I subscribe on the homepage
    Then A 'created' event should be sent to the WIS

  Scenario: A customer creates a subscribed account and then unsubscribes when not configured for Drip
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'main'
    When I open the 'site1' homepage
      And I create a 'subscribed' account
    Then No web requests are sent
    When I 'unsubscribe' from the general newsletter
    Then No web requests are sent

  Scenario: A customer creates an unsubscribed account and then subscribes when not configured for Drip
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'main'
    When I open the 'site1' homepage
      And I create a 'unsubscribed' account
    Then No web requests are sent
    When I 'subscribe' from the general newsletter
    Then No web requests are sent
