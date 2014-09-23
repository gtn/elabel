<?php
function xmldb_block_elabel_install() {
	require_once dirname(__FILE__)."/../inc.php";
	global $DB, $OUTPUT, $PAGE, $CG;
	
	$json = file_get_contents(dirname(__FILE__).'/questions.json');
	$pages = json_decode($json);
	
	foreach($pages as $page) {
		$pageid = $DB->insert_record('block_elabel_page', $page);
		foreach($page->questiongroups as $questiongroup) {
			$questiongroup->pageid = $pageid;
			$questiongroupid = $DB->insert_record('block_elabel_questiongroup', $questiongroup);
			foreach($questiongroup->questions as $question) {
				$question->questiongroupid = $questiongroupid;
				$question->questiontype = ($question->type == 'dropdown') ? 0 : 1;
				$DB->insert_record('block_elabel_question', $question);
			}
		}
	}
}