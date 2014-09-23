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

define('STATUS_NEW',3);
define('STATUS_INPROGRESS',0);
define('STATUS_REQUESTED',1);
define('STATUS_GRANTED',2);

define('PAGE_METAINFO',0);
define('PAGE_RESULT', 100);

define('QUESTION_TYPE_DROPDOWN',0);
define('QUESTION_TYPE_TEXT',1);

define('SELECT_NONE',0);
define('SELECT_TRUE',3);
define('SELECT_FALSE',2);
define('SELECT_PARTLY',1);

function block_elabel_init_js_css() {
	global $PAGE;
	
	$PAGE->requires->css('/blocks/elabel/style.css');
	$PAGE->requires->js('/blocks/elabel/lib/jquery-1.11.1.min.js', true);
	$PAGE->requires->css('/blocks/elabel/lib/jquery-ui.min.css');
	$PAGE->requires->js('/blocks/elabel/lib/jquery-ui.min.js', true);
	$PAGE->requires->js('/blocks/elabel/lib/form.js', true);
	$PAGE->requires->js('/blocks/elabel/lib/jquery-ui-slider-pips.js',true);
	$PAGE->requires->css('/blocks/elabel/lib/jquery-ui-slider-pips.css',true);
}
/**
 * Build navigation tabs
 */
function block_elabel_build_navigation_tabs($courseid) {

	//$rows[] = new tabobject('tab_course_reminders', new moodle_url('/blocks/elabel/course_reminders.php',array("courseid"=>$courseid)),get_string('tab_course_reminders','block_elabel'));
	//$rows[] = new tabobject('tab_new_reminder', new moodle_url('/blocks/elabel/new_reminder.php',array("courseid"=>$courseid)),get_string('tab_new_reminder','block_elabel'));
	return array();
	return $rows;
}

function block_elabel_get_my_courses() {
	global $DB, $USER;
	
	$courses = enrol_get_my_courses();
	$data = array();
	foreach($courses as $course) {
		if(!has_capability('block/elabel:use', context_course::instance($course->id), $USER))
			unset($course);
			
		$request = $DB->get_record('block_elabel_request',array('courseid' => $course->id));
		if(!$request) {
			$course->status = STATUS_NEW;
			$course->requestid = 0;
		}
		else {
			$course->status = $request->state;
			$course->requestid = $request->id;
		}
		$data[] = array('title'=>$course->fullname, 'status'=>$course->status,'requestid'=>$course->requestid,'courseid'=>$course->id);
	}
	
	return $data;
}

