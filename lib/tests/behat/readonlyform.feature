@core @core_form
Feature: Read-only forms should work
  In order to use certain forms on large Moodle installations
  As a user
  Relevant featuers of non-editable forms should still work

  @javascript
  Scenario: Shortforms expand collapsing should work for read-only forms - one-section form
<<<<<<< HEAD
    Given I log in as "admin"
    And I visit "/lib/tests/fixtures/readonlyform.php?sections=1"
    When I press "First section"
    Then "Name" "field" should be visible
    And the field "Name" matches value "Important information"
    And I press "First section"
    And "Name" "field" should not be visible

  @javascript
  Scenario: Shortforms expand collapsing should work for read-only forms - two-section form
    Given I log in as "admin"
    And I visit "/lib/tests/fixtures/readonlyform.php?sections=2"
    When I press "Expand all"
    Then "Name" "field" should be visible
    And the field "Name" matches value "Important information"
    And "Other" "field" should be visible
    And the field "Other" matches value "Other information"
    And I press "Collapse all"
    And "Name" "field" should not be visible
    And "Other" "field" should not be visible
=======
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "activities" exist:
      | activity   | name | intro                                                                        | course | idnumber |
      | label      | L1   | <a href="../lib/tests/fixtures/readonlyform.php?sections=1">Fixture link</a> | C1     | label1   |
    Given I am on the "C1" "Course" page logged in as "admin"
    And I follow "Fixture link"
    When I expand all fieldsets
    Then the field "Name" matches value "Important information"

  @javascript
  Scenario: Shortforms expand collapsing should work for read-only forms - two-section form
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "activities" exist:
      | activity   | name | intro                                                                        | course | idnumber |
      | label      | L1   | <a href="../lib/tests/fixtures/readonlyform.php?sections=2">Fixture link</a> | C1     | label1   |
    Given I am on the "C1" "Course" page logged in as "admin"
    And I follow "Fixture link"
    When I expand all fieldsets
    Then the field "Name" matches value "Important information"
    Then the field "Other" matches value "Other information"
>>>>>>> upstream/MOODLE_38_STABLE
