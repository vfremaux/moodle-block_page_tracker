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
 * Form for editing HTML block instances.
 *
 * @package   block_livedesk
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
if (!defined('MOODLE_INTERNAL')) die ('You cannot use this script this way');

class block_page_tracker_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
    	global $DB, $CFG, $COURSE;
    	
        // Fields for editing HTML block title and contents.

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_page_tracker'));
        $mform->setType('config_title', PARAM_MULTILANG);
	
    	$linkoptions['0'] = get_string('no');
    	$linkoptions['1'] = get_string('yesonvisited', 'block_page_tracker');
    	$linkoptions['2'] = get_string('yes');
		$mform->addElement('select', 'config_allowlinks', get_string('allowlinks', 'block_page_tracker'), $linkoptions);

    	$pageoptions = array();
    	$pageoptions['0'] = get_string('root', 'block_page_tracker');
    	$pages = course_page::get_all_pages($COURSE->id);
    	foreach($pages as $p){
    		$pageoptions[$p->id] = format_string($p->nametwo);
    	}
		$mform->addElement('select', 'config_startpage', get_string('startpage', 'block_page_tracker'), $pageoptions);

    	$leveloptions = array();
    	$leveloptions['100'] = get_string('alllevels', 'block_page_tracker');
    	for($i = 1; $i <= 3; $i++){
    		$leveloptions[$i] = $i;
    	}
		$mform->addElement('select', 'config_depth', get_string('depth', 'block_page_tracker'), $leveloptions);
		
    }

    function set_data($defaults, &$files = null) {

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        parent::set_data($defaults);
        if (isset($title)) {
            // Reset the preserved title
            $this->block->config->title = $title;
        }
    }
}
