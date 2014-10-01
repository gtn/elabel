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
define('PAGE_AUDIT',200);
define('PAGE_PDF',250);

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
	$PAGE->requires->js('/blocks/elabel/lib/Chart.min.js',true);
}
/**
 * Build navigation tabs
 */
function block_elabel_build_navigation_tabs($courseid) {

	return array();
}
function block_elabel_get_all_requests($sorting = 'id') {
	global $DB;
	
	$data = array();
	$requests = $DB->get_records('block_elabel_request',null,$sorting);
	foreach($requests as $request) {
		$data[] = array(
				'title' => $request->coursename,
				'faculty' => $request->faculty,
				'user' => $request->username,
				'status' => $request->state,
				'requestid' => $request->id,
				'courseid' => $request->courseid,
				'timecreated' => ($request->timecreated) ? date("d.m.Y",$request->timecreated) : '',
				'timegranted' => ($request->timegranted) ? date("d.m.Y",$request->timegranted) : '');
	}
	
	return $data;
}
function block_elabel_get_my_courses() {
	global $DB, $USER;
	
	$courses = enrol_get_my_courses();
	$data = array();
	foreach($courses as $course) {
		if(!has_capability('block/elabel:audit', context_course::instance($course->id), $USER)) {
			unset($course);
			continue;
		}
			
		$request = $DB->get_record('block_elabel_request',array('courseid' => $course->id));
		if(!$request) {
			$course->status = STATUS_NEW;
			$course->requestid = 0;
		}
		else {
			$course->status = $request->state;
			$course->requestid = $request->id;
			
		}
		list($faculty,$department,$center) = block_elabel_get_coursecat_infos($course->id);
		$data[] = array('title'=>$course->fullname,
				'faculty' => $faculty,
				'user' => fullname($USER),
				'status'=>$course->status,
				'requestid'=>$course->requestid,
				'courseid'=>$course->id,
				'timecreated'=>(isset($request->timecreated) ? date("d.m.Y",$request->timecreated) : ''),
				'timegranted'=>(isset($request->timegranted) ? date("d.m.Y",$request->timegranted) : ''));
	}
	
	return $data;
}

