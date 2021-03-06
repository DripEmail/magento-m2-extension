Feature: Product Interactions

  I want to send all product events to Drip.

  Scenario: An admin creates a simple product
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'default'
    When I create a simple product
    Then a product 'created' event is sent to the WIS

  Scenario: An admin creates a simple product with a multi-store configuration
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have set up Drip via the API for 'default'
    When I create a simple product
    Then a product 'created' event is sent to the WIS

  Scenario: An admin updates a simple product
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'default'
      And I have configured a simple widget for 'main'
      And previous product webhooks have already fired
    When I update the simple widget
    Then a product 'updated' event is sent to the WIS

  Scenario: An admin updates a simple product in a non-default site
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'main'
      And I have configured a simple widget for 'main'
    Then a product 'created' event is sent to the WIS
      And previous product webhooks have already fired
    When I update the simple widget
    Then a product 'updated' event is sent to the WIS

  Scenario: An admin deletes a simple product
    Given I am logged into the admin interface
      And I have set up Drip via the API for 'default'
      And I have configured a simple widget for 'main'
      And previous product webhooks have already fired
    When I delete the simple widget
    Then a product 'deleted' event is sent to the WIS
