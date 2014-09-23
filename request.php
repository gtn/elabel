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

$context = context_course::instance($courseid);
require_capability('block/elabel:use', $context);

if($_POST) {
	block_elabel_save_formdata($_POST,$requestid,$labelcourseid);
}
$page_identifier = 'tab_request';

$PAGE->set_url('/blocks/elabel/request.php', array('courseid' => $courseid,'requestid'=>$requestid, 'labelcourseid'=>$labelcourseid));
$PAGE->set_heading(get_string('pluginname', 'block_elabel'));
$PAGE->set_title(get_string($page_identifier, 'block_elabel'));
block_elabel_init_js_css();

// build breadcrumbs navigation
$coursenode = $PAGE->navigation->find($COURSE->id, navigation_node::TYPE_COURSE);
$blocknode = $coursenode->add(get_string('pluginname','block_elabel'));
$pagenode = $blocknode->add(get_string($page_identifier,'block_elabel'), $PAGE->url);
$pagenode->make_active();

/* CONTENT REGION */
echo $OUTPUT->header();

// build navigation
echo block_elabel_get_navigation($pageid);
echo block_elabel_get_page_content($pageid,$requestid);

/* END CONTENT REGION */

echo $OUTPUT->footer();

?>