function block_elabel_get_navigation($pageid, $audit = false, $pdf = false) {
	global $DB,$PAGE;
	
	$menu = '
	<div class="exaLabel-Tabs">
		<ul>
			<li'.(($pageid == PAGE_METAINFO) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_METAINFO.'">Angaben zum Lehrgang</a></li>';
	
			foreach($DB->get_records('block_elabel_page') as $page)
				$menu .= '<li'.(($pageid == $page->id) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.$page->id.'">'.$page->shorttitle.'</a></li>';
			
			$menu .= '<li'.(($pageid == PAGE_RESULT) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_RESULT.'">Auswertung</a></li>';
			if($audit) 
				$menu .= '<li'.(($pageid == PAGE_AUDIT) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_AUDIT.'">Audit</a></li>';
			if($pdf)
				$menu .= '<li'.(($pageid == PAGE_PDF) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_PDF.'">Label (PDF)</a></li>';
		$menu .= '
		</ul>
	</div>';
			
	return $menu;
}

function block_elabel_get_page_content($pageid, $request) {
	global $DB;
	if($pageid == PAGE_METAINFO)
		return block_elabel_get_metainfo_page($request);
	
	if($pageid == PAGE_AUDIT)
		return block_elabel_get_audit_page($request);
	
	if($pageid == PAGE_PDF)
		redirect(new moodle_url('label.php?request='.$request->id));
	
	if($pageid > $DB->count_records('block_elabel_page'))
		return block_elabel_get_result_page($request);
		
	return block_elabel_get_evaluation_page($pageid,$request);
}
function block_elabel_get_coursecat_infos($courseid) {
	global $DB;
	$i=0;
	$context = context_course::instance($courseid);
	foreach(array_reverse($context->get_parent_contexts()) as $parentcontext) {
		if($parentcontext->contextlevel == CONTEXT_COURSECAT) {
			$cat = $DB->get_record('course_categories',array('id'=>$parentcontext->instanceid));
			if($i == 0)
				$faculty = $cat->name;
			if($i == 1)
				$department = $cat->name;
			if($i == 2)
				$center = $cat->name;
			
			$i++;
		}
	}
	return array($faculty,@$department,@$center);
}
function block_elabel_get_metainfo_page($data) {
	global $DB,$PAGE,$USER;

	if(!$data) {
		$data = new stdClass();
		
		$data->faculty = '';
		$data->department = '';
		$data->center = '';
		
		$courseid = required_param('labelcourseid', PARAM_INT);
		$i=0;
		$context = context_course::instance($courseid);
		foreach(array_reverse($context->get_parent_contexts()) as $parentcontext) {
			if($parentcontext->contextlevel == CONTEXT_COURSECAT) {
				$cat = $DB->get_record('course_categories',array('id'=>$parentcontext->instanceid));
				if($i == 0)
					$data->faculty = $cat->name;
				if($i == 1)
					$data->department = $cat->name;
				if($i == 2)
					$data->center = $cat->name;
				$i++;
			}
		}
		
		$data->coursename = $DB->get_record('course',array('id' => $courseid))->fullname;
		$data->coursenumber = '';
		$data->internalnumber = '';
		$data->completiontype = '';
		$data->ects = '';
		$data->lessons = '';
		$data->days = '';
		$data->semester = '';
		$data->survey = '';
		$data->urldescription = '';
		$data->urlmoodle = '';
		$data->other = '';
		$data->username = fullname($USER);
		$data->courseteacher = '';
		$data->coursehead = '';
		$data->departmenthead = '';
		$data->year = '';
		$data->departmentnotification = false;
		$data->timecreated = time();
	}
	return '<div style="clear: both;"></div>
			<form name="request" id="request" method="POST" action="'.$PAGE->url.'&pageid=1">
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
						<input id="" class="" type="text" value="'.$data->faculty.'" name="faculty" readonly>
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Department</td>
					<td>
						<input id="" class="" type="text" value="'.$data->department.'" name="department" readonly>
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Zentrum</td>
					<td>
						<input id="" class="" type="text" value="'.$data->center.'" name="center" readonly>
					</td>
				</tr>
				
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Lehrgang</h2>
					</td>
				</tr>
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Lehrgangsbezeichnung</td>
					<td>
						<input id="" class="" type="text" value="'.$data->coursename.'" name="coursename" readonly>
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Nummer oder interne Bezeichnung (optional)</td>
					<td>
						<input id="" class="" type="text" value="'.$data->coursenumber.'" name="coursenumber">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Jahrgang (Start)</td>
					<td>
						<select name="year">
							<option value="WS2013" ' . ((($data->year) == "WS2013") ? ' selected ' : '') .'>WS2013</option>
							<option value="SS2014" ' . ((($data->year) == "SS2014") ? ' selected ' : '') .'>SS2014</option>
							<option value="WS2014" ' . ((($data->year) == "WS2014") ? ' selected ' : '') .'>WS2014</option>
							<option value="SS2015" ' . ((($data->year) == "SS2015") ? ' selected ' : '') .'>SS2015</option>
							<option value="WS2015" ' . ((($data->year) == "WS2015") ? ' selected ' : '') .'>WS2015</option>
							<option value="SS2016" ' . ((($data->year) == "SS2016") ? ' selected ' : '') .'>SS2016</option>
							<option value="WS2016" ' . ((($data->year) == "WS2016") ? ' selected ' : '') .'>WS2016</option>
							<option value="SS2017" ' . ((($data->year) == "SS2017") ? ' selected ' : '') .'>SS2017</option>
							<option value="WS2017" ' . ((($data->year) == "WS2017") ? ' selected ' : '') .'>WS2017</option>
						</select>
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Nummer oder interne Bezeichnung (optional)</td>
					<td>
						<input id="" class="" type="text" value="'.$data->internalnumber.'" name="internalnumber">
					</td>
				</tr>
				
				<tr class="exalabel-Angaben">
					<td class="exalabel-row-right">Lehrgangsabschluss</td>
					<td>
						<select name="completiontype">
							<option value="Master" ' . ((($data->completiontype) == "Master") ? ' selected ' : '') .'>Master</option>
							<option value="Akademische/r Expertin" ' . ((($data->completiontype) == "Akademische/r Expertin") ? ' selected ' : '') .'>Akademische/r Expertin</option>
							<option value="Certified Program" ' . ((($data->completiontype) == "Certified Program") ? ' selected ' : '') .'>Certified Program</option>
						</select>
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
						<input id="" class="" type="text" value="'.(date("d.m.Y",$data->timecreated)).'" name="timecreated" disabled>
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

function block_elabel_get_evaluation_page($pageid, $request) {
	global $DB,$PAGE,$labelconfig;
	
	$page = $DB->get_record('block_elabel_page', array('id'=>$pageid));
	$pageinstance = $DB->get_record('block_elabel_pageinstance', array('requestid'=>$request->id,'pageid'=>$pageid));
	$question_groups = $DB->get_records('block_elabel_questiongroup',array('pageid'=>$pageid));
	
	$answers = $DB->get_records_menu('block_elabel_qinstance',array('requestid'=>$request->id),'','questionid,answer');
	
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
										<option value="'.SELECT_FALSE.'" ' . ((isset($answers[$question->id]) && $answers[$question->id] == SELECT_FALSE) ? ' selected ' : '') .'>gar nicht</option>
										<option value="'.SELECT_PARTLY.'" ' . ((isset($answers[$question->id]) && $answers[$question->id] == SELECT_PARTLY) ? ' selected ' : '') .'>teilweise</option>
										<option value="'.SELECT_TRUE.'"' . ((isset($answers[$question->id]) && $answers[$question->id] == SELECT_TRUE) ? ' selected ' : '') .'>gänzlich</option>
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
				<tr>
					<td class="exalabel-topicev_grad">
						<div id="pagevaluelabel"></div>
						
					</td>
					<td class="exalabel-topicev_gew">
						Gewichtung '.$labelconfig->weights[$page->id].' %
						
					</td>
				</tr>
				<tr class="exalabel-submit">
					<td class="exalabel-slider"><div id="slider"></div></td>
					<td class="exalable-right">
						<div id="min">min: 0</div>
						<div id="max">max: 22</div>
						<div id="score"></div>
						<input type="hidden" id="weightvalue" value="'.$labelconfig->weights[$page->id].'">
						<input type="hidden" id="pagemultiplier" value="'.$labelconfig->multipliers[$page->id].'">
						<input type="hidden" name="pagevalue" id="pagevalue" value="'.((isset($pageinstance->value)) ? $pageinstance->value : 0).'">
					</td>
				</tr>
			<tr class="exalabel-submit">
					<td></td>
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
	global $USER, $DB, $CFG;
	if(!isset($data['formpage']))
		$data['formpage'] = 0;
	
	if($data['formpage'] > 0 && $data['formpage'] < 8) {
		$pageinstance = $DB->delete_records('block_elabel_pageinstance', array('requestid'=>$requestid,'pageid'=>$data['formpage']));
		$DB->insert_record('block_elabel_pageinstance', array('requestid'=>$requestid,'pageid'=>$data['formpage'],'userid'=>$USER->id,'value'=>$data['pagevalue']));
	}
	unset($data['pagevalue']);
	
	$request = $DB->get_record('block_elabel_request',array('id'=>$requestid));
	if($data['formpage'] == 0 && !$request) {
		$data['courseid'] = $courseid;
		$data['userid'] = $USER->id;
		$data['timecreated'] = time();
		
		$DB->delete_records('block_elabel_request',array('courseid'=>$courseid));
		$requestid = $DB->insert_record('block_elabel_request', $data);
		return;
	} elseif($data['formpage'] == 0) {
		foreach($data as $name => $field) {
			$request->{$name} = $field;
		}
		$request->timemodified = time();
		$request->modifiedby = $USER->id;
		$DB->update_record('block_elabel_request', $request);	
		return;
	} elseif($data['formpage'] == PAGE_AUDIT) {
		$data['requestid'] = $requestid;
		$DB->delete_records('block_elabel_audit',array('requestid'=>$requestid));
		$DB->insert_record('block_elabel_audit', $data);
		
		if($request->state == STATUS_REQUESTED) {
			$request->state = STATUS_GRANTED;
			$request->timegranted = time();
			
			$textParams = new stdClass();
			$textParams->coursename = $request->coursename;
			$textParams->label = $CFG->wwwroot.'/blocks/elabel/label.php?request='.$request->id;
			email_to_user($DB->get_record('user', array('id'=>$request->userid)), $USER, get_string('pluginname','block_elabel'), get_string('request_granted','block_elabel',$textParams));
			$DB->update_record('block_elabel_request', $request);
		}
		return;
	}
	
	unset($data['formpage']);
	foreach($data as $id => $answer) {
		$DB->delete_records('block_elabel_qinstance',array('requestid' => $requestid, 'questionid' => $id));
		$DB->insert_record('block_elabel_qinstance', array('questionid'=>$id,'answer'=>$answer,'userid'=>$USER->id,'requestid'=>$requestid));
	}
	
	return;
}
function block_elabel_get_score_for_request($request) {
	global $DB,$labelconfig;
	$records = $DB->get_records('block_elabel_pageinstance',array('requestid'=>$request->id),'pageid');
	$total = 0;
	foreach($records as $record)
		$total += ($record->value * $labelconfig->weights[$record->pageid] / 100);
	
	return $total;
}
function block_elabel_get_result_page($request) {
	global $DB,$labelconfig,$PAGE,$COURSE;

	$records = $DB->get_records('block_elabel_pageinstance',array('requestid'=>$request->id),'pageid');
	$total = block_elabel_get_score_for_request($request);
	
	$content = '
		<form name="request" id="request" method="POST" action="'.new moodle_url('/blocks/elabel/labels.php?courseid='.$COURSE->id).'&action=submit">
		<input type="hidden" name="requestid" value="'.$request->id.'"/>
		<div style="clear: both;"></div>
		<table class="exaLabel-Table">
			<thead>
				<tr>
					<th colspan="3">
						<table class="exaLabel-Table-Head">
							<tr>
								<th class="exHeFi">E-Learning Label</th>
								<th class="exHeSe"><h1>Auswertung</h1></th>
								<th class="exHeTh"><img src="pix/duk_logo_00.png" alt=""></th>
							</tr>
						</table>
					</th>
				</tr>
			</thead>
			
			<tbody>
				';
			if($total >= $labelconfig->labelprofessional)
				$class = "label_professional";
			elseif($total >= $labelconfig->labeladvanced)
				$class = "label_advanced";
			else
				$class = "label_none";
			$content .='
				<tr class="exaLabel-evaluation-head">
					<td>erreichtes Label:</td>
					<td class="'.$class.'">'.get_string($class,'block_elabel').'</td>
					<td>Punkte: '.round($total).'</td>
				</tr>';
				
				$content .= '
				<tr>
					<td colspan="3">
						<div class="radargraph"><canvas id="canvas"></canvas></div>
						<div class="bargraph"><canvas width="50" id="bar_canvas"></canvas>	</div>					
					</td>
				</tr>
				
				
				<tr>
					<td colspan="3">';
						if(!has_capability('block/elabel:audit', context_course::instance($COURSE->id)))
							$content .= '
								<div class="infotext">'.(($request->state < STATUS_REQUESTED) ? get_string('infotext','block_elabel') : get_string('inforequested','block_elabel')).'</div>
								<div class="submitbutton"><input type="submit" value="Antrag absenden"></div>';
					$content .= '</td>
				</tr>
			
			</tbody>
		</table>
		</form>		
				<script>
					var radarChartData = {
						labels: [';
						
							foreach($DB->get_records('block_elabel_page',null,'id') as $page) {
								$content .= '"' . $page->shorttitle . ' ' . $page->title . '(' . $labelconfig->weights[$page->id] .'%)",';
							}
						
						$content .= '],
						datasets: [
							{
								label: "Auswertung",
								fillColor: "rgba(151,187,205,0.2)",
								strokeColor: "rgba(151,187,205,1)",
								pointColor: "rgba(151,187,205,1)",
								pointStrokeColor: "#fff",
								pointHighlightFill: "#fff",
								pointHighlightStroke: "rgba(151,187,205,1)",
								data: [';
									foreach($records as $pageinstance) {
										$content .= $pageinstance->value . ', ';
									}
								$content .= '	
								]
							}
						]
					};
				
					var randomScalingFactor = function(){ return Math.round(Math.random()*100)};

					var barChartData = {
						labels : [""],
						datasets : [
							{
								fillColor : "rgba(151,187,205,0.5)",
								strokeColor : "rgba(151,187,205,0.8)",
								highlightFill : "rgba(151,187,205,0.75)",
								highlightStroke : "rgba(151,187,205,1)",
								data : [' .$total.'],
							}
						]
				
					}
					window.onload = function(){
					window.myRadar = new Chart(document.getElementById("canvas").getContext("2d")).Radar(radarChartData, {
						responsive: true
					});
					var ctx = document.getElementById("bar_canvas").getContext("2d");
					window.myBar = new Chart(ctx).Bar(barChartData, {
						responsive : true
					});
	}</script>';
	
	return $content;
}
function block_elabel_submit_request($requestid) {
	global $CFG,$COURSE,$DB,$USER;
	$request = $DB->get_record('block_elabel_request',array('id'=>$requestid));
	$request->state = STATUS_REQUESTED;
	$request->timesubmitted = time();
	
	$DB->update_record('block_elabel_request', $request);
	//write email notification to course teachers
	$teachers = block_elabel_get_course_teachers(context_course::instance($request->courseid));
	$textParams = new stdClass();
	$textParams->username = $request->username;
	$textParams->coursename = $request->coursename;
	$textParams->form = $CFG->wwwroot . '/blocks/elabel/request.php?courseid=' . $COURSE->id .'&labelcourseid='.$request->courseid.'&requestid='.$request->id;
	foreach($teachers as $teacher) {
		email_to_user($teacher, $USER, get_string('pluginname','block_elabel'), get_string('request_sent','block_elabel',$textParams));
	}
}
function block_elabel_get_course_teachers($coursecontext) {
	return array_merge(get_role_users(4, $coursecontext),get_role_users(3, $coursecontext),get_role_users(2, $coursecontext),get_role_users(1, $coursecontext));
}
function block_elabel_get_audit_page($request) {
	global $DB,$labelconfig,$PAGE;
	
	$total = block_elabel_get_score_for_request($request);
	
	if($total >= $labelconfig->labelprofessional)
		$class = "label_professional";
	elseif($total >= $labelconfig->labeladvanced)
		$class = "label_advanced";
	else
		$class = "label_none";
	
	$audit = $DB->get_record('block_elabel_audit',array('requestid'=>$request->id));
	
	if(!isset($audit->timecreated)) {
		$audit = new stdClass();
		$audit->timecreated = time();
	}
	
	return 
	'
	<form name="request" id="request" method="POST" action="'.$PAGE->url.'&pageid='.PAGE_AUDIT.'">
	<div style="clear: both;"></div>
		<table class="exaLabel-Table">
			<thead>
				<tr>
					<th colspan="2">
						<table class="exaLabel-Table-Head">
							<tr>
								<th class="exHeFi">E-Learning Label</th>
								<th class="exHeSe"><h1>Audit</h1></th>
								<th class="exHeTh"><img src="pix/duk_logo_00.png" alt=""></th>
							</tr>
						</table>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Angaben und Auswertung</h2>
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Fortlaufende Nummer:</td>
					<td>
						'.$request->id.'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Gültig ab:</td>
					<td>
						'.date("d.m.Y",$request->timecreated).'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Kontaktperson:</td>
					<td>
						'.$request->username.'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Label:</td>
					<td>
						'.get_string($class,'block_elabel').'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Punktebewertung:</td>
					<td>
						Punkte: '.$total.'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Antragsvoraussetzung:</td>
					<td>
						<input type="text" name="requirement" value="'.(isset($audit->requirement) ? $audit->requirement : '').'"/>
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Lehrgangsbezeichnung:</td>
					<td>
						'.$request->coursename.'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Studienkennzahl:</td>
					<td>
						'.$request->coursenumber.'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Nummer od. interne Bezeichnung:</td>
					<td>
						'.$request->internalnumber.'
					</td>
				</tr>
				<tr class="exalabel-Angaben-Audit">
					<td class="exalabel-row-right">Jahrgang (Start):</td>
					<td>
						'.$request->year.'
					</td>
				</tr>
			</tbody>
		</table>
		<table class="exaLabel-Table exaLabel-TableSec">
			<tbody>
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Protokoll</h2>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">Anmerkungen:</td>
					<td>
						<input type="text" name="note" value="'.(isset($audit->note) ? $audit->note : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">Evaluation:</td>
					<td>
						<textarea name="evaluation" rows="4" cols="50">'.((isset($audit->evaluation)) ? $audit->evaluation : '').'</textarea>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">Empfehlungen:</td>
					<td>
						<input type="text" name="recommendation" value="'.(isset($audit->recommendation) ? $audit->recommendation : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">Auflagen:</td>
					<td>
						<input type="text" name="requirements" value="'.(isset($audit->requirements) ? $audit->requirements : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">TeilnehmerInnen:</td>
					<td>
						<input type="text" name="participants" value="'.(isset($audit->participants) ? $audit->participants : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">Für das Protokoll:</td>
					<td>
						<input type="text" name="protocol" value="'.(isset($audit->protocol) ? $audit->protocol : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row25">Datum des Audits:</td>
					<td>
						<input type="hidden" name="timecreated" value="'.$audit->timecreated.'"/>
						<input type="text"value="'.date("d.m.Y",$audit->timecreated).'" disabled/>
					</td>
				</tr>
				<tr class="exalabel-submit">
					<td></td>
					<td class="exalable-right">
						<input type="hidden" name="formpage" value="'.PAGE_AUDIT.'"/>
						<input type="submit" value="Absenden">
					</td>
				</tr>
			</tbody>
		</table>
		</form>';
}