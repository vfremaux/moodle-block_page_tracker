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
 * Block backup tasks
 *
 * @package         block_page_tracker
 * @author          Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright       2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/page_tracker/backup/moodle2/backup_page_tracker_stepslib.php');

/**
 * Specialised backup task for the html block
 * (requires encode_content_links in some configdata attrs)
 */
class backup_page_tracker_block_task extends backup_block_task {

    /**
     * Settings definition
     */
    protected function define_my_settings() {
    }

    /**
     * Steps definition
     */
    protected function define_my_steps() {
        // Block page_tracker has one structure step.
        $this->add_step(new backup_page_tracker_block_structure_step('page_tracker_structure', 'page_tracker.xml'));
    }

    /**
     * Associated fileareas
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Encode block configutation
     */
    public function get_configdata_encoded_attributes() {
        return []; // We need to encode some attrs in configdata.
    }

    /**
     * Encode embedded links
     */
    public static function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}
