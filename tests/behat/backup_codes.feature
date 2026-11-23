@tool @tool_mfa @factor @factor_backupcodes
Feature: Sign in with a backup code
  In order to sign in when I do not have my phone
  As a user
  I need one-time backup codes

  Background:
    Given I log in as "admin"
    # One step per plugin: rows with the same setting name overwrite each other.
    And the following config values are set as admin:
      | enabled | 1 | tool_mfa |
    And the following config values are set as admin:
      | enabled | 1 | factor_backupcodes |

  Scenario: Set up codes, sign in with one, and it is used up
    Given I follow "Preferences" in the user menu
    And I click on "Multi-factor authentication preferences" "link"
    And I click on "Set up backup codes" "button"
    And I remember backup code number "3"
    And I set the field "I have saved these codes somewhere safe" to "1"
    And I press "Save changes"
    And I should see "Active" in the "#factor-card-backupcodes" "css_element"
    And I log out
    When I log in as "admin"
    And I should see "Use a backup code"
    And I choose backup codes on the verification page
    And I enter the remembered backup code
    And I press "Continue"
    Then I should see "Acceptance test site"
    And I follow "Preferences" in the user menu
    And I click on "Multi-factor authentication preferences" "link"
    And I should see "You have 9 backup codes left." in the "#factor-card-backupcodes" "css_element"
    And I click on "Manage backup codes" "button"
    And I should see "Backup codes (9 left)"
