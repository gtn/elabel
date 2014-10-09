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

define('PAGE_HOWTO', 0);
define('PAGE_METAINFO',50);
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
/**
 * Is used to fetch all requests and display them to a course teacher
 * @param String $sorting
 */
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
/**
 * Is used to fetch all user courses he is a teacher in and he can request a label for
 */
function block_elabel_get_my_courses() {
	global $DB, $USER;
	
	$courses = enrol_get_my_courses();
	$data = array();
	foreach($courses as $course) {
		//skip course if the is not a teacher
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
/**
 * Builds navigation in the form
 * @param int $pageid
 * @param boolean $audit
 * @param boolean $pdf
 */
function block_elabel_get_navigation($pageid, $audit = false, $pdf = false) {
	global $DB,$PAGE;
	/*
	$menu = '
	<div class="exaLabel-Tabs">
		<ul>
			<li'.(($pageid == PAGE_HOWTO) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_HOWTO.'">Anleitung</a></li>
		
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
	</div>';*/
		if($pageid == PAGE_METAINFO) 
			$title = "Angaben zum Lehrgang";
		else if($pageid == PAGE_HOWTO)
			$title = "Anleitung";
		else if($pageid == PAGE_RESULT || $pageid == 8)
			$title = "Auswertung";
		else if($pageid == PAGE_AUDIT)
			$title = "Audit";
		else
			$title = "Evaluation";
		
	$colspan = ($pageid == PAGE_RESULT || $pageid == 8) ? 3 : 2;
		$menu = '
		<div style="clear: both;"></div>
		<table class="exaLabel-Table" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th colspan="'.$colspan.'">
						<table class="exaLabel-Table-Head" cellspacing="0" cellpadding="0">
							<tr>
								<th colspan="3" class="exHeSe exTitle"><h1>'.$title.'</h1></th>
							</tr>
							<tr>
								<th colspan="3" class="exHeSe exHeSeNav">
									<div class="exaLabel-Tabs">
										<ul>
											<li'.(($pageid == PAGE_HOWTO) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_HOWTO.'">Anleitung</a></li>
										
											<li'.(($pageid == PAGE_METAINFO) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_METAINFO.'">Angaben<br/>zum<br/>Lehrgang</a></li>';
									
											foreach($DB->get_records('block_elabel_page') as $page)
												$menu .= '<li'.(($pageid == $page->id) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.$page->id.'">'.$page->shorttitle.'<br/>'.str_replace(' ','<br/>',$page->title).'</a></li>';
											
											$menu .= '<li'.(($pageid == PAGE_RESULT) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_RESULT.'">Auswertung</a></li>';
											if($audit) 
												$menu .= '<li'.(($pageid == PAGE_AUDIT) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_AUDIT.'">Audit</a></li>';
											if($pdf)
												$menu .= '<li'.(($pageid == PAGE_PDF) ? ' class="active" ' : '' ).'><a name="formnav" href="'.$PAGE->url . '&pageid='.PAGE_PDF.'">Label (PDF)</a></li>';
										$menu .= '
										</ul>
									</div>
								</th>
							</tr>
						</table>
					</th>
				</tr>
			</thead>
		';
			
	return $menu;
}
function block_elabel_get_page_content($pageid, $request) {
	global $DB;
	if($pageid == PAGE_HOWTO)
		return block_elabel_get_howto_page($request);
	
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
/**
 * Get faculty, department and center information from course categories.
 * top level = faculty
 * 2nd level = department
 * 3rd level = center
 * @param int $courseid
 */
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
/**
 * Prints first form page
 */
function block_elabel_get_metainfo_page($data) {
	global $DB,$PAGE,$USER;

	if(!$data) {
		$data = new stdClass();
		
		$data->faculty = '';
		$data->department = '';
		$data->center = '';
		
		$courseid = required_param('labelcourseid', PARAM_INT);
		list($data->faculty,$data->department,$data->center) = block_elabel_get_coursecat_infos($courseid);

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
	return '
			<form name="request" id="request" method="POST" action="'.$PAGE->url.'&pageid=1">
			<input type="hidden" name="formpage" value="'.PAGE_METAINFO.'">
			
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
						<textarea rows="4" cols="50" name="other">'.$data->other.'</textarea>
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
		</form>
	</table>';
}

/**
 * Prints first form page
 */
function block_elabel_get_howto_page($data) {
	global $DB,$PAGE,$USER;

	
	return '
	<form name="request" id="request" method="POST" action="'.$PAGE->url.'&pageid='.PAGE_HOWTO.'">
	<input type="hidden" name="formpage" value="0">
		
	<tbody>

	<tr>
	<td class="exaLabel-Description-head" colspan="2"><h2>Durchführung der Selbstevaluation</h2>
	</td>
	</tr>
	
	<tr class="exalabel-Angaben">
	<td colspan="2">
		Die Evaluation erfolgt anhand der Arbeitsblätter <b>Angaben zum LG</b> und der sieben Evaluationsformulare <b>1, 2.1, 2.2, 3.1, 3.2, 3.3, 4.</b><br/>
		Eintragungen sind jeweils in den rot umrandeten Feldern vorgesehen. Die Selbstevaluation anhand der
		Evaluationsformulare besteht aus zwei Schritten: <br/><br/>

		1.) Anhand von Indikatoren wird das jeweilige Evaluationskriterium erläutert. Es erfolgt eine Orientierung durch Angaben (Listbox) in der Spalte <b><trifft zu></b>. Abhängig von der dreistufigen Auswahl   ("gar nicht", "teilweise", "gänzlich") wird ein min-Wert und ein max-Wert ermittelt. Es erscheint in Abhängigkeit dieser Werte der Zellbereich des Sliders in grüner oder roter Schattierung. Die  min- und max-Werte führen zur Eingrenzung des Evaluationsbereiches. Da die Indikatoren jedoch keine vollständige Charakterisierung zulassen, kann der Slider  über die Grenzen der beiden Werte hinausverschoben werden.
		<br/><br/>
		2.) Die eigentliche Selbstevaluation erfolgt durch Angabe eines <b>Erfüllungsgrades [in %]</b> mittels Positionierung eines Sliders auf einer Prozentskala (siehe Beispielabbildung).
		<div class="exaLabel-howto-text">
			<img style="width:70%" src="pix/slider.png">
		</div>
	</td>
	</tr>

	<tr>
	<td class="exaLabel-Description-head" colspan="2"><h2>Berechnung</h2>
	</td>
	</tr>
	<tr class="exalabel-Angaben">
	<td colspan="2">
		Die Berechnung und Auswertung kann dem Arbeitsblatt Auswertung entnommen werden.
		Die Berechnung erfolgt durch Ermittlung von Punktewerten, die sich aus aus dem jeweiligen eingegebenen Erfüllungsgrad und der zugehörigen vorgegebenen Gewichtung ergibt.
		Aus den dreistufigen Angaben in den Feldern <trifft zu> resultiert eine Bereichseinschränkung des möglichen Erfüllungsgrades, die jedoch von der Erfüllungsgradangabe mittels Slider überschrieben werden kann.
		Ausschliesslich die mittels Slider vorgenommene Eingabe wird für die Berechnung herangezogen.
		Die Auswertung zeigt den Erfüllungsgrad pro Kategorie an, sowie einen kumulativen Punktewert, der als Kriterium für die Labelzuteilung dient.
	</td>
	</tr>
	<tr>
	<td class="exaLabel-Description-head" colspan="2"><h2>Was ist noch zu tun?</h2>
	</td>
	</tr>
	<tr class="exalabel-Angaben">
	<td colspan="2">
	Die Auswertung bildet die Grundlage für den anschliessenden Audit. Das fertig ausgefüllte Formular bitte abspeichern
	und senden an: elearning@donau-uni.ac.at  mit Betreff: Selbstevaluation.
	Im Anschluss daran erfolgt der Audit am E-Learning Center nach Terminvereinbarung.	
	</td>
	</tr>
	</tbody>
	</table>
	<input type="hidden" name="formpage" value="'.PAGE_HOWTO.'"/>
	</form>
	</table>';
}
/**
 * Builds evaluation form pages, based on the questions and questiongroups in the database
 * 
 * @param int $pageid
 * @param object $request
 */
function block_elabel_get_evaluation_page($pageid, $request) {
	global $DB,$PAGE,$labelconfig;
	
	$page = $DB->get_record('block_elabel_page', array('id'=>$pageid));
	$pageinstance = $DB->get_record('block_elabel_pageinstance', array('requestid'=>$request->id,'pageid'=>$pageid));
	$question_groups = $DB->get_records('block_elabel_questiongroup',array('pageid'=>$pageid));
	
	$answers = $DB->get_records_menu('block_elabel_qinstance',array('requestid'=>$request->id),'','questionid,answer');
	
	$content = '
		<form name="request" id="request" method="POST" action="'.$PAGE->url.'&pageid='.($pageid+1).'">
		<input type="hidden" name="formpage" value="'.$pageid.'">
			<tbody>
				<tr>
					<td class="exaLabel-Description-head" colspan="2">
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
					<td rowspan="2" class="exalabel-slider"><div id="slider"></div></td>
					<td class="exalable-right">
						<div id="min">min: 0</div>
						<div id="max">max: 22</div>
						<input type="hidden" id="weightvalue" value="'.$labelconfig->weights[$page->id].'">
						<input type="hidden" id="pagemultiplier" value="'.$labelconfig->multipliers[$page->id].'">
						<input type="hidden" name="pagevalue" id="pagevalue" value="'.((isset($pageinstance->value)) ? $pageinstance->value : 0).'">
					</td>
				</tr>
				<tr>
					<td><div id="score"></div>
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
		$data['formpage'] = PAGE_METAINFO;
	
	if($data['formpage'] > 0 && $data['formpage'] < 8) {
		$pageinstance = $DB->delete_records('block_elabel_pageinstance', array('requestid'=>$requestid,'pageid'=>$data['formpage']));
		$DB->insert_record('block_elabel_pageinstance', array('requestid'=>$requestid,'pageid'=>$data['formpage'],'userid'=>$USER->id,'value'=>$data['pagevalue']));
	}
	unset($data['pagevalue']);

	$request = $DB->get_record('block_elabel_request',array('id'=>$requestid));
	if($data['formpage'] == PAGE_METAINFO && !$request) {
		$data['courseid'] = $courseid;
		$data['userid'] = $USER->id;
		$data['timecreated'] = time();
		
		$DB->delete_records('block_elabel_request',array('courseid'=>$courseid));
		$requestid = $DB->insert_record('block_elabel_request', $data);
		return;
	} elseif($data['formpage'] == PAGE_METAINFO) {
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
/**
 * Submit a request, set the state to REQUESTED and notify course teachers via mail
 * @param int $requestid
 */
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
						Punkte: '.round($total,0).'
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
				<tr>
					<td class="exaLabel-Description-head" colspan="2"><h2>Protokoll</h2>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">Anmerkungen:</td>
					<td>
						<textarea name="note" rows="4" cols="50">'.((isset($audit->note)) ? $audit->note : '').'</textarea>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">Evaluation:</td>
					<td>
						<textarea name="evaluation" rows="4" cols="50">'.((isset($audit->evaluation)) ? $audit->evaluation : '').'</textarea>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">Empfehlungen:</td>
					<td>
						<textarea name="recommendation" rows="4" cols="50">'.((isset($audit->recommendation)) ? $audit->recommendation : '').'</textarea>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">Auflagen:</td>
					<td>
						<textarea name="requirements" rows="4" cols="50">'.((isset($audit->requirements)) ? $audit->requirements : '').'</textarea>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">TeilnehmerInnen:</td>
					<td>
						<input type="text" name="participants" value="'.(isset($audit->participants) ? $audit->participants : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">Für das Protokoll:</td>
					<td>
						<input type="text" name="protocol" value="'.(isset($audit->protocol) ? $audit->protocol : '').'"/>
					</td>
				</tr>
				<tr>
					<td class="exalabel-row-right">Datum des Audits:</td>
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

