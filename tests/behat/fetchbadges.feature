@local @local_obf
Feature: Admin can fetch all the organization's badges from OBF
  In order to be able to issue badges in Moodle
  As an admin
  I need to be able to fetch the badges from OBF
  
  Background:
    Given I am on homepage
    And I log in as "admin"
  
  Scenario: Fetch the badges from OBF
    And I expand "Site administration" node
    And I expand "Open Badge Factory" node
    And I follow "All badges"
    Then I should see "Couldn't find OBF API"
    When I follow "Client settings"
    And I fill the moodle form with:
      | Open Badge Factory URL | MTKUR18NL1 |
    And I press "Save changes"
    And I follow "All badges"
    Then I should see "Think tank"
    
#    When I press "Update badges from Open Badge Factory"
#    Then I should see "Think tank"