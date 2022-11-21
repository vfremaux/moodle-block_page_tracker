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
 * @subpackage  blocks
 * @copyright   2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php'); // Cli only functions.

list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'host' => true,
        'courses' => true,
        'generatelinks' => true,
        'hidedisabled' => true,
        'usesummarynames' => true,
        'hideaccessmarks' => false,
        'showanyway' => false,
        'initiallyopened' => false,
        'levels' => false
    ],
    [
        'h' => 'help',
        'H' => 'host',
        'C' => 'courses',
        'g' => 'generatelinks',
        'd' => 'hidedisabled',
        'n' => 'usesummarynames',
        'm' => 'hideaccessmarks',
        's' => 'showanyway',
        'o' => 'initiallyopened',
        'l' => 'levels'
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if ($options['help']) {
    $help = "
Set or unset globally config choices for all instances.

Options:
-h, --help            Print out this help
-H, --host            the virtual host you are working for
-C, --courses         If given as a comma separated list of course id, restrict to those courses
-g, --generatelinks   Generate links : 0 no, 1 on visited pages, 2 yes
-d, --hidedisabled    Hide not accessible pages : 0 or 1
-n, --usesummarynames Use summary names : 0 or 1
-m, --hideaccessmarks Hide access marks (ticks) : 0 or 1
-s, --showanyway      Show anyway : 0 or 1
-o, --initiallyopened Initially expaned : 0 or 1
-l, --levels          Levels, 100 for all, 1, 2 or 3 (other positive ints could be valid value)

Example:
\$sudo -u www-data /usr/bin/php blocks/page_tracker/cli/change_config.php
";

    echo $help;
    exit(0);
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot."\n");
require_once($CFG->dirroot.'/blocks/page_tracker/db/upgrade.php');
require_once($CFG->dirroot.'/blocks/page_tracker/locallib.php');

$allinstances = $DB->get_records('block_instances', ['blockname' => 'page_tracker']);

if (!empty($options['courses'])) {
    $courseids = explode(',', $options['courses']);
    $contexts = [];
    foreach ($courseids as $cid) {
        $contexts[] = context_course::instance($cid)->id;
    }
}

$instances = [];

// Restrict by courses if told to do so.
if (!empty($contexts)) {
    foreach ($allinstances as $i) {
        if (in_array($i->parentcontextid, $contexts)) {
            $instances[$i->id] = $i;
        }
    }
} else {
    $instances = $allinstances;
}

foreach ($instances as $i) {
    $condigdata = (array) unserialize_object(base64_decode($i->configdata));

    foreach ($configdata as $key => $datum) {
        if (array_key_exists($key, $options)) {
            $configdata[$key] = $options[$key];
        }
    }

    $i->configdata = base64_encode(serialize_object((object)$configdata));
    echo "Updating config for block instance $i->id\n";
    $DB->update_record('block_instances', $i);
}

echo "All done.\n";
exit(0);