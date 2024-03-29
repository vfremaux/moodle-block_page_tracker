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
 * @package    block_page_tracker
 * @category   blocks
 * @copyright  2003 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('PAGE_TRACKER_NOLINKS', 0);
define('PAGE_TRACKER_LINKSVISITED', 1);
define('PAGE_TRACKER_LINKS', 2);

function punch_track($courseid, $pageid, $userid) {
    global $DB;

    if (empty($pageid)) {
        return;
    }

    $params = array('courseid' => $courseid, 'pageid' => $pageid, 'userid' => $userid);
    if (!$track = $DB->get_record('block_page_tracker', $params)) {
        $track = new StdClass;
        $track->courseid = $courseid;
        $track->pageid = $pageid;
        $track->userid = $userid;

        $track->firsttimeviewed = time();
        $track->lasttimeviewed = time();
        $track->views = 1;
        $DB->insert_record('block_page_tracker', $track);
    } else {
        $track->lasttimeviewed = time();
        $track->views++;
        $DB->update_record('block_page_tracker', $track);
    }
}

/**
 * Forges a simplified template tree for debug display.
 */
function block_page_tracker_debug_print_tree($template) {
    $simplified = new Stdclass;
    $simplified->hassubs = $template->hassubs;
    $simplified->pagename = $template->pagename;
    if (!empty($template->pages)) {
        foreach ($template->pages as $p) {
            $simplified->pages[] = block_page_tracker_debug_print_tree($p);
        }
    }

    return $simplified;
}
