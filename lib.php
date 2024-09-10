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
 * Block main library
 *
 * @package    block_page_tracker
 * @author          Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright       2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function is not implemented in this plugin, but is needed to mark
 * the vf documentation custom volume availability.
 * @param string $feature checks availability of a feature
 * @param bool $getsupported if true returns the list of supported features
 */
function block_page_tracker_supports_feature($feature = null, $getsupported = false) {
    if ($getsupported) {
        return [];
    }

    return '';
}
