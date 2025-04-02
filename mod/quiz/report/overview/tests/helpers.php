<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

<<<<<<< HEAD
use mod_quiz\local\reports\attempts_report;

/**
 * Makes some protected methods of attempts_report public to facilitate testing.
=======
/**
 * Makes some protected methods of quiz_attempts_report public to facilitate testing.
>>>>>>> upstream/MOODLE_38_STABLE
 *
 * @package   quiz_overview
 * @copyright 2020 Huong Nguyen <huongnv13@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

<<<<<<< HEAD
/**
 * Makes some protected methods of attempts_report public to facilitate testing.
=======
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');

/**
 * Makes some protected methods of quiz_attempts_report public to facilitate testing.
>>>>>>> upstream/MOODLE_38_STABLE
 *
 * @copyright 2020 Huong Nguyen <huongnv13@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
<<<<<<< HEAD
class testable_quiz_attempts_report extends attempts_report {

    /**
     * Override this function to displays the report.
     * @param stdClass $cm the course-module for this quiz.
     * @param stdClass $course the course we are in.
     * @param stdClass $quiz this quiz.
=======
class testable_quiz_attempts_report extends quiz_attempts_report {

    /**
     * Override this function to displays the report.
     * @param object $cm the course-module for this quiz.
     * @param object $course the course we are in.
     * @param object $quiz this quiz.
>>>>>>> upstream/MOODLE_38_STABLE
     */
    public function display($cm, $course, $quiz) {

    }

    /**
     * Testable delete_selected_attempts function.
     *
<<<<<<< HEAD
     * @param stdClass $quiz
     * @param stdClass $cm
=======
     * @param object $quiz
     * @param object $cm
>>>>>>> upstream/MOODLE_38_STABLE
     * @param array $attemptids
     * @param \core\dml\sql_join $allowedjoins
     */
    public function delete_selected_attempts($quiz, $cm, $attemptids, \core\dml\sql_join $allowedjoins) {
        parent::delete_selected_attempts($quiz, $cm, $attemptids, $allowedjoins);
    }
}
