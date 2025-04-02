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
namespace core_form;
=======
/**
 * Unit tests for MoodleQuickForm_course.
 *
 * This file contains unit tests related to course forms element.
 *
 * @package     core_form
 * @category    test
 * @copyright   2020 Ruslan Kabalin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
>>>>>>> upstream/MOODLE_38_STABLE

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/course.php');

/**
 * Unit tests for MoodleQuickForm_course
 *
 * Contains test cases for testing MoodleQuickForm_course.
 *
 * @package    core_form
 * @category   test
 * @copyright  2020 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
<<<<<<< HEAD
final class course_test extends \basic_testcase {
=======
class core_form_course_testcase extends basic_testcase {
>>>>>>> upstream/MOODLE_38_STABLE

    /**
     * Test constructor supports all declared attributes.
     */
<<<<<<< HEAD
    public function test_constructor_attributes(): void {
=======
    public function test_constructor_attributes() {
>>>>>>> upstream/MOODLE_38_STABLE
        $attributes = [
            'exclude' => [1, 2],
            'requiredcapabilities' => ['moodle/course:update'],
        ];

<<<<<<< HEAD
        $element = new \MoodleQuickForm_course('testel', null, $attributes);
        $html = $element->toHtml();
        $this->assertStringContainsString('data-exclude="1,2"', $html);
        $this->assertStringContainsString('data-requiredcapabilities="moodle/course:update"', $html);
        $this->assertStringContainsString('data-limittoenrolled="0"', $html);
        $this->assertStringNotContainsString('multiple', $html);
        $this->assertStringNotContainsString('data-includefrontpage', $html);
        $this->assertStringNotContainsString('data-onlywithcompletion', $html);
=======
        $element = new MoodleQuickForm_course('testel', null, $attributes);
        $html = $element->toHtml();
        $this->assertContains('data-exclude="1,2"', $html);
        $this->assertContains('data-requiredcapabilities="moodle/course:update"', $html);
        $this->assertContains('data-limittoenrolled="0"', $html);
        $this->assertNotContains('multiple', $html);
        $this->assertNotContains('data-includefrontpage', $html);
        $this->assertNotContains('data-onlywithcompletion', $html);
>>>>>>> upstream/MOODLE_38_STABLE

        // Add more attributes.
        $attributes = [
            'multiple' => true,
            'limittoenrolled' => true,
            'includefrontpage' => true,
            'onlywithcompletion' => true,
        ];
<<<<<<< HEAD
        $element = new \MoodleQuickForm_course('testel', null, $attributes);
        $html = $element->toHtml();
        $this->assertStringContainsString('multiple', $html);
        $this->assertStringContainsString('data-limittoenrolled="1"', $html);
        $this->assertStringContainsString('data-includefrontpage="' . SITEID . '"', $html);
        $this->assertStringContainsString('data-onlywithcompletion="1"', $html);
=======
        $element = new MoodleQuickForm_course('testel', null, $attributes);
        $html = $element->toHtml();
        $this->assertContains('multiple', $html);
        $this->assertContains('data-limittoenrolled="1"', $html);
        $this->assertContains('data-includefrontpage="' . SITEID . '"', $html);
        $this->assertContains('data-onlywithcompletion="1"', $html);
>>>>>>> upstream/MOODLE_38_STABLE
    }
}
