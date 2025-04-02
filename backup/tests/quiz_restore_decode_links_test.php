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
namespace core_backup;
=======
/**
 * Decode links quiz restore tests.
 *
 * @package    core_backup
 * @copyright  2020 Ilya Tregubov <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
>>>>>>> upstream/MOODLE_38_STABLE

defined('MOODLE_INTERNAL') || die();

// Include all the needed stuff.
global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
<<<<<<< HEAD
 * Decode links quiz restore tests.
 *
 * @package    core_backup
 * @copyright  2020 Ilya Tregubov <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz_restore_decode_links_test extends \advanced_testcase {
=======
 * restore_decode tests (both rule and content)
 */
class restore_quiz_decode_testcase extends \core_privacy\tests\provider_testcase {
>>>>>>> upstream/MOODLE_38_STABLE

    /**
     * Test restore_decode_rule class
     */
<<<<<<< HEAD
    public function test_restore_quiz_decode_links(): void {
=======
    public function test_restore_quiz_decode_links() {
>>>>>>> upstream/MOODLE_38_STABLE
        global $DB, $CFG, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));
        $quiz = $generator->create_module('quiz', array(
            'course' => $course->id));
<<<<<<< HEAD
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id]);
=======
>>>>>>> upstream/MOODLE_38_STABLE

        // Create questions.

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
<<<<<<< HEAD
        $context = \context_module::instance($qbank->cmid);
=======
        $context = context_course::instance($course->id);
>>>>>>> upstream/MOODLE_38_STABLE
        $cat = $questiongenerator->create_question_category(array('contextid' => $context->id));
        $question = $questiongenerator->create_question('multichoice', null, array('category' => $cat->id));

        // Add to the quiz.
        quiz_add_quiz_question($question->id, $quiz);
<<<<<<< HEAD
        \mod_quiz\external\submit_question_version::execute(
                $DB->get_field('quiz_slots', 'id', ['quizid' => $quiz->id, 'slot' => 1]), 1);

        $questiondata = \question_bank::load_question_data($question->id);

        $DB->set_field('question', 'questiontext', $CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->cmid, ['id' => $question->id]);
=======

        $questiondata = question_bank::load_question_data($question->id);
>>>>>>> upstream/MOODLE_38_STABLE

        $firstanswer = array_shift($questiondata->options->answers);
        $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/course/view.php?id=' . $course->id,
            ['id' => $firstanswer->id]);

        $secondanswer = array_shift($questiondata->options->answers);
        $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->cmid,
            ['id' => $secondanswer->id]);

        $thirdanswer = array_shift($questiondata->options->answers);
        $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/grade/report/index.php?id=' . $quiz->cmid,
            ['id' => $thirdanswer->id]);

        $fourthanswer = array_shift($questiondata->options->answers);
        $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/mod/quiz/index.php?id=' . $quiz->cmid,
            ['id' => $fourthanswer->id]);

        $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($quiz->cmid));

<<<<<<< HEAD
        $quizquestions = \mod_quiz\question\bank\qbank_helper::get_question_structure(
                $newcm->instance, \context_module::instance($newcm->id));
        $questionids = [];
        foreach ($quizquestions as $quizquestion) {
            if ($quizquestion->questionid) {
                $this->assertEquals($CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->cmid, $quizquestion->questiontext);
                $questionids[] = $quizquestion->questionid;
            }
        }
        list($condition, $param) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'questionid');
        $condition = 'WHERE qa.question ' . $condition;

        $sql = "SELECT qa.id,
                       qa.answer
                  FROM {question_answers} qa
                  $condition";
        $answers = $DB->get_records_sql($sql, $param);

        $this->assertEquals($CFG->wwwroot . '/course/view.php?id=' . $course->id, $answers[$firstanswer->id]->answer);
        $this->assertEquals($CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->cmid, $answers[$secondanswer->id]->answer);
        $this->assertEquals($CFG->wwwroot . '/grade/report/index.php?id=' . $quiz->cmid, $answers[$thirdanswer->id]->answer);
        $this->assertEquals($CFG->wwwroot . '/mod/quiz/index.php?id=' . $quiz->cmid, $answers[$fourthanswer->id]->answer);
=======
        $sql = "SELECT qa.id, qa.answer
                  FROM {quiz} q
             LEFT JOIN {quiz_slots} qs ON qs.quizid = q.id
             LEFT JOIN {question_answers} qa ON qa.question = qs.questionid
                 WHERE q.id = :quizid";
        $params = array('quizid' => $newcm->instance);
        $answers = $DB->get_records_sql_menu($sql, $params);

        $this->assertEquals($CFG->wwwroot . '/course/view.php?id=' . $course->id, $answers[$firstanswer->id]);
        $this->assertEquals($CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->cmid, $answers[$secondanswer->id]);
        $this->assertEquals($CFG->wwwroot . '/grade/report/index.php?id=' . $quiz->cmid, $answers[$thirdanswer->id]);
        $this->assertEquals($CFG->wwwroot . '/mod/quiz/index.php?id=' . $quiz->cmid, $answers[$fourthanswer->id]);
>>>>>>> upstream/MOODLE_38_STABLE
    }
}
