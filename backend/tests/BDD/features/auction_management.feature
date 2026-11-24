Feature: Auction Creation and Management
  As a seller
  I want to create and manage auctions for my products
  So that buyers can bid on them

  Background:
    Given I am authenticated as a seller
    And I have a published product "iPhone 15 Pro" with starting price 500 USD

  Scenario: Seller creates a new auction
    Given I am on the create auction page for "iPhone 15 Pro"
    When I fill in the auction form with:
      | starting_price | min_bid_increment | reserve_price | start_date           | end_date             |
      | 500            | 25                | 800           | 2026-01-15 10:00:00  | 2026-01-22 10:00:00  |
    And I submit the form
    Then an auction should be created with status "Scheduled"
    And the product status should change to "InAuction"
    And I should be redirected to the auction details page

  Scenario: Seller cannot create auction with past end date
    Given I am on the create auction page for "iPhone 15 Pro"
    When I fill in the auction form with end date in the past
    And I submit the form
    Then I should see a validation error "End date must be in the future"

  Scenario: Seller cannot create auction with reserve price below starting price
    Given I am on the create auction page for "iPhone 15 Pro"
    When I fill in the auction form with reserve_price 400 and starting_price 500
    And I submit the form
    Then I should see a validation error "Reserve price must be greater than starting price"

  Scenario: Seller cancels a scheduled auction
    Given I have a scheduled auction for "iPhone 15 Pro" starting tomorrow
    When I click the "Cancel Auction" button
    And I confirm the cancellation
    Then the auction status should change to "Cancelled"
    And the product status should change back to "Published"

  Scenario: Seller cannot cancel an active auction
    Given I have an active auction for "iPhone 15 Pro"
    When I attempt to cancel the auction
    Then I should see an error "Cannot cancel active auction"
    And the auction status should remain "Active"

  Scenario: Auction automatically starts at scheduled time
    Given I have a scheduled auction starting in 1 minute
    When the start time is reached
    Then the auction status should automatically change to "Active"
    And bidders should be able to place bids

  Scenario: Auction automatically ends at scheduled time
    Given I have an active auction ending in 1 minute
    And the current highest bid is 750 USD (above reserve)
    When the end time is reached
    Then the auction status should change to "Completed"
    And the highest bidder should be declared the winner
    And the winner should be notified

  Scenario: Auction ends unsold if reserve not met
    Given I have an active auction ending in 1 minute
    And the current highest bid is 600 USD (below reserve of 800)
    When the end time is reached
    Then the auction status should change to "Unsold"
    And no winner should be declared
    And the seller should be notified

  Scenario: Auto-extend auction when bid placed near end
    Given I have an active auction ending in 30 seconds
    And the auto-extend threshold is 60 seconds
    When a bid is placed 20 seconds before end
    Then the auction end time should be extended by 5 minutes
    And all participants should be notified of the extension