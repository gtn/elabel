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
 * Collection of useful functions and constants
*
* @package   block_elabel
* @copyright Florian Jungwirth <fjungwirth@gtn-solutions.com>
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once dirname(__FILE__)."/inc.php";

global $DB, $OUTPUT, $PAGE, $USER, $COURSE;
$courseid = required_param('courseid', PARAM_INT);
$labelcourseid = required_param('labelcourseid', PARAM_INT);
$requestid = optional_param('requestid', 0, PARAM_INT);
$pageid = optional_param('pageid', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
	print_error('invalidcourse', 'block_simplehtml', $courseid);
}

require_login($courseid);
$request = $DB->get_record('block_elabel_request', array('id'=>$requestid));
$context = context_course::instance($courseid);

$editable = false;
$audit = false;
//check permissions
if(!$request || $request->state < STATUS_REQUESTED) {
	//check if i am a teacher in the requested course
	require_capability('block/elabel:audit', context_course::instance($labelcourseid));
	//check if i am a student in the current course
	require_capability('block/elabel:use', $context);
	
	if($request && $request->userid != $USER->id)
		die;
	
	$editable = true;
} else if($request && has_capability('block/elabel:audit', $context)) {
	//to review the request i need to be a teacher in the current course
	
	if($request->state >= STATUS_REQUESTED)
		$audit = true;
	
	if($request->state == STATUS_REQUESTED)
		$editable = true;
}

if($_POST && $editable) {
	block_elabel_save_formdata($_POST,$requestid,$labelcourseid);
}
//requestid might have changed
$request = $DB->get_record('block_elabel_request', array('id'=>$requestid));

$page_identifier = 'tab_request';

$PAGE->set_url('/blocks/elabel/request.php', array('courseid' => $courseid,'requestid'=>$requestid, 'labelcourseid'=>$labelcourseid));
$PAGE->set_heading(get_string('pluginname', 'block_elabel'));
$PAGE->set_title(get_string($page_identifier, 'block_elabel'));
block_elabel_init_js_css();

// build breadcrumbs navigation
$coursenode = $PAGE->navigation->find($COURSE->id, navigation_node::TYPE_COURSE);
$blocknode = $coursenode->add(get_string('pluginname','block_elabel'), new moodle_url('/blocks/elabel/labels.php',array('courseid'=>$courseid)));
$pagenode = $blocknode->add(get_string($page_identifier,'block_elabel'), $PAGE->url);
$pagenode->make_active();

echo $OUTPUT->header();

// build navigation
echo block_elabel_get_navigation($pageid,$audit,($request && $request->state == STATUS_GRANTED));
echo block_elabel_get_page_content($pageid,$request);

//DISABLE FORM IF REQUEST ALREADY REQUESTED
if(!$editable) {
	echo '
	<script type="text/javascript">
		$(":input").prop("disabled", true);
	</script>';
}

echo $OUTPUT->footer();

?>