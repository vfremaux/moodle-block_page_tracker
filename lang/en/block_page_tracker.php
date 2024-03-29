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
 * Block Page Tracker Language File
 *
 * @author Valery Fremaux
 * @version $Id: block_page_tracker.php,v 1.4 2012-02-16 19:53:55 vf Exp $
 * @package block_page_tracker
 */

// Capabilities.
$string['page_tracker:addinstance'] = 'Can add an instance';
$string['page_tracker:accessallpages'] = 'Can access all pages';

// Privacy.
$string['privacy:metadata'] = 'The Page Tracker block does not store any personal data about any user.';

$string['alllevels'] = 'All levels';
$string['allowlinks'] = 'Allow links generation';
$string['blockname'] = 'Learning Stations';
$string['configtitle'] = 'Block title (leave blank for standard title).';
$string['depth'] = 'Depth';
$string['displayerror'] = 'An error occured in displaying this module.';
$string['errormissingpage'] = 'The start page configured for this block seems being gone. Please reconfigure the block.';
$string['pluginname'] = 'Learning Stations';
$string['root'] = 'Course root';
$string['self'] = '-- The current page';
$string['selfupper'] = '-- Upper nav and current tree';
$string['parent'] = '-- The parent page';
$string['initiallyexpanded'] = 'Nodes initially expanded';
$string['startpage'] = 'Start page';
$string['yesonvisited'] = 'Only on visited pages';
$string['hidedisabledlinks'] = 'Hide disabled links';
$string['usemenulabels'] = 'Use menu labels';
$string['hideaccessbullets'] = 'Hide access marks';
$string['configdefaultallowlinks'] = 'Default for link generation';
$string['configdefaultallowlinks_desc'] = 'Default value for link generation that applies to any new instance';
$string['configdefaulthidedisabledlinks'] = 'Default for hiding disabled links';
$string['configdefaulthidedisabledlinks_desc'] = 'Default value for hiding disabled links that applies to any new instance';
$string['configdefaultdepth'] = 'Default for depth';
$string['configdefaultdepth_desc'] = 'Default value for depth that applies to any new instance';
$string['configdefaultstartpage'] = 'Start page default (generic)';
$string['configdefaultstartpage_desc'] = 'Generic location for start page';
$string['configdefaultusemenulabels'] = 'Default for menu labels';
$string['usemenulabels_help'] = 'If disabled, plain page name will be used for display';
$string['configdefaultusemenulabels_desc'] = 'Default value for menu labels that applies to any new instance';
$string['configdefaulthideaccessbullets'] = 'Default for hiding bullets';
$string['configdefaulthideaccessbullets_desc'] = 'Default value for hiding bullets that applies to any new instance';
$string['showanyway'] = 'Show anyway';
$string['allowlinks_help'] = 'Links will never be generated on unvisited pages when the Show anyway option is enabled.';
$string['showanyway_help'] = 'If checked, all the pages will be shown in the block, wether the use can access them or not. For this reason,
the allowlinks options will be forced to the "Only on visited pages" value if it il fully open.';
$string['upgradepagetrackerconfig'] = 'Upgrade page_tracker config data';
