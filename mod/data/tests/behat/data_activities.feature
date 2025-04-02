@mod @mod_data
Feature: Users can view the list of data activities and their formatted descriptions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Bob       | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name            | intro                                                                     | course | idnumber |
      | data     | Test database 1 | This is an intro without an image                                         | C1     | data1    |
      | data     | Test database 2 | This is an intro with an image: <img src="@@PLUGINFILE@@/some_image.jpg"> | C1     | data2    |
<<<<<<< HEAD
    And the following "blocks" exist:
      | blockname        | contextlevel | reference | pagetypepattern | defaultregion |
      | activity_modules | Course       | C1        | course-view-*   | side-pre      |

  Scenario: Teachers can view the list of data activities and their formatted descriptions
    Given I am on the "Course 1" course page logged in as teacher1
=======
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Activities" block
    And I log out

  Scenario: Teachers can view the list of data activities and their formatted descriptions
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
>>>>>>> upstream/MOODLE_38_STABLE
    When I follow "Databases"
    Then I should see "Test database 1"
    And I should see "Test database 2"
    And I should see "This is an intro without an image"
    And I should see "This is an intro with an image: "
    And "//img[contains(@src, 'some_image.jpg')]" "xpath_element" should exist
    And "//img[contains(@src, '@@PLUGINFILE@@/some_image.jpg')]" "xpath_element" should not exist
<<<<<<< HEAD

  Scenario: Students can view the list of data activities and their formatted descriptions
    Given I am on the "Course 1" course page logged in as student1
=======
    And I log out

  Scenario: Students can view the list of data activities and their formatted descriptions
    Given I log in as "student1"
    And I am on "Course 1" course homepage
>>>>>>> upstream/MOODLE_38_STABLE
    When I follow "Databases"
    Then I should see "Test database 1"
    And I should see "Test database 2"
    And I should see "This is an intro without an image"
    And I should see "This is an intro with an image: "
    And "//img[contains(@src, 'some_image.jpg')]" "xpath_element" should exist
    And "//img[contains(@src, '@@PLUGINFILE@@/some_image.jpg')]" "xpath_element" should not exist
<<<<<<< HEAD
=======
    And I log out
>>>>>>> upstream/MOODLE_38_STABLE
