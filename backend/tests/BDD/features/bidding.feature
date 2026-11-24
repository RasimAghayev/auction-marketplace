Feature: Bidding Process
  As a bidder
  I want to place bids on active auctions
  So that I can win items I'm interested in

  Background:
    Given I am authenticated as a bidder
    And there is an active auction "iPhone 15 Pro" with current price 500 USD
    And the minimum bid increment is 25 USD
    And the auction ends in 1 hour

  Scenario: Bidder places a valid bid
    Given I am on the auction page for "iPhone 15 Pro"
    When I enter bid amount "550" and click "Place Bid"
    Then my bid should be accepted
    And the auction current price should update to 550 USD
    And I should be shown as the current highest bidder
    And a notification should be sent to the previous highest bidder

  Scenario: Bidder places bid below minimum increment
    Given the current price is 500 USD with minimum increment 25 USD
    When I enter bid amount "510" and click "Place Bid"
    Then my bid should be rejected
    And I should see an error "Bid must be at least 525 USD"
    And the auction current price should remain 500 USD

  Scenario: Bidder places bid equal to current price
    Given the current price is 500 USD
    When I enter bid amount "500" and click "Place Bid"
    Then my bid should be rejected
    And I should see an error "Bid must be higher than current price"

  Scenario: Bidder cannot bid on their own auction
    Given I am the seller of the auction "iPhone 15 Pro"
    When I attempt to place a bid on my own auction
    Then I should see an error "Cannot bid on your own auction"

  Scenario: Bidder cannot bid on ended auction
    Given the auction "iPhone 15 Pro" has ended
    When I attempt to place a bid
    Then I should see an error "Auction has ended"

  Scenario: Bidder cannot bid on unscheduled auction
    Given the auction "iPhone 15 Pro" is in "Scheduled" status
    When I attempt to place a bid
    Then I should see an error "Auction has not started yet"

  Scenario: Bidder places winning bid at auction end
    Given the auction ends in 30 seconds
    And I am the current highest bidder at 600 USD
    When the auction ends
    And my bid is above the reserve price
    Then I should be declared the winner
    And I should receive a "You won!" notification
    And the seller should receive a "Auction completed" notification

  Scenario: Bidder is outbid and receives notification
    Given I am the current highest bidder at 550 USD
    When another bidder places a bid of 575 USD
    Then I should receive an "You've been outbid" notification
    And I should see the new current price of 575 USD

  Scenario: Bidder watches auction without bidding
    Given I am on the auction page for "iPhone 15 Pro"
    When I click the "Watch" button
    Then I should be added to the watchers list
    And I should receive notifications for new bids
    And the auction should appear in my "Watched Auctions" list

  Scenario: Bidder views bid history
    Given I am on the auction page for "iPhone 15 Pro"
    And there have been 5 bids placed
    When I view the bid history
    Then I should see all bids with amounts and timestamps
    And I should see which bids were winning at the time