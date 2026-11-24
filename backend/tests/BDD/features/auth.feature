Feature: User Registration and Authentication
  As a new user
  I want to register and authenticate
  So that I can participate in auctions

  Background:
    Given the application is running

  Scenario: Successful user registration
    Given I am on the registration page
    When I fill in the registration form with valid data:
      | name     | email              | password    | password_confirmation |
      | John Doe | john@example.com   | secret123   | secret123             |
    And I submit the form
    Then I should be registered successfully
    And I should receive an authentication token
    And I should be redirected to the dashboard

  Scenario: Registration fails with duplicate email
    Given a user exists with email "john@example.com"
    When I fill in the registration form with:
      | name     | email              | password    | password_confirmation |
      | Jane Doe | john@example.com   | secret123   | secret123             |
    And I submit the form
    Then I should see an error "Email already exists"
    And I should not be registered

  Scenario: Successful user login
    Given a user exists with email "john@example.com" and password "secret123"
    When I fill in the login form with:
      | email              | password  |
      | john@example.com   | secret123 |
    And I submit the form
    Then I should be authenticated
    And I should receive an authentication token

  Scenario: Login fails with wrong credentials
    Given a user exists with email "john@example.com" and password "secret123"
    When I fill in the login form with:
      | email              | password    |
      | john@example.com   | wrongpass   |
    And I submit the form
    Then I should see an error "Invalid credentials"
    And I should not be authenticated

  Scenario: Authenticated user can logout
    Given I am authenticated as "john@example.com"
    When I click the logout button
    Then I should be logged out
    And my token should be invalidated