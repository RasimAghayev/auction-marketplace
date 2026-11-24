Feature: Real-time Notifications
  As a user
  I want to receive real-time notifications
  So that I stay informed about auction activities

  Background:
    Given I am authenticated as a user
    And the Reverb WebSocket server is running

  Scenario: User receives bid placed notification
    Given I am watching an auction "iPhone 15 Pro"
    When another user places a bid on that auction
    Then I should receive a real-time notification "New bid placed"
    And the notification should show the new price
    And the notification should appear in my notification center

  Scenario: User receives outbid notification
    Given I am the current highest bidder on "iPhone 15 Pro"
    When another user places a higher bid
    Then I should receive a real-time notification "You've been outbid"
    And the notification should link to the auction page

  Scenario: User receives auction won notification
    Given I am the highest bidder on "iPhone 15 Pro"
    And the auction ends with my bid above reserve
    Then I should receive a real-time notification "Congratulations! You won"
    And the notification should include next steps for payment

  Scenario: User receives auction lost notification
    Given I participated in auction "iPhone 15 Pro"
    And the auction ended with another winner
    Then I should receive a notification "Auction ended - you didn't win"
    And the notification should show the winning bid amount

  Scenario: Seller receives auction completed notification
    Given I am the seller of auction "iPhone 15 Pro"
    And the auction has ended
    Then I should receive a notification "Your auction has ended"
    And the notification should show the final price and winner info

  Scenario: User marks notification as read
    Given I have unread notifications
    When I click on a notification
    Then the notification should be marked as read
    And the unread count should decrease

  Scenario: User marks all notifications as read
    Given I have multiple unread notifications
    When I click "Mark all as read"
    Then all notifications should be marked as read
    And the unread count should be zero

  Scenario: Notifications persist across sessions
    Given I have unread notifications
    When I log out and log back in
    Then my unread notifications should still be there
    And the unread count should be correct