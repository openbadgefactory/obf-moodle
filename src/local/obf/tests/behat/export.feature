@local @local_obf @_only_local
Feature: Admin can export existing badges to OBF
  In order to use the existing badges in OBF
  As an admin
  I have to be able to export the existing badges to OBF

  Background:
    Given I am on homepage
      And I log in as "admin"

  @javascript
  Scenario: Export existing badges to Open Badge Factory
    Given I expand "Site administration" node
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
