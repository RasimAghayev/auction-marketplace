Feature: Product Management
  As a seller
  I want to manage my products
  So that I can list them for auction

  Background:
    Given I am authenticated as a seller
    And a category "Electronics" exists

  Scenario: Seller creates a new product
    Given I am on the create product page
    When I fill in the product form with valid data:
      | title           | description       | category    | condition | starting_price | reserve_price | currency |
      | iPhone 15 Pro   | Latest iPhone     | Electronics | New       | 500            | 800           | USD      |
    And I upload product images
    And I submit the form
    Then the product should be created with status "Draft"
    And I should be redirected to the product details page

  Scenario: Seller cannot create product without required fields
    Given I am on the create product page
    When I submit the form without filling required fields
    Then I should see validation errors for:
      | title           |
      | description     |
      | category        |
      | condition       |
      | starting_price  |

  Scenario: Seller publishes a draft product
    Given I have a draft product "iPhone 15 Pro"
    When I click the "Publish" button
    Then the product status should change to "Published"
    And the product should be visible in the marketplace

  Scenario: Seller cannot publish product without images
    Given I have a draft product without images
    When I click the "Publish" button
    Then I should see an error "At least one image is required"
    And the product status should remain "Draft"

  Scenario: Seller edits their own product
    Given I have a draft product "iPhone 15 Pro"
    When I edit the product and change the title to "iPhone 15 Pro Max"
    And I save the changes
    Then the product title should be updated
    And the product should remain in "Draft" status

  Scenario: Seller cannot edit another seller's product
    Given another seller has a product "Samsung Galaxy"
    When I attempt to edit that product
    Then I should see a "Forbidden" error
    And the product should not be modified

  Scenario: Seller deletes their draft product
    Given I have a draft product "Old Phone"
    When I delete the product
    Then the product should be removed from the system
    And I should be redirected to my products list

  Scenario: Seller cannot delete published product
    Given I have a published product "iPhone 15 Pro"
    When I attempt to delete the product
    Then I should see an error "Cannot delete published product"
    And the product should remain in the system