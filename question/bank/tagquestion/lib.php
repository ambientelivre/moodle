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

/**
 * Question related functions.
 *
 * This file was created just because Fragment API expects callbacks to be defined on lib.php.
 *
 * @package   qbank_tagquestion
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use qbank_tagquestion\form\tags_form;

/**
 * Question tags fragment callback.
 *
 * @param array $args Arguments to the form.
 * @return null|string The rendered form.
 */
function qbank_tagquestion_output_fragment_tags_form($args) {

    if (!empty($args['id'])) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $id = clean_param($args['id'], PARAM_INT);
        $editingcontext = $args['context'];

        // Load the question and some related information.
        $question = $DB->get_record('question', ['id' => $id]);

        $sql = "SELECT qc.*
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE q.id = :id";
        $category = $DB->get_record_sql($sql, ['id' => $question->id]);
        $questioncontext = \context::instance_by_id($category->contextid);
        $contexts = new \core_question\local\bank\question_edit_contexts($editingcontext);

        // Load the question tags and filter the course tags by the current course.
        if (core_tag_tag::is_enabled('core_question', 'question')) {
            $tagobjectsbyquestion = core_tag_tag::get_items_tags('core_question', 'question', [$question->id]);
            if (!empty($tagobjectsbyquestion[$question->id])) {
                $tagobjects = $tagobjectsbyquestion[$question->id];
                $sortedtagobjects = question_sort_tags($tagobjects, context::instance_by_id($category->contextid));
            }
        }
        $formoptions = [
                'editingcontext' => $editingcontext,
                'questioncontext' => $questioncontext,
                'contexts' => $contexts->all()
        ];
        $data = [
                'id' => $question->id,
                'questioncategory' => $category->name,
                'questionname' => $question->name,
                'categoryid' => $category->id,
                'contextid' => $category->contextid,
                'context' => $questioncontext->get_context_name(),
                'tags' => $sortedtagobjects->tags ?? [],
                'coursetags' => $sortedtagobjects->coursetags ?? [],
        ];

        $cantag = question_has_capability_on($question, 'tag');
        $mform = new tags_form(null, $formoptions, 'post', '', null, $cantag, $data);
        $mform->set_data($data);

        return $mform->render();
    }

}
