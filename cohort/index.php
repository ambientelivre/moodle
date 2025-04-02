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
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_cohort\reportbuilder\local\systemreports\cohorts;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\system_report_factory;

require('../config.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
$searchquery  = optional_param('search', '', PARAM_RAW);
$showall = optional_param('showall', false, PARAM_BOOL);

require_login();

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
    throw new \moodle_exception('invalidcontext');
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
}

$manager = has_capability('moodle/cohort:manage', $context);
$canassign = has_capability('moodle/cohort:assign', $context);
if (!$manager) {
    require_capability('moodle/cohort:view', $context);
}

$strcohorts = get_string('cohorts', 'cohort');

if ($category) {
    $PAGE->set_pagelayout('admin');
    $PAGE->set_context($context);
    $PAGE->set_url('/cohort/index.php', array('contextid'=>$context->id));

    core_course_category::page_setup();
    // Set the cohorts node active in the settings navigation block.
    if ($cohortsnode = $PAGE->settingsnav->find('cohort', navigation_node::TYPE_SETTING)) {
        $cohortsnode->make_active();
    }

    $PAGE->set_title($strcohorts);
    $showall = false;
} else {
    admin_externalpage_setup('cohorts', '', null, '', array('pagelayout'=>'report'));
    navigation_node::override_active_url(new moodle_url('/cohort/index.php'));
    if ($showall) {
        $strallcohorts = get_string('allcohorts', 'cohort');
        $PAGE->set_title($strallcohorts);
        $PAGE->navbar->add($strallcohorts, $PAGE->url);
    } else {
        $strsystemcohorts = get_string('systemcohorts', 'cohort');
        $PAGE->set_title($strsystemcohorts);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strallcohorts ?? $strsystemcohorts ?? $strcohorts);

$params = [];
if ($contextid) {
    $params['contextid'] = $contextid;
}
if ($searchquery) {
    $params['search'] = $searchquery;
}
if ($showall) {
    $params['showall'] = true;
}
$baseurl = new moodle_url('/cohort/index.php', $params);

if ($editcontrols = cohort_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

$reportparams = ['contextid' => $context->id, 'showall' => $showall];
$report = system_report_factory::create(cohorts::class, $context, '', '', 0, $reportparams);

<<<<<<< HEAD
// Check if it needs to search by name.
if (!empty($searchquery)) {
    $report->set_filter_values([
        'cohort:name_operator' => text::CONTAINS,
        'cohort:name_value' => $searchquery,
    ]);
=======
// Output pagination bar.
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);

$data = array();
$editcolumnisempty = true;
foreach($cohorts['cohorts'] as $cohort) {
    $line = array();
    $cohortcontext = context::instance_by_id($cohort->contextid);
    $cohort->description = file_rewrite_pluginfile_urls($cohort->description, 'pluginfile.php', $cohortcontext->id,
            'cohort', 'description', $cohort->id);
    if ($showall) {
        if ($cohortcontext->contextlevel == CONTEXT_COURSECAT) {
            $line[] = html_writer::link(new moodle_url('/cohort/index.php' ,
                    array('contextid' => $cohort->contextid)), $cohortcontext->get_context_name(false));
        } else {
            $line[] = $cohortcontext->get_context_name(false);
        }
    }
    $tmpl = new \core_cohort\output\cohortname($cohort);
    $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
    $tmpl = new \core_cohort\output\cohortidnumber($cohort);
    $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
    $line[] = format_text($cohort->description, $cohort->descriptionformat);

    $line[] = $DB->count_records('cohort_members', array('cohortid'=>$cohort->id));

    if (empty($cohort->component)) {
        $line[] = get_string('nocomponent', 'cohort');
    } else {
        $line[] = get_string('pluginname', $cohort->component);
    }

    $buttons = array();
    if (empty($cohort->component)) {
        $cohortmanager = has_capability('moodle/cohort:manage', $cohortcontext);
        $cohortcanassign = has_capability('moodle/cohort:assign', $cohortcontext);

        $urlparams = array('id' => $cohort->id, 'returnurl' => $baseurl->out_as_local_url(false));
        $showhideurl = new moodle_url('/cohort/edit.php', $urlparams + array('sesskey' => sesskey()));
        if ($cohortmanager) {
            if ($cohort->visible) {
                $showhideurl->param('hide', 1);
                $visibleimg = $OUTPUT->pix_icon('t/hide', get_string('hide'));
                $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('hide')));
            } else {
                $showhideurl->param('show', 1);
                $visibleimg = $OUTPUT->pix_icon('t/show', get_string('show'));
                $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('show')));
            }
            $buttons[] = html_writer::link(new moodle_url('/cohort/edit.php', $urlparams + array('delete' => 1)),
                $OUTPUT->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete')));
            $buttons[] = html_writer::link(new moodle_url('/cohort/edit.php', $urlparams),
                $OUTPUT->pix_icon('t/edit', get_string('edit')),
                array('title' => get_string('edit')));
            $editcolumnisempty = false;
        }
        if ($cohortcanassign) {
            $buttons[] = html_writer::link(new moodle_url('/cohort/assign.php', $urlparams),
                $OUTPUT->pix_icon('i/users', get_string('assign', 'core_cohort')),
                array('title' => get_string('assign', 'core_cohort')));
            $editcolumnisempty = false;
        }
    }
    $line[] = implode(' ', $buttons);

    $data[] = $row = new html_table_row($line);
    if (!$cohort->visible) {
        $row->attributes['class'] = 'dimmed_text';
    }
>>>>>>> upstream/MOODLE_38_STABLE
}

// Show the report.
echo $report->output();

// Show the delete selected button if there are records.
if ($DB->record_exists('cohort', [])) {

    echo $OUTPUT->render(new single_button(
        new moodle_url('#'),
        get_string('deleteselected'),
        'post',
        single_button::BUTTON_PRIMARY,
        ['data-action' => 'cohort-delete-selected']
    ));

    $PAGE->requires->js_call_amd('core_cohort/actions', 'init');
}

echo $OUTPUT->footer();
