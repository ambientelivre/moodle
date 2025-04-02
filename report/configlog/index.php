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
 * Config changes report
 *
 * @package    report
 * @subpackage configlog
 * @copyright  2009 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\system_report_factory;
use core_reportbuilder\local\filters\text;
use report_configlog\reportbuilder\local\systemreports\config_changes;

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Allow searching by setting when providing parameter directly.
$search = optional_param('search', '', PARAM_TEXT);

admin_externalpage_setup('reportconfiglog', '', ['search' => $search], '', ['pagelayout' => 'report']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configlog', 'report_configlog'));

// Create out report instance, setting initial filtering if required.
$report = system_report_factory::create(config_changes::class, context_system::instance());
if (!empty($search)) {
    $report->set_filter_values([
        'config_change:setting_operator' => text::IS_EQUAL_TO,
        'config_change:setting_value' => $search,
    ]);
}

<<<<<<< HEAD
echo $report->output();
=======
// Sort direction can only be ASC or DESC. Fall back to default (DESC) for invalid values.
$dir = $dir != 'ASC' ? 'DESC' : 'ASC';

foreach ($columns as $column=>$strcolumn) {
    if ($sort != $column) {
        $columnicon = '';
        if ($column == 'lastaccess') {
            $columndir = 'DESC';
        } else {
            $columndir = 'ASC';
        }
    } else {
        $columndir = $dir == 'ASC' ? 'DESC':'ASC';
        if ($column == 'lastaccess') {
            $columnicon = $dir == 'ASC' ? 'up':'down';
        } else {
            $columnicon = $dir == 'ASC' ? 'down':'up';
        }
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, '');

    }
    $hcolumns[$column] = "<a href=\"index.php?sort=$column&amp;dir=$columndir&amp;page=$page&amp;perpage=$perpage\">".$strcolumn."</a>$columnicon";
}

$baseurl = new moodle_url('index.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($changescount, $page, $perpage, $baseurl);

$override = new stdClass();
$override->firstname = 'firstname';
$override->lastname = 'lastname';
$fullnamelanguage = get_string('fullnamedisplay', '', $override);
if (($CFG->fullnamedisplay == 'firstname lastname') or
    ($CFG->fullnamedisplay == 'firstname') or
    ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
    $fullnamedisplay = $hcolumns['firstname'].' / '.$hcolumns['lastname'];
} else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
    $fullnamedisplay = $hcolumns['lastname'].' / '.$hcolumns['firstname'];
}

$table = new html_table();
$table->head  = array($hcolumns['timemodified'], $fullnamedisplay, $hcolumns['plugin'], $hcolumns['name'], $hcolumns['value'], $hcolumns['oldvalue']);
$table->colclasses = array('leftalign date', 'leftalign name', 'leftalign plugin', 'leftalign setting', 'leftalign newvalue', 'leftalign originalvalue');
$table->id = 'configchanges';
$table->attributes['class'] = 'admintable generaltable';
$table->data  = array();

if ($sort == 'firstname' or $sort == 'lastname') {
    $orderby = "u.$sort $dir";
} else if ($sort == 'value' or $sort == 'oldvalue') {
    // cross-db text-compatible sorting.
    $orderby = $DB->sql_order_by_text("cl.$sort", 255) . ' ' . $dir;
} else {
    $orderby = "cl.$sort $dir";
}

$ufields = user_picture::fields('u');
$sql = "SELECT $ufields,
               cl.timemodified, cl.plugin, cl.name, cl.value, cl.oldvalue
          FROM {config_log} cl
          JOIN {user} u ON u.id = cl.userid
      ORDER BY $orderby";

$rs = $DB->get_recordset_sql($sql, array(), $page*$perpage, $perpage);
foreach ($rs as $log) {
    $row = array();
    $row[] = userdate($log->timemodified);
    $row[] = fullname($log);
    if (is_null($log->plugin)) {
        $row[] = 'core';
    } else {
        $row[] = $log->plugin;
    }
    $row[] = $log->name;
    $row[] = s($log->value);
    $row[] = s($log->oldvalue);

    $table->data[] = $row;
}
$rs->close();

echo html_writer::table($table);
>>>>>>> upstream/MOODLE_38_STABLE

echo $OUTPUT->footer();
