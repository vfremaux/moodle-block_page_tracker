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
 * @package     block_page_tracker
 * @copyright   2008 onwards Valery Fremaux <http://docs.activeprolearn.com/en>
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$id = required_param('id', PARAM_INT); // Current course id.
$itemid = required_param('itemid', PARAM_INT); // the page id.
$blockid = required_param('blockid', PARAM_INT);
$action = required_param('what', PARAM_ALPHA);

if (!$DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('badsectionid');
}

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('coursemisconf');
}

require_login($course);

if ($action == 'collapse') {

    if (!isset($SESSION->pagetracker)) {
        $SESSION->pagetracker = new StdClass;
    }

    $trackid = $blockid.'_'.$itemid;
    $SESSION->pagetracker->$trackid = 0;
    echo "collapse recorded";
} else if ($action == 'expand') {

    if (!isset($SESSION->pagetracker)) {
        $SESSION->pagetracker = new StdClass;
    }

    $trackid = $blockid.'_'.$itemid;
    $SESSION->pagetracker->$trackid = 1;
    echo "expand recorded";
}
