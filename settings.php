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

defined('MOODLE_INTERNAL') || die();

/**
 * @package   block_page_tracker
 * @category  blocks
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$linkoptions = array();
$linkoptions['0'] = get_string('no');
$linkoptions['1'] = get_string('yesonvisited', 'block_page_tracker');
$linkoptions['2'] = get_string('yes');

$settings->add(new admin_setting_configselect('block_page_tracker/defaultallowlinks', get_string('configdefaultallowlinks', 'block_page_tracker'),
                   get_string('configdefaultallowlinks_desc', 'block_page_tracker'), 2, $linkoptions));

$settings->add(new admin_setting_configcheckbox('block_page_tracker/defaulthidedisabledlinks', get_string('configdefaulthidedisabledlinks', 'block_page_tracker'),
                   get_string('configdefaulthidedisabledlinks_desc', 'block_page_tracker'), true));

$leveloptions = array();
$leveloptions['100'] = get_string('alllevels', 'block_page_tracker');
for ($i = 1; $i <= 3; $i++) {
    $leveloptions[$i] = $i;
}
$settings->add(new admin_setting_configselect('block_page_tracker/defaultdepth', get_string('configdefaultdepth', 'block_page_tracker'),
                   get_string('configdefaultdepth_desc', 'block_page_tracker'), 100, $leveloptions));

$settings->add(new admin_setting_configcheckbox('block_page_tracker/defaultusemenulabels', get_string('configdefaultusemenulabels', 'block_page_tracker'),
                   get_string('configdefaultusemenulabels_desc', 'block_page_tracker'), true));

$settings->add(new admin_setting_configcheckbox('block_page_tracker/defaulthideaccessbullets', get_string('configdefaulthideaccessbullets', 'block_page_tracker'),
                   get_string('configdefaulthideaccessbullets_desc', 'block_page_tracker'), false));
