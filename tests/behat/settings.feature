@local @local_obf @foobar
Feature: Admin can modify the OBF client settings
  In order to get the OBF client to communicate with OBF API
  As an admin
  I need to be able to modify the OBF client settings

  Background:
    Given I am on homepage
      And I log in as "admin"

  Scenario: Display OBF settings page
    Given I expand "Site administration" node
     Then I should see "Open Badges"
      And I expand "Open Badges" node
      And I follow "Settings"
     Then I should see "OBF request token"
      And I should not see "I know it's working"
      And I enter a valid request token to "obftoken"
      And I press "Save changes"
     Then I should see "Authentication successful"
      And I should see "I know it's working"

  Scenario: Export existing badges to Open Badge Factory
    Given I expand "Site administration" node
      And I expand "Open Badges" node
      And I follow "Badges"
     Then I should not see "Behat Test Badge"
     When I expand "Badges" node
      And I follow "Add a new badge"
      And I fill the moodle form with:
          | Name | Behat Test Badge |
          | Description | Badge description |
          | issuername | Test Issuer |
      And I upload "local/obf/tests/behat/badge.png" file to "Image" filepicker
      And I press "Create badge"
      And I expand "Open Badges" node
      And I follow "Settings"
      And I enter a valid request token to "obftoken"
      And I press "Save changes"

