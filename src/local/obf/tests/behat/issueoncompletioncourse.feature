@local @local_obf @_only_local
Feature: Admin can export existing badges to OBF
  In order to use the existing badges in OBF
  As an admin
  I have to be able to export the existing badges to OBF

  Background:
    Given I am on homepage
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | First | student1@example.com |
      | student2 | Student | Second | student2@example.com |
      | teacher1 | Teacher | First | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Completion course 1 | CC1 | topics | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | CC1 | student |
      | student2 | CC1 | student |
      | teacher1 | CC1 | editingteacher |
    And I log in as "admin"
    And I set the following administration settings values:
      | enablecompletion | 1 |

  @javascript
  Scenario: Export existing badges to Open Badge Factory
     Given I am on site homepage
      And I follow "Completion course 1"
      And completion tracking is "Enabled" in current course
      And I follow "Course completion"
      And I set the following fields to these values:
        | Teacher | 1 |
      And I press "Save changes"
      And I turn editing mode on
      And I add the "Course completion status" block
      And I log out
      When I log in as "teacher1"
      And I am on site homepage
      And I follow "Completion course"
      And I follow "View course report"
      And I should see "Student First"
      And I follow "Click to mark user complete"
     Given I am on site homepage
     And I log out
     When I log in as "admin"
     Then I expand "Site administration" node
      And I follow "Advanced features"
      And I set the field "Enable completion tracking" to "1"
      And I press "Save changes"
      And I go to the courses management page
      And I follow "Course default settings"
      And I set the field "Completion tracking" to "Yes"
      And I press "Save changes"
      And I follow "Manage courses and categories"
      And I follow "Create new course"
      And I set the following fields to these values:
          | Course full name   | Behat Test Course  |
          | Course short name  | Course description |
      And the field "Enable completion tracking" matches value "Yes"
      And I press "Save and return"
      And I expand "Open Badges" node
      And I follow "Badge list"
     Then I should not see "Behat Test Badge"
     Then I should see "Advanced features"
      And I follow "Advanced features"
      And I set the field "Enable badges" to "1"
      And I press "Save changes"
     When I expand "Badges" node
      And I follow "Add a new badge"
      And I set the following fields to these values:
          | Name        | Behat Test Badge  |
          | Description | Badge description |
          | issuername  | Test Issuer       |
      And I upload "local/obf/tests/behat/badge.png" file to "Image" filemanager
      And I press "Create badge"
      And I expand "Open Badges" node
      And I follow "Settings"
      And I enter a valid request token to "obftoken"
      And I press "Authenticate"
     Then I should see "Select badges you want to export"
      And I should see "Behat Test Badge"
     Then I set the field "Behat Test Badge" to "1"
      And I set the field "Exported badges are drafts by default" to "0"
      And I set the field "Disable Moodle's own badge-module" to "1"
      And I press "Continue"
     Then I should see "OBF connection is up and working"
     When I follow "Advanced features"
     Then the field "Enable badges" matches value ""
      And I expand "Open Badges" node
      And I follow "Badge list"
     Then I should see "Behat Test Badge"
      And I follow "Behat Test Badge"
      And I follow "Awarding rules"
      And I press "Create new awarding rule"
      And I set the field "course[]" to "Completion course 1"
      And I press "Add selected courses"
      And I press "Save changes"
      And I trigger cron
      Given I am on site homepage
      And I expand "Site administration" node
      And I expand "Open Badges" node
      And I follow "History"
      And I should see "Behat Test Badge"
      And I should see "Student First"
