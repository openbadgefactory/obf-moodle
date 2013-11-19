@local @local_obf
Feature: Users can connect to Mozilla Backpack
    In order to users see their Open Badges in Moodle
    As a user
    I have to be able to configure my backpack settings

    Background:
        Given the following "users" exists:
            | username | firstname | lastname | email                 |
            | user1    | Test      | User1    | testuser1@example.com |

    Scenario:
        Given I am on homepage
          And I log in as "user1"
          And I expand "My profile settings" node
         Then I should see "My Open Badges"
         When I follow "My Open Badges"
         Then I should see "Disconnected"
         When I fill in "Email address" with "thisdoesnotexistitreallydoesnt@jfkasdljfaslkdf.com"
          And I press "Connect"
         Then I should see "Couldn't find a user"