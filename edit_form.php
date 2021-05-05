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
 * @package   block_page_tracker
 * @category  blocks
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

use \format\page\course_page;

class block_page_tracker_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $CFG, $COURSE;

        $config = get_config('block_page_tracker');

        // Fields for editing HTML block title and contents.

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_page_tracker'));
        $mform->setType('config_title', PARAM_MULTILANG);

        $linkoptions[PAGE_TRACKER_NOLINKS] = get_string('no');
        $linkoptions[PAGE_TRACKER_LINKSVISITED] = get_string('yesonvisited', 'block_page_tracker');
        $linkoptions[PAGE_TRACKER_LINKS] = get_string('yes');
        $label = get_string('allowlinks', 'block_page_tracker');
        $mform->addElement('select', 'config_allowlinks', $label, $linkoptions, @$config->defaultallowlinks);
        $mform->setDefault('config_allowlinks', PAGE_TRACKER_LINKS);
        $mform->addHelpButton('config_allowlinks', 'allowlinks', 'block_page_tracker');

        $mform->addElement('advcheckbox', 'config_hidedisabledlinks', get_string('hidedisabledlinks', 'block_page_tracker'));
        $mform->setDefault('config_hidedisabledlinks', $config->defaulthidedisabledlinks);
        $mform->setAdvanced('config_hidedisabledlinks');

        $pageoptions = array();
        $pageoptions['0'] = get_string('root', 'block_page_tracker');
        $pageoptions['-1'] = get_string('self', 'block_page_tracker');
        $pageoptions['-2'] = get_string('parent', 'block_page_tracker');
        $pageoptions['-3'] = get_string('selfupper', 'block_page_tracker');
        $pages = course_page::get_all_pages($COURSE->id, 'flat');
        foreach ($pages as $p) {
            $pageoptions[$p->id] = format_string($p->nametwo);
        }
        $mform->addElement('select', 'config_startpage', get_string('startpage', 'block_page_tracker'), $pageoptions);
        $mform->setDefault('config_startpage', isset($config->defaultstartpage) ? $config->defaultstartpage : 0);

        $leveloptions = array();
        $leveloptions['100'] = get_string('alllevels', 'block_page_tracker');
        for ($i = 1; $i <= 3; $i++) {
            $leveloptions[$i] = $i;
        }
        $mform->addElement('select', 'config_depth', get_string('depth', 'block_page_tracker'), $leveloptions);
        $mform->setDefault('config_depth', isset($config->defaultdepth) ? $config->defaultdepth : 100);

        $mform->addElement('advcheckbox', 'config_usemenulabels', get_string('usemenulabels', 'block_page_tracker'), '');
        $mform->setDefault('config_usemenulabels', isset($config->defaultusemenulabels) ? $config->defaultusemenulabels : true);
        $mform->addHelpButton('config_usemenulabels', 'usemenulabels', 'block_page_tracker');
        $mform->setAdvanced('config_usemenulabels');

        $mform->addElement('advcheckbox', 'config_hideaccessbullets', get_string('hideaccessbullets', 'block_page_tracker'), '');
        $mform->setDefault('config_hideaccessbullets', isset($config->defaulthideaccessbullets) ? $config->defaulthideaccessbullets : false);
        $mform->setAdvanced('config_hideaccessbullets');

        $mform->addElement('advcheckbox', 'config_showanyway', get_string('showanyway', 'block_page_tracker'), '');
        $mform->setDefault('config_showanyway', 0);
        $mform->disabledIf('config_showanyway', 'config_allowlinks', 'eq', PAGE_TRACKER_LINKS);
        $mform->addHelpButton('config_showanyway', 'showanyway', 'block_page_tracker');

        $mform->addElement('advcheckbox', 'config_initialexpanded', get_string('initiallyexpanded', 'block_page_tracker'), '');
        $mform->setDefault('config_initialexpanded', 0);
    }

    public function set_data($defaults, &$files = null) {

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely.
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        parent::set_data($defaults);
        if (isset($title)) {
            // Reset the preserved title.
            $this->block->config->title = $title;
        }
    }
}
