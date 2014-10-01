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
 * elabel block caps.
 *
 * @package    block_elabel
 * @copyright  Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_elabel extends block_list {

	function init() {
		$this->title = get_string('pluginname', 'block_elabel');
	}

	function get_content() {
		global $CFG, $OUTPUT, $COURSE;

		if ($this->content !== null) {
			return $this->content;
		}

		if (empty($this->instance)) {
			$this->content = '';
			return $this->content;
		}

		$this->content = new stdClass();
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		$this->content->items[] = html_writer::link(new moodle_url('/blocks/elabel/labels.php', array('courseid'=>$COURSE->id)), get_string('tab_labels', 'block_elabel'), array('title'=>get_string('tab_labels', 'block_elabel')));
		$this->content->icons[] = html_writer::empty_tag('img', array('src'=>new moodle_url('/blocks/elabel/pix/reminders.png'), 'alt'=>"", 'height'=>16, 'width'=>23));

		return $this->content;
	}


	public function instance_allow_multiple() {
		return false;
	}

}