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
     When I expand "Badges" node
      And I follow "Add a new badge"
      And I fill the moodle form with:
          | Name        | Behat Test Badge  |
          | Description | Badge description |
          | issuername  | Test Issuer       |
      And I upload "local/obf/tests/behat/badge.png" file to "Image" filepicker
      And I press "Create badge"
      And I expand "Open Badges" node
      And I follow "Settings"
      And I enter a valid request token to "obftoken"
      And I press "Save changes"
     Then I should see "Select badges you want to export"
      And I should see "Behat Test Badge"
     Then I check "Behat Test Badge"
      And I check "Make exported badges visible by default"
      And I check "Disable Moodle's own badge-module"
      And I press "Continue"
     Then I should see "Authentication successful"
     When I follow "Advanced features"
     Then the "Enable badges" checkbox should not be checked
      And I expand "Open Badges" node
      And I follow "Badge list"
     Then I should see "Behat Test Badge"

