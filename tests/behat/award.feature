# Lots of credit goes to Moodle's internal Behat tests.

@local @local_obf @_only_local
Feature: Admin can issue site badges
  In order to users earn site badges
  As an admin
  I have to be able to issue site badges

  Background:
    Given I am on homepage
      And I log in as "admin"
      And the following "users" exists:
        | username | firstname | lastname | email                    |
        | user1    | Test      | User1    | testuser1@example.com    |
        | user2    | Test      | User2    | testuser2@example.com    |
        | teacher1 | Test      | Teacher1 | testteacher1@example.com |
      And the following badges exist:
        | Name | Description | issuername | image |
        | Behat Test Badge | Badge description | Test Issuer | local/obf/tests/behat/badge.png |
      And I set the following administration settings values:
        | Enable completion tracking | 1  |
        | Debug messages             | 15 |

  @javascript
  Scenario: Issue site badge manually
    Given I am on homepage
      And I go to badge list
      And I follow "Behat Test Badge"
      And I follow "Issuance history"
     Then I should see "This badge hasn't been issued yet"
     When I press "Issue this badge"
      And I select "Test User1 (testuser1@example.com)" from "recipientlist[]"
      And I press "Issue badge"
     Then I should see "Badge was successfully issued"
      And I should see "Test User1"
     When I follow "Details"
     Then I should see "Issuance details"

  @javascript
  Scenario: Issue course badge manually
    Given the following "courses" exists:
        | fullname | shortname | category |
        | Course 1 | course1   | 0        |
      And the following "course enrolments" exists:
        | user     | course  | role    |
        | user1    | course1 | student |
        | teacher1 | course1 | teacher |
      And I log out
      And I log in as "admin"
      And I go to badge list
      And I follow "Behat Test Badge"
      And I follow "Issuance history"
     Then I should see "This badge hasn't been issued yet"
      And I log out
      And I log in as "teacher1"
      And I am on homepage
      And I follow "Course 1"
      And I follow "Open Badges"
      And I follow "Behat Test Badge"
      And I press "Issue this badge"
      And I select "Test User1" from "recipientlist[]"
      And I press "Issue badge"
     Then I should see "Badge was successfully issued"
     When I log out
      And I log in as "admin"
      And I go to badge list
      And I follow "Behat Test Badge"
      And I follow "Issuance history"
     Then I should see "Test User1"

  @javascript
  Scenario: Issue site badge automatically
    Given the following "courses" exists:
        | fullname | shortname | category |
        | Course 1 | course1   | 0        |
        | Course 2 | course2   | 0        |
      And the following "course enrolments" exists:
        | user     | course  | role    |
        | user1    | course1 | student |
        | user1    | course2 | student |
        | user2    | course1 | student |
        | user2    | course2 | student |
      And I log out
      And I log in as "admin"
      And I set the following administration settings values:
        | Enable completion tracking | 1 |
      And I set "Course 1" to be completed when assignment "Course assignment" is completed
      And I go to badge list
      And I follow "Behat Test Badge"
      And I follow "Awarding rules"
     Then I should see "No automatic awarding rules created yet"
     When I press "Create new awarding rule"
      And I select "Course 1" from "course[]"
      And I press "Add selected courses"
      And I fill in "Minimum grade" with "5"
      And I press "Save changes"
     Then I should see "Course 1 with minimum grade of 5"
     When I press "Create new awarding rule"
     Then I should see "There are no courses"
     When I am on homepage
      And I follow "Course 1"
      And I follow "Course assignment"
      And I follow "View/grade all submissions"
      And I follow "Grade Test User1"
      And I fill in "Grade out of 100" with "5"
      And I press "Save and show next"
      And I fill in "Grade out of 100" with "4"
      And I press "Save changes"
     When I log out
      And I mark "Course assignment" of "Course 1" completed by "user1"
      And I mark "Course assignment" of "Course 1" completed by "user2"
      And I log in as "admin"
      # Apparently the completions are checked when the cron is ran twice
      And I trigger cron
      And I trigger cron
      And I go to badge list
      And I follow "Behat Test Badge"
      And I follow "Issuance history"
     Then I should see "Test User1"
      And I should not see "Test User2"
     When I follow "Awarding rules"
     Then I should see "This rule cannot be edited"

  @javascript @wip
  Scenario: Issue course badge automatically
    Given the following "courses" exists:
        | fullname | shortname | category |
        | Course 1 | course1   | 0        |
      And the following "course enrolments" exists:
        | user     | course  | role           |
        | user1    | course1 | student        |
        | user2    | course1 | student        |
        | teacher1 | course1 | editingteacher |
      And I log out
      And I log in as "admin"
      And I set the following administration settings values:
        | Enable completion tracking | 1 |
      And I log out
      And I log in as "teacher1"
      And I set "Course 1" to be completed when assignment "Course assignment" is completed
      And I am on homepage
      And I follow "Course 1"
      And I follow "Open Badges"
     Then I should see "No related badges yet"
      And I follow "Behat Test Badge"
      And I follow "Awarding rules"
      And I fill the moodle form with:
        | Minimum grade | 4 |
      And I press "Save changes"
      And I am on homepage
      And I follow "Course 1"
      And I follow "Open Badges"
     Then I should not see "No related badges"
     When I am on homepage
      And I follow "Course 1"
      And I follow "Course assignment"
      And I follow "View/grade all submissions"
      And I follow "Grade Test User1"
      And I fill in "Grade out of 100" with "5"
      And I press "Save changes"
      And I log out
      And I mark "Course assignment" of "Course 1" completed by "user1"
      And I log in as "admin"
      And I trigger cron
      And I trigger cron
      And I go to badge list
      And I follow "Behat Test Badge"
      And I follow "Issuance history"
     Then I should see "Test User1"
     When I log out
      And I log in as "teacher1"
      And I am on homepage
      And I follow "Course 1"
      And I follow "Open Badges"
      And I follow "Behat Test Badge"
      And I follow "Awarding rules"
     Then I should see "This rule cannot be edited"
