<?php
/* * *************************************************************
 *  Copyright notice
*
*  (c) 2014 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
* ************************************************************* */

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

$table->head = array(html_writer::link($PAGE->url . "&sorting=title", get_string('title','block_elabel')),
		html_writer::link($PAGE->url . "&sorting=to_status&type=desc", get_string('status','block_elabel')),
		'');

$data = block_elabel_get_my_courses();
$status = array(STATUS_NEW => get_string('status_new','block_elabel'),
		STATUS_INPROGRESS => get_string('status_inprogress','block_elabel'),
		STATUS_REQUESTED => get_string('status_requested','block_elabel'),
		STATUS_GRANTED => get_string('status_granted','block_elabel'));

foreach($data as &$record) {
	$record['status'] = $status[$record['status']];
	$record['actions'] = 
		html_writer::link(
			new moodle_url('/blocks/elabel/request.php', array('courseid'=>$courseid,'labelcourseid'=>$record['courseid'],'requestid'=>$record['requestid'])),
			html_writer::empty_tag('img', array('src'=>new moodle_url('/blocks/elabel/pix/new.png'), 'alt'=>"", 'height'=>16, 'width'=>23)))
		;

	//don't display id, it is only used for the delete link
	unset($record['requestid']);
	unset($record['courseid']);
	
}
$table->data = $data;
echo html_writer::table($table);

/* END CONTENT REGION */

echo $OUTPUT->footer();

?>