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

require_once($CFG->dirroot.'/blocks/roster_tbird/lib.php');

/**
 * Form for editing HTML block instances.
 *
 * @package   block_html
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_roster_tbird extends block_list {

	public function init() {
		$this->title = get_string('blocktitle', 'block_roster_tbird');
	}

	function has_config() {
		return true;
	}
	
	public function specialization() {
		if (!empty($this->config->title)) {
			$this->title = $this->config->title;
		}
	}
	
	//only usable on courses
	//this will also filter out front page which has format 'site-index'
	public function applicable_formats() {
		return array('course-view' => true);
	}
	
	//a single instance per course
	public function instance_allow_multiple() {
		return false;
	}

    public function get_content() {
        global $COURSE, $CFG;
        
        if ($this->content !== null) {
        	return $this->content;
        }
        
        $this->content =  new stdClass;
        
        $coursecontext = context_course::instance($COURSE->id);
        
        // check if viewparticipants is allowed
		if (has_capability('moodle/course:viewparticipants', $coursecontext)) {
			// start with clean slate
			$this->content->items = array();
			$this->content->icons = array();
			//$this->content->text   = '';
			$this->content->footer = '';

			//$icon_code = '<img src="'.$CFG->pixpath.'/i/users.gif" class="icon" alt="" />';
			$icon_code = html_writer::empty_tag('img', array('src' => '/pix/i/users.gif', 'class' => 'icon'));
			
			//no need to check for is_null($this->config), since we use formelement "advcheckbox" in edit_form.php
			$basehref = $CFG->wwwroot.'/blocks/roster_tbird/view.php?contextid='. $coursecontext->id .'&mode=';
			if ($this->config->flaglinknames) {
				//$this->content->items[] = $this->_roster_link(MODE_BRIEF,$coursecontext->id);
				$this->content->items[] = '<a title="'.get_string('listnames','block_roster_tbird').'" href="' . $basehref . MODE_NAMES . '">' . get_string('linknames', 'block_roster_tbird'). '</a>'; 
				$this->content->icons[] = $icon_code;
			}
			//if ($this->config->flaglinkfull) {
			//	//$this->content->items[] = $this->_roster_link(MODE_DETAILS,$coursecontext->id);
			//	$this->content->items[] = '<a title="'.get_string('listdetails','block_roster_tbird').'" href="' . $basehref . MODE_DETAILS . '">' . get_string('linkdetails', 'block_roster_tbird'). '</a>'; 
			//	$this->content->icons[] = $icon_code;
			//}
			if ($this->config->flaglinkdescription) {
				//$this->content->items[] = $this->_roster_link(MODE_DESCRIPTION,$coursecontext->id);
				$this->content->items[] = '<a title="'.get_string('listdescriptions','block_roster_tbird').'" href="' . $basehref . MODE_DESCRIPTION . '">' . get_string('linkdescription', 'block_roster_tbird'). '</a>'; 
				$this->content->icons[] = $icon_code;
			}
			if ($this->config->flaglinkusermanagement) {
				$this->content->items[] = '<a title="'.get_string('listenrolments','block_roster_tbird').'" href="'.$CFG->wwwroot.'/user/index.php?contextid='.$coursecontext->id.'">'
				.get_string('linkusermanagement','block_roster_tbird').'</a>';
				$this->content->icons[] = $icon_code;
			}
				
		}
		
		return $this->content;
    }


}
