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
 * Form for editing page_tracker block instances.
 *
 * @package   block_page_tracker
 * @category  blocks
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/page_tracker/locallib.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');

use format_page\course_page;

/**
 * Main block class.
 * Generates a menu list of child pages ("stations") for a paged format course
 */
class block_page_tracker extends block_base {

    /** @var Errors should be always traced when trace is on. */
    const TRACE_ERRORS = 1;

    /** @var Notices are important notices in normal execution. */
    const TRACE_NOTICE = 3;

    /** @var Debug are debug time notices that should be burried in debug_fine level when debug is ok. */
    const TRACE_DEBUG = 5;

    /** @var Data level is when requiring to see data structures content. */
    const TRACE_DATE = 8;

    /** @var Debug fine are control points we want to keep when code is refactored and debug needs to be reactivated. */
    const TRACE_DEBUG_FINE = 8;

    /** @var array trace of visited pages. */
    protected $tracks;

    /** @var array tick images. */
    public static $ticks;

    /**
     * @var int The relative curent tree depth. Loaded with the block instance initial config value,
     * will be decremented by one for each child level until reaches 0.
     */
    protected $reldepth;

    /**
     * @var the current loaded page in screen.
     */
    protected $current;

    /**
     * Block standard init.
     */
    public function init() {
        global $OUTPUT;

        $this->title = '';

        if (is_null(self::$ticks)) {
            $ticks = new StdClass();
            if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
                // image_url needs some theme initialisation.
                $ticks->image = $OUTPUT->image_url('bullet_visited', 'block_page_tracker');
                $ticks->imagepartial = $OUTPUT->image_url('bullet_half-visited', 'block_page_tracker');
                $ticks->imageempty = $OUTPUT->image_url('bullet', 'block_page_tracker');
            }
            self::$ticks = $ticks;
        }

    }

    /**
     * Standard specialization.
     */
    public function specialization() {
        if (!empty($this->config)) {
            if (!empty($this->config->title)) {
                $this->title = format_string($this->config->title);
            }

            if (!isset($this->config->allowlinks)) {
                $this->config->allowlinks = PAGE_TRACKER_LINKS;
            }

            if (!isset($this->config->depth)) {
                $this->config->depth = 100;
            }

            $this->reldepth = $this->config->depth;
        }
    }

    /**
     * Does the block have global config ?
     */
    public function has_config() {
        return true;
    }

    /**
     * Does the block have instance config ?
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Does the block allow multiples instances in course ?
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Wich format and page layouts allowed ?
     */
    public function applicable_formats() {
        return ['all' => false, 'course' => true, 'mod-*' => true];
    }

    /**
     * Main block content
     */
    public function get_content() {
        global $COURSE, $OUTPUT;

        if (empty($this->config)) {
            $this->initialize_config();
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if ($COURSE->format != 'page') {
            $this->content = new stdClass;
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        $filteropt = new stdClass();
        $filteropt->noclean = true;

        $this->content = new stdClass();
        $template = $this->get_summary();
        $template->level = 0;
        $template->blockid = $this->instance->id;
        $template->courseid = $COURSE->id;
        $template->showmarks = empty($this->config->hideaccessbullets);

        $this->content->text = $OUTPUT->render_from_template('block_page_tracker/pagelist', $template);
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Makes a default config
     */
    protected function initialize_config() {
        $config = get_config('block_page_tracker');
        $this->config = new StdClass();
        $this->config->initialexpanded = true;
        $this->config->allowlinks = $config->defaultallowlinks;
        $this->config->hidedisabledlinks = $config->defaulthidedisabledlinks;
        $this->config->depth = 100;
        $this->config->usemenulabels = $config->defaultusemenulabels;
        $this->config->hideaccessbullets = $config->defaulthideaccessbullets;
        $this->config->showanyway = true;
        $this->config->startpage = 0;

        $this->instance_config_save($this->config);
    }

    /**
     * Generates the bloc's full page summary as a template. Initiates first hierarchy level.
     */
    public function get_summary() {
        global $CFG, $USER, $COURSE, $DB, $OUTPUT;

        $template = new StdClass();

        $this->context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        if (!$courseid = $COURSE->id) {
            $courseid = $this->instance->pageid;
        }

        if (!isset($this->config)) {
            $this->initialize_config();
        }

        if (!isset($this->config->startpage)) {
            $this->config->startpage = 0;
        }

        $reldepth = 0;
        if ($this->config->startpage > 0) {
            // An explicit page has been designated as tree root.
            if ($startpage = course_page::get($this->config->startpage, $COURSE->id)) {
                $pages = $startpage->get_children();
            } else {
                $this->content->footer = get_string('errormissingpage', 'block_page_tracker');
                return $template;
            }
        } else if ($this->config->startpage == -1) {
            // Current page has been designated as tree root.
            $startpage = course_page::get_current_page($courseid);
            $pages = $startpage->get_children();
        } else if ($this->config->startpage == -2) {
            // Parent page has been designated as tree root.
            $startpage = course_page::get_current_page($courseid);
            $parent = $startpage->get_parent();
            if (!empty($parent)) {
                $pages = $parent->get_children();
                $startpage = $parent;
            } else {
                $pages = course_page::get_all_pages($courseid, 'nested');
            }
        } else if ($this->config->startpage == -3) {
            // Top branch page has been designated as tree root.
            // Get all upper nav.
            $current = course_page::get_current_page($courseid);
            $reldepth = $current->get_page_depth();
            $pages = course_page::get_all_pages($courseid, 'nested', true, 0, $reldepth);
            $flat = course_page::get_all_pages($courseid, 'flat'); // No cost.

            // Find current's parent and plug current into tree.
            if ($current->parent) {
                $flat[$current->parent]->childs = [$current->id => $current];
            }
            $flat[$current->id] = $current;
        } else {
            // Take all pages from absolute root.
            $pages = course_page::get_all_pages($courseid, 'nested', false, 0, $this->config->depth);
        }

        $this->current = course_page::get_current_page($courseid);

        if (!empty($startpage)) {
            $tmp = $startpage;
            // Remove childs to only have this page.
            if (!empty($parent)) {
                $tmp->childs = null;
                array_unshift($pages, $tmp);
            }
            while ($tmp = $tmp->get_parent()) {
                $tmp->childs = null;
                if (!empty($pages)) {
                    array_unshift($pages, $tmp);
                }
            }
        }

        if (empty($pages)) {
            // Return empty template.
            return $template;
        }

        // TODO : if in my learning paths check completion for tick display.

        $this->get_tracks();

        // Pre scans page for completion compilation.
        foreach ($pages as $pid => $page) {
            if (!empty($this->tracks) && in_array($pid, $this->tracks) || ($page->id == $this->current->id)) {
                $pages[$pid]->accessed = 1;
            } else {
                $pages[$pid]->accessed = 0;
            }

            if ($page->has_children()) {
                $pages[$pid]->complete = ($pages[$pid]->accessed && $this->check_childs_access($pages[$pid]));
            } else {
                $pages[$pid]->complete = $page->accessed;
            }
        }

        // make a top fake page to add firstlevel children.
        $toppage = new course_page(null);
        $toppage->childs = $pages;

        $template = $this->get_sub_stations($toppage);
        $template->initialtoggleclass = ''; // Top level must be never collapsed.

        return $template;
    }

    /**
     * Recursive printing of children pages.
     * @param object $page the parent station
     */
    public function get_sub_stations($page) {
        global $CFG, $COURSE, $OUTPUT;

        $debug = optional_param('debug', false, PARAM_BOOL);
        if ($debug) {
            self::debug_trace("Sub stations for page $page->id ", self::TRACE_DEBUG);
        }

        $currentpage = optional_param('page', 0, PARAM_INT);

        $coursecontext = context_course::instance($COURSE->id);

        $template = $this->export_page_template($page);
        $template->hassubs = false;
        $children = $page->get_children();
        if (!empty($children)) {
            foreach ($children as $child) {

                if ($debug) {
                    self::debug_trace(" => Child $child->id ", self::TRACE_DEBUG_FINE);
                }

                $displaymenu = $child->displaymenu;
                if (empty($displaymenu)) {
                    continue;
                }

                if (empty($this->config->showanyway)) {
                    if (!$child->is_visible(false) || !$child->is_available()) {
                        if (!has_capability('format/page:editpages', $coursecontext)) {
                            self::debug_trace("Hide page as not visible and no override editing cap", self::TRACE_DEBUG_FINE);
                            continue;
                        }
                    }
                }

                $template->hassubs = true && ($this->reldepth > 0); // At least first visible child must trigger.
                $childtpl = $this->export_page_template($child);

                if ($child->is_visible() && $child->is_available()) {
                    // $childtpl->subs = null;
                    if ($this->reldepth > 0) {
                        $this->reldepth--;
                        $template->pages[] = $this->get_sub_stations($child);
                        $this->reldepth++;
                    }
                    $template->hassubs = true && ($this->reldepth > 0); // At least first visible child must trigger.
                } else {
                    $childtpl->hassubs = false;
                }
            }
        }
        return $template;
    }

    /**
     * Exports all template data for one page to print in list.
     * @param object $page
     */
    protected function export_page_template($page) {
        global $COURSE, $OUTPUT, $SESSION;

        $pagetpl = new Stdclass;
        $pagetpl->id = $page->id;

        $realvisible = $page->is_visible_page();
        $pagetpl->iscurrentclass = ($realvisible) ? '' : 'is-hidden-page ';
        $pagetpl->iscurrentclass .= ($this->current->id == $page->id) ? 'is-current-page ' : '';
        $isenabled = $page->check_activity_lock();

        $pagetpl->parent = $page->get_parent(true);
        $pagetpl->initialexpanded = ($this->config->initialexpanded ?? 1) ? 'true' : '';
        $pagetpl->initialtoggleclass = ($this->config->initialexpanded ?? 1) ? '' : 'collapsed ';
        $initialicon = ($this->config->initialexpanded ?? 1) ? 'minus' : 'plus';
        $pagetpl->initialicon = $OUTPUT->pix_icon('t/switch_'.$initialicon, '', 'moodle');

        // Override by session if set.
        $trackid = $this->instance->id.'_'.$page->id;
        if (isset($SESSION->pagetracker->$trackid)) {
            if ($SESSION->pagetracker->$trackid) {
                // force expanded.
                $pagetpl->initialexpanded = 'true';
                $pagetpl->initialtoggleclass = '';
                $pagetpl->initialicon = $OUTPUT->pix_icon('t/switch_minus', '', 'moodle');
            } else {
                // force collapsed.
                $pagetpl->initialexpanded = '' ;
                $pagetpl->initialtoggleclass = 'collapsed' ;
                $pagetpl->initialicon = $OUTPUT->pix_icon('t/switch_plus', '', 'moodle');
            }
        }

        if (empty($this->config->hideaccessbullets)) {
            if ($page->accessed) {
                $pagetpl->hasbeenseenclass = 'has-been-seen ';
                if ($page->complete) {
                    $pagetpl->markurl = self::$ticks->image;
                    $pagetpl->hasbeenseenclass = 'has-been-seen full ';
                } else {
                    $pagetpl->markurl = self::$ticks->imagepartial;
                }
            } else {
                $pagetpl->markurl = self::$ticks->imageempty;
            }
        }

        if (!empty($this->config->usemenulabels)) {
            $pagetpl->pagename = format_string($page->nametwo);
            if (empty($pagetpl->pagename)) {
                $pagetpl->pagename = format_string($page->nameone);
            }
        } else {
            $pagetpl->pagename = format_string($page->nameone);
        }

        // page url must use course_page::url_build as it resolves CM override.
        $pagetpl->level = $page->get_page_depth() ?? 0;
        $pagetpl->pageurl = $page->url_build('page', $page->id);
        $pagetpl->islink = $this->is_link($page);

        $parentid = $page->get_parent(true);
        $pagetpl->parent = $parentid;

        return $pagetpl;
    }

    /**
     * Recursive down scann into children to check if some
     * have been accessed already.
     * @param object $page the parent course page
     */
    public function check_childs_access($page) {
        global $USER, $COURSE, $DB;

        $complete = true;
        $children = $page->get_children();
        foreach ($children as &$child) {

            if (!empty($this->tracks) && in_array($child->id, $this->tracks)) {
                $child->accessed = 1;
            } else {
                $child->accessed = 0;
            }

            if ($child->has_children()) {
                $child->complete = $child->accessed && $this->check_childs_access($child);
            } else {
                $child->complete = $child->accessed;
            }
            $complete = $complete && $child->accessed;
        }

        return $complete;
    }

    /**
     * Checks if the page entry needs a link.
     * @param object $page the course_page object
     */
    protected function is_link($page) {
        global $USER, $COURSE;

        $context = context_course::instance($COURSE->id);
        $isenrolled = is_enrolled($context, $USER);

        switch ($this->config->allowlinks) {
            case PAGE_TRACKER_LINKS: {
                return $page->check_activity_lock();
            }

            case PAGE_TRACKER_LINKSVISITED: {
                if ($page->accessed && ($isenrolled || has_capability('moodle/course:viewhiddenactivities', $context))) {
                    return $page->check_activity_lock();
                }
            }

            case PAGE_TRACKER_NOLINKS: {
                return false;
            }
        }

        return false;
    }

    /**
     * Checks if a page node is a visible page
     * @param object $page
     * @return bool
     */
    protected function is_visible($page) {
        return empty($this->config->hidedisabledlinks) ||
                $page->accessed ||
                    ($this->current->id == $page->id) ||
                        $this->config->allowlinks != PAGE_TRACKER_LINKSVISITED ||
                                has_capability('block/page_tracker:accessallpages', $this->context);
    }

    /**
     * Get distinct pages that have been viewed by the current user
     * @return an array of page ids or null if empty.
     */
    protected function get_tracks() {
        global $DB, $COURSE, $USER;

        $params = ['courseid' => $COURSE->id, 'userid' => $USER->id];
        if ($tracks = $DB->get_records('block_page_tracker', $params, 'id', 'DISTINCT pageid,pageid')) {
            $this->tracks = array_keys($tracks);
        }
    }

    /**
     * Get the JS required when this block is in page
     */
    public function get_required_javascript() {
        $this->page->requires->js_call_amd('block_page_tracker/pagetracker', 'init', [[$this->instance->id]]);
    }

    /** 
     * Wrapper to APL general debug tools
     *
     * @param string $msg
     * @param int $level
     * @param string $label
     * @param int $backtracelevel
     */
    public static function debug_trace($msg, $level = self::TRACE_DEBUG, $label = '', $backtracelevel = 1) {
        if (function_exists('debug_trace')) {
            debug_trace($msg, $level, $label, $backtracelevel);
        }
    }
}
