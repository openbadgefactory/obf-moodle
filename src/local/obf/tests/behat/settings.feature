@local @local_obf @_only_local
Feature: Admin can modify the OBF client settings
  In order to get the OBF client to communicate with OBF API
  As an admin
  I have to be able to modify the OBF client settings

  Background:
    Given I am on homepage
      And I log in as "admin"

  @javascript
  Scenario: Display OBF settings page
    Given I expand "Site administration" node
     Then I should see "Advanced features"
      And I follow "Advanced features"
      And I set the field "Enable badges" to "0"
      And I press "Save changes"
     Then I should see "Open Badges"
      And I expand "Open Badges" node
      And I follow "Settings"
     Then I should see "OBF request token"
      And I should not see "I know it's working"
      And I enter a valid request token to "obftoken"
      And I press "Authenticate"
     Then I should not see "There was an error while"
      And I follow "Badge list"
     Then I should not see "Open Badge Factory service request failed."
      And I should not see "Open Badge Factory denied the request."
      And I follow "Settings"
     Then I should see "OBF connection is up and working" 
