@local @local_obf
Feature: Admin can fetch all the badges of an organization from OBF
   In order to be able to issue badges in Moodle
   As an admin
   I want to be able to fetch the badges from OBF

   Background:
      Given I am on homepage
      And I log in as "admin"

   Scenario: Fetch the badges from OBF
      And I expand "Site administration" node
      And I expand "Open Badges" node
      And I follow "Badges"
      Then I should see "Couldn't find OBF API"
      When I follow "Client settings"
      And I fill the moodle form with:
         | Open Badge Factory URL | MTKUR18NL1 |
      And I press "Save changes"
      And I follow "Badges"
      Then I should see "Think tank"