function block_elabel_get_navigation($pageid) {
	global $DB,$PAGE;
	
	$menu = '
	<div class="exaLabel-Tabs">
		<ul>
			<li'.(($pageid == PAGE_METAINFO) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid=0'.'">Angaben zum Lehrgang</a></li>';
	
			foreach($DB->get_records('block_elabel_page') as $page)
				$menu .= '<li'.(($pageid == $page->id) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.$page->id.'">'.$page->shorttitle.'</a></li>';
			
			$menu .=
			'<li'.(($pageid == PAGE_RESULT) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid=100'.'">Auswertung</a></li>
		</ul>
	</div>';
			
	return $menu;
}

function block_elabel_get_page_content($pageid, $requestid) {
	global $DB;
	if($pageid == PAGE_METAINFO)
		return block_elabel_get_metainfo_page($DB->get_record('block_elabel_request',array('id'=>$requestid)));
	
	return block_elabel_get_evaluation_page($pageid,$requestid);
}

function block_elabel_get_metainfo_page($data) {
	global $PAGE;
	return '<div name="request" id="request" style="clear: both;"></div>
			<form method="POST" action="'.$PAGE->url.'&pageid=1">
			<input type="hidden" name="formpage" value="0">
			<table class="exaLabel-Table">
			<thead>
				<tr>
					<th colspan="2">
					
						<table class="exaLabel-Table-Head">
							<tr>
								<th class="exHeFi">E-Learning Label</th>
								<th class="exHeSe"><h1>Angaben zum Lehrgang</h1></th>
								<th class="exHeTh"><img src="pix/duk_logo_00.png" alt=""></th>
							</tr>
						</table>
					</th>
				</tr>
			</thead>
			
			<tbody>
				
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Organisationseinheit</h2>
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Fakultät</td>
					<td>
						<input id="" class="" type="text" value="'.$data->faculty.'" name="faculty">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Department</td>
					<td>
						<input id="" class="" type="text" value="'.$data->department.'" name="department">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Zentrum</td>
					<td>
						<input id="" class="" type="text" value="'.$data->center.'" name="center">
					</td>
				</tr>
				
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Lehrgang</h2>
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Lehrgangsbezeichnung</td>
					<td>
						<input id="" class="" type="text" value="'.$data->coursename.'" name="coursename">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Nummer oder interne Bezeichnung (optional)</td>
					<td>
						<input id="" class="" type="text" value="'.$data->coursenumber.'" name="coursenumber">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Lehrgangsabschluss</td>
					<td>
						<input id="" class="" type="text" value="'.$data->completiontype.'" name="completiontype">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Anzahl ECTS</td>
					<td>
						<input id="" class="" type="text" value="'.$data->ects.'" name="ects">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Anzahl Präsenz-Unterrichtseinheiten</td>
					<td>
						<input id="" class="" type="text" value="'.$data->lessons.'" name="lessons">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Anzahl Präsenztage</td>
					<td>
						<input id="" class="" type="text" value="'.$data->days.'" name="days">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Lehrgangsdauer in Semester</td>
					<td>
						<input id="" class="" type="text" value="'.$data->semester.'" name="semester">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Lehrgangsabschlusstermin für 
Studierendenbefragung  (Monat/Jahr)</td>
					<td>
						<input id="" class="" type="text" value="'.$data->survey.'" name="survey">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">URL Lehrgangsbeschreibung-Web</td>
					<td>
						<input id="" class="" type="text" value="'.$data->urldescription.'" name="urldescription">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">URL Lehrgang-Moodle</td>
					<td>
						<input id="" class="" type="text" value="'.$data->urlmoodle.'" name="urlmoodle">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Sonstige Angaben zum Lehrgang (optional)</td>
					<td>
						<input id="" class="" type="text" value="'.$data->other.'" name="other">
					</td>
				</tr>
				
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Zuständigkeiten</h2>
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Name Antragsteller</td>
					<td>
						<input id="" class="" type="text" value="'.$data->username.'" name="username">
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Name Lehrgangsbetreuung (Moodle)</td>
					<td>
						<input id="" class="" type="text" value="'.$data->courseteacher.'" name="courseteacher">
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Name Lehrgangsleitung</td>
					<td>
						<input id="" class="" type="text" value="'.$data->coursehead.'" name="coursehead">
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Name Departmentleitung</td>
					<td>
						<input id="" class="" type="text" value="'.$data->departmenthead.'" name="departmenthead">
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Kenntnisnahme durch Departmentleitung</td>
					<td>
						<input type="checkbox" name="departmentnotification" value="1" '.(($data->departmentnotification) ? 'checked' : '').'>
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Datum der Einreichung</td>
					<td>
						<input id="" class="" type="text" value="'.$data->timecreated.'" name="timecreated">
					</td>
				</tr>
				<tr class="exalabel-submit">
					<td></td>
					<td class="exalable-right"><input type="submit" value="Weiter"></td>
				</tr>
			</tbody>
		</table>
		</form>';
}

function block_elabel_get_evaluation_page($pageid, $requestid) {
	global $DB,$PAGE;
	
	$page = $DB->get_record('block_elabel_page', array('id'=>$pageid));
	$question_groups = $DB->get_records('block_elabel_questiongroup',array('pageid'=>$pageid));
	
	$answers = $DB->get_records_menu('block_elabel_qinstance',array('requestid'=>$requestid),'','questionid,answer');
	
	$content = '<div style="clear: both;"></div>
		<form name="request" id="request" method="POST" action="'.$PAGE->url.'&pageid='.($pageid+1).'">
		<input type="hidden" name="formpage" value="'.$pageid.'">
		<table class="exaLabel-Table">
			<thead>
				<tr>
					<th colspan="2">
					
						<table class="exaLabel-Table-Head">
							<tr>
								<th class="exHeFi">E-Learning Label</th>
								<th class="exHeSe"><h1>EVALUATION</h1></th>
								<th class="exHeTh"><img src="pix/duk_logo_00.png" alt=""></th>
							</tr>
						</table>
					</th>
				</tr>
			</thead>
			
			<tbody>
				
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>'.$page->shorttitle . ' ' . $page->title .'</h2>
					<p>'.$page->description.'</p>
					</td>
				</tr>
				
				<tr>
					<td class="exaLabel-Description" colspan="2">'.$page->description_detail.'</td>
				</tr>';
	
				foreach($question_groups as $group) {
					$content .= '
				<tr class="exalabel-topic">
					<td class="exalabel-topic-title">'.$group->title.'</td>
					<td>trifft zu</td>
				</tr>';
					
					$questions = $DB->get_records('block_elabel_question',array('questiongroupid'=>$group->id));
					foreach($questions as $question) {
						if($question->questiontype == QUESTION_TYPE_DROPDOWN) {
							$content .= '
							<tr>
								<td class="exalabel-row">'.$question->title.'</td>
								<td>
									<select name="'.$question->id.'">
										<option value="'.SELECT_NONE.'" ' . ((!isset($answers[$question->id]) || $answers[$question->id] == SELECT_NONE) ? ' selected ' : '') .'></option>
										<option value="'.SELECT_TRUE.'"' . ((isset($answers[$question->id]) && $answers[$question->id] == SELECT_TRUE) ? ' selected ' : '') .'>trifft zu</option>
										<option value="'.SELECT_FALSE.'" ' . ((isset($answers[$question->id]) && $answers[$question->id] == SELECT_FALSE) ? ' selected ' : '') .'>trifft nicht zu</option>
										<option value="'.SELECT_PARTLY.'" ' . ((isset($answers[$question->id]) && $answers[$question->id] == SELECT_PARTLY) ? ' selected ' : '') .'>trifft teilweise zu</option>
									</select>
								</td>
							</tr>';
						} else {
							$content .= '
							<tr>
								<td colspan="2"class="exalabel-row exalabel-row-more">'.$question->title.':
								<textarea name="'.$question->id.'" rows="4" cols="50">'.((isset($answers[$question->id])) ? $answers[$question->id] : '').'</textarea></td>
							</tr>';
						}

					}
				}
				
			$content .= '
			<tr class="exalabel-submit">
					<td><div id="slider"></div></td>
					<td class="exalable-right"><input type="submit" value="Weiter"></td>
				</tr>
			</tbody>
		</table>';
			return $content;
}
function block_elabel_get_questions_for_page($pageid) {
	global $DB;
	$questions = array();
	foreach($DB->get_records('block_elabel_questiongroup',array('pageid'=>$pageid)) as $group) {
		$questions += $DB->get_records('block_elabel_question',array('questiongroupid'=>$group->id),'','id');
	}
	return $questions;
}
function block_elabel_save_formdata($data, &$requestid, $courseid) {
	global $USER, $DB;
	$request = $DB->get_record('block_elabel_request',array('id'=>$requestid));
	if($data['formpage'] == 0 && !$request) {
		$data['courseid'] = $courseid;
		$data['userid'] = $USER->id;
		$data['timecreated'] = time();
		
		$requestid = $DB->insert_record('block_elabel_request', $data);
		return;
	} elseif($data['formpage'] == 0) {
		foreach($data as $name => $field) {
			$request->{$name} = $field;
		}
		$request->timemodified = time();
		$DB->update_record('block_elabel_request', $request);	
		return;
	}
	unset($data['formpage']);
	foreach($data as $id => $answer) {
		$DB->delete_records('block_elabel_qinstance',array('userid' => $USER->id, 'requestid' => $requestid, 'questionid' => $id));
		$DB->insert_record('block_elabel_qinstance', array('questionid'=>$id,'answer'=>$answer,'userid'=>$USER->id,'requestid'=>$requestid));
	}
	
	return;
}