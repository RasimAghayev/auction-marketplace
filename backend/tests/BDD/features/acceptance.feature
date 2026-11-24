Feature: Complete Auction Lifecycle - Acceptance Tests
  As a marketplace operator
  I want to verify the complete auction lifecycle works end-to-end
  So that users can successfully buy and sell items

  @complete-lifecycle @atdd
  Scenario: Complete successful auction from listing to payment
    Given a seller "alice@example.com" exists
    And a bidder "bob@example.com" exists
    And a category "Electronics" exists

    # Seller creates product
    When "alice@example.com" creates a product:
      | title           | description       | category    | condition | starting_price | reserve_price |
      | iPhone 15 Pro   | Brand new iPhone  | Electronics | New       | 500            | 800           |
    And "alice@example.com" publishes the product

    # Seller creates auction
    And "alice@example.com" creates an auction for the product:
      | starting_price | min_increment | reserve_price | duration_hours |
      | 500            | 25            | 800           | 24           |

    # Bidder places bids
    And "bob@example.com" places a bid of 550 on the auction
    And "alice@example.com" (as another bidder) places a bid of 575
    And "bob@example.com" places a bid of 600

    # Auction ends
    And the auction ends (time travels to end)

    # Verify results
    Then the auction should be "Completed"
    And the winner should be "bob@example.com"
    And the winning bid should be 600
    And "bob@example.com" should receive "won" notification
    And "alice@example.com" should receive "auction completed" notification

  @complete-lifecycle @atdd
  Scenario: Auction ends unsold when reserve not met
    Given a seller "alice@example.com" exists
    And a bidder "bob@example.com" exists
    And a category "Electronics" exists

    When "alice@example.com" creates and publishes a product:
      | title           | starting_price | reserve_price |
      | Rare Coin       | 100            | 500           |
    And "alice@example.com" creates an auction:
      | starting_price | min_increment | reserve_price | duration_hours |
      | 100            | 10            | 500           | 1            |

    And "bob@example.com" places a bid of 200
    And the auction ends (time travels to end)

    Then the auction should be "Unsold"
    And there should be no winner
    And "alice@example.com" should receive "unsold" notification

  @complete-lifecycle @atdd
  Scenario: Auto-extend works when bid placed near end
    Given a seller "alice@example.com" exists
    And bidders "bob@example.com" and "charlie@example.com" exist
    And an active auction ending in 30 seconds with current price 500

    When "bob@example.com" places a bid of 525 at 20 seconds before end
    Then the auction end time should be extended by 5 minutes
    And "charlie@example.com" should receive extension notification

    When "charlie@example.com" places a bid of 550
    And the auction ends

    Then the winner should be "charlie@example.com"
    And the final price should be 550

  @complete-lifecycle @atdd
  Scenario: Multiple auctions concurrent bidding
    Given a seller "alice@example.com" exists
    And a bidder "bob@example.com" exists
    And two active auctions: "iPhone 15" (current 500) and "MacBook Pro" (current 1000)

    When "bob@example.com" places bid of 550 on "iPhone 15"
    And "bob@example.com" places bid of 1100 on "MacBook Pro"

    Then both bids should be accepted
    And "bob@example.com" should be highest bidder on both
    And notifications should be sent for both auctions

  @complete-lifecycle @atdd
  Scenario: Seller cannot bid on own auction
    Given a seller "alice@example.com" exists
    And "alice@example.com" has an active auction "iPhone 15"

    When "alice@example.com" attempts to place a bid of 600 on their own auction

    Then the bid should be rejected
    And "alice@example.com" should see error "Cannot bid on your own auction"

  @complete-lifecycle @atdd
  Scenario: Watchlist functionality
    Given a user "bob@example.com" exists
    And an active auction "iPhone 15" exists

    When "bob@example.com" adds "iPhone 15" to watchlist
    Then "bob@example.com" should be in the watchers list

    When a bid is placed on "iPhone 15"
    Then "bob@example.com" should receive "new bid" notification

    When "bob@example.com" removes "iPhone 15" from watchlist
    Then "bob@example.com" should not receive further notifications

  @complete-lifecycle @atdd
  Scenario: Search and filter auctions
    Given active auctions exist:
      | title         | category    | current_price |
      | iPhone 15     | Electronics | 500           |
      | MacBook Pro   | Electronics | 1200          |
      | Vintage Watch | Fashion     | 300           |

    When user searches for "iPhone"
    Then only "iPhone 15" should be returned

    When user filters by category "Electronics"
    Then "iPhone 15" and "MacBook Pro" should be returned

    When user filters by max price 600
    Then only "iPhone 15" and "Vintage Watch" should be returned

  @complete-lifecycle @atdd
  Scenario: User dashboard shows relevant data
    Given a user "bob@example.com" has:
      | Won auctions count    | 2 |
      | Active bids count     | 1 |
      | Watched auctions count| 3 |
      | Unread notifications  | 5 |

    When "bob@example.com" visits dashboard
    Then they should see:
      | Won auctions    | 2 |
      | Active bids     | 1 |
      | Watched auctions| 3 |
      | Unread notifications| 5 |