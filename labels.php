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
 * Overview of all the requests and courses
 *
 * @package    block_elabel
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author	   Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once dirname(__FILE__)."/inc.php";
global $DB, $OUTPUT, $PAGE, $CG;
require_once $CFG->libdir . "/tablelib.php";

$courseid = required_param('courseid', PARAM_INT);
$sorting = optional_param('sorting', 'id', PARAM_TEXT);
$sorttype = optional_param('type', 'asc', PARAM_TEXT);
$action = optional_param('action','',PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
	print_error('invalidcourse', 'block_simplehtml', $courseid);
}

require_login($course);

$context = context_course::instance($courseid);
require_capability('block/elabel:use', $context);

$page_identifier = 'tab_labels';

$PAGE->set_url('/blocks/elabel/labels.php', array('courseid' => $courseid));
$PAGE->set_heading(get_string('pluginname', 'block_elabel'));
$PAGE->set_title(get_string($page_identifier, 'block_elabel'));

// build breadcrumbs navigation
$coursenode = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$blocknode = $coursenode->add(get_string('pluginname','block_elabel'));
$pagenode = $blocknode->add(get_string($page_identifier,'block_elabel'), $PAGE->url);
$pagenode->make_active();

// build tab navigation & print header
echo $OUTPUT->header();
echo $OUTPUT->tabtree(block_elabel_build_navigation_tabs($courseid), $page_identifier);

/* CONTENT REGION */
if($action == 'submit') {
	$requestid = required_param('requestid', PARAM_INT);
	block_elabel_submit_request($requestid);
}
$table = new html_table();

if(has_capability('block/elabel:audit', $context)) {
	$data = block_elabel_get_all_requests($sorting);
	echo $OUTPUT->box(get_string('teacher_description','block_elabel'));
}
else {
	$data = block_elabel_get_my_courses();
	echo $OUTPUT->box(get_string('student_description','block_elabel'));
}

$table->head = array(
		html_writer::link($PAGE->url . "&sorting=title", get_string('title','block_elabel')),
		html_writer::link($PAGE->url . "&sorting=faculty", get_string('faculty','block_elabel')),
		html_writer::link($PAGE->url . "&sorting=username", get_string('username','block_elabel')),
		html_writer::link($PAGE->url . "&sorting=state", get_string('status','block_elabel')),
		html_writer::link($PAGE->url . "&sorting=timecreated", get_string('timecreated','block_elabel')),
		html_writer::link($PAGE->url . "&sorting=timegranted", get_string('timegranted','block_elabel')),
		'');
$status = array(STATUS_NEW => get_string('status_new','block_elabel'),
		STATUS_INPROGRESS => get_string('status_inprogress','block_elabel'),
		STATUS_REQUESTED => get_string('status_requested','block_elabel'),
		STATUS_GRANTED => get_string('status_granted','block_elabel'));

foreach($data as &$record) {
	$record['actions'] = 
		html_writer::link(
			new moodle_url('/blocks/elabel/request.php', array('courseid'=>$courseid,'labelcourseid'=>$record['courseid'],'requestid'=>$record['requestid'])),
			html_writer::empty_tag('img', array('src'=>new moodle_url('/blocks/elabel/pix/new.gif'), 'alt'=>"", 'height'=>16, 'width'=>23)));
		
		if($record['status'] == STATUS_GRANTED)
				$record['actions'] .= html_writer::link(
					new moodle_url('/blocks/elabel/label.php', array('request'=>$record['requestid'])),
					html_writer::empty_tag('img', array('src'=>new moodle_url('/blocks/elabel/pix/pdf.gif'), 'alt'=>"", 'height'=>16, 'width'=>16)),
						array('target'=>'_blank'));

	$record['status'] = $status[$record['status']];
		
	//don't display id, it is only used for the delete link
	unset($record['requestid']);
	unset($record['courseid']);
	
}
$table->data = $data;
echo html_writer::table($table);

/* END CONTENT REGION */

echo $OUTPUT->footer();

?>