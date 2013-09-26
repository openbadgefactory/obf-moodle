@local @local_obf
Feature: Admin can modify the OBF client settings
  In order to get the OBF client to communicate with OBF API
  As an admin
  I need to be able to modify the OBF client settings

  Background:
    Given I am on homepage
    And I log in as "admin"

  Scenario: Display OBF settings page
    And I expand "Site administration" node
    Then I should see "Open Badge Factory"
    And I expand "Open Badge Factory" node
    And I follow "Client settings"
    Then I should see "obf_url"