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
* @package   block_dukreminder
* @copyright Florian Jungwirth <fjungwirth@gtn-solutions.com>
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define('COMPLETION_STATUS_ALL', 0);
define('COMPLETION_STATUS_COMPLETED',1);
define('COMPLETION_STATUS_NOTCOMPLETED',2);

define('PLACEHOLDER_COURSENAME', '###coursename###');
define('PLACEHOLDER_USERNAME', '###username###');
define('PLACEHOLDER_USERMAIL', '###usermail###');

// SHOULD BE CHANGED
define('EMAIL_DUMMY',2);

/**
 * Build navigation tabs
 */
function block_dukreminder_build_navigation_tabs($courseid) {

	$rows[] = new tabobject('tab_course_reminders', new moodle_url('/blocks/dukreminder/course_reminders.php',array("courseid"=>$courseid)),get_string('tab_course_reminders','block_dukreminder'));
	$rows[] = new tabobject('tab_new_reminder', new moodle_url('/blocks/dukreminder/new_reminder.php',array("courseid"=>$courseid)),get_string('tab_new_reminder','block_dukreminder'));

	return $rows;
}

function block_dukreminder_init_js_css() {

}
/**
 * This function gets all the pending reminder entries. An entry is pending
 * if dateabsolute is set and it is not sent yet (sent = 0)
 * OR
 * if daterelative is set
 * 
 * @return array $entries
 */
function block_dukreminder_get_pending_reminders() {
	global $DB;
	$entries = $DB->get_records('block_dukreminder', array('sent' => 0));
	$now = time();
	
	$entries = $DB->get_records_select('block_dukreminder', "(sent = 0 AND dateabsolute > 0 AND dateabsolute < $now) OR (dateabsolute = 0 AND daterelative > 0)");
	return $entries;
}

function block_dukreminder_replace_placeholders($text, $coursename, $username, $usermail) {

	$text = str_replace(PLACEHOLDER_COURSENAME, $coursename, $text);
	$text = str_replace(PLACEHOLDER_USERMAIL, $usermail, $text);
	$text = str_replace(PLACEHOLDER_USERNAME, $username, $text);
	
	return $text;
}

/**
 * This function filters the users to recieve a reminder according to the
 * criterias recorded in the database.
 * The criterias are: 
 *  - deadline: amount of sec after course enrolment
 *  - groups: user groups specified in the course
 *  - completion status: if users have already completed/not completed the course
 *  
 * @param stdClass $entry database entry of block_dukreminder table
 * @return array $users users to recieve a reminder
 */
function block_dukreminder_filter_users($entry) {
	global $DB;
	
	//all potential users
	$users = get_role_users(5, context_course::instance($entry->courseid));

	//filter users by deadline
	if($entry->daterelative > 0) {
		//if reminder has relative date: check if user has already got an email
		$mailsSent = $DB->get_records('block_dukreminder_mailssent',array('reminderid' => $entry->id),'','userid');
		
		$enabled_enrol_plugins = implode(',', $DB->get_fieldset_select('enrol','id',"courseid = $entry->courseid"));
		//check user enrolment dates
		foreach($users as $user) {
			//if user has already got an email -> unset
			if(array_key_exists($user->id, $mailsSent))
				unset($users[$user->id]);
			
			$enrolment_time = $DB->get_field_select('user_enrolments','timestart',"userid = $user->id AND enrolid IN ($enabled_enrol_plugins)");
			//if user is longer enroled than the deadline is long -> unset
			if($enrolment_time + $entry->daterelative > time()) {
				unset($users[$user->id]);
			}
		}
	}
	
	//filter users by groups
	$group_ids = explode(';',$entry->to_groups);
	if($entry->to_groups) {
		foreach($users as $user) {
			//if user is not part in at least 1 group -> unset
			$isMember = false;
			foreach($group_ids as $group_id)
				if(groups_is_member($group_id,$user->id))
				$isMember = true;

			if(!$isMember) {
				unset($users[$user->id]);
			}
		}
	}
	
	//filter users by completion status
	if($entry->to_status != COMPLETION_STATUS_ALL) {
		foreach ($users as $user) {
			$select = "course = $entry->courseid AND userid = $user->id";
			$timecompleted = $DB->get_field_select('course_completions', 'timecompleted', $select);
			//if user has completed and status is "not completed" -> unset
			//if user has not completed and status is "completed" -> unset
			if (($timecompleted && $entry->to_status == COMPLETION_STATUS_NOTCOMPLETED) || (!$timecompleted && $entry->to_status == COMPLETION_STATUS_COMPLETED)) {
				$timecompleted = date("d.m.Y", $timecompleted);
				unset($users[$user->id]);
			}
		}
	}
	
	return $users;
}

function block_dukreminder_get_manager($user) {
	global $DB;
	// Bestimme Vorgesetzten (= Manager) zum User
	if (isset($user->address)) { // Vorgesetzte stehen in Moodle im Adressfeld des Users
		$manager = addslashes(substr($user->address, 0, 50)) . "%"; // addslashes wegen ' in manchen Usernamen
		// Suche userid des Vorgesetzten in mdl_user
		$select = "idnumber LIKE '$manager'";
		$manager_id = $DB->get_field_select('user', 'id', $select);
	
		#// Hole Details des Vorgesetzten aus mdl_user
		return $DB->get_record('user',array('id' => $manager_id));
		/*
		$managers[$manager_id]->username = $DB->get_field_select('user', 'username', $select);
		$managers[$manager_id]->firstname = $DB->get_field_select('user', 'firstname', $select);
		$managers[$manager_id]->lastname = $DB->get_field_select('user', 'lastname', $select);
		$managers[$manager_id]->email = $DB->get_field_select('user', 'email', $select);
		*/
	}
	return false;
}

function block_dukreminder_get_mail_text($course, $users) {
	$textParams = new stdClass();
	$textParams->amount = count($users);
	$textParams->course = $course;
	$mail_text = get_string('email_teacher_notification','block_dukreminder',$textParams);
	foreach($users as $user)
		$mail_text .=  "\n" . fullname($user);
	
	return $mail_text;
}

function block_dukreminder_get_course_teachers($coursecontext) {
	return array_merge(get_role_users(4, $coursecontext),get_role_users(3, $coursecontext),get_role_users(2, $coursecontext),get_role_users(1, $coursecontext));
}