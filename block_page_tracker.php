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

/*
 * generates a menu list of child pages ("stations") for a paged format course
 */

require_once($CFG->dirroot.'/blocks/page_tracker/locallib.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');

use \format\page\course_page;

class block_page_tracker extends block_base {

    protected $tracks;

    public static  $ticks;

    /**
     * The relative curent tree depth. Loaded with the block instance initial config value,
     * will be decremented by one for each child level until reaches 0.
     */
    protected $reldepth;

    /**
     * the current loaded page in screen.
     */
    protected $current;

    public function init() {
        global $OUTPUT;

        $this->title = get_string('blockname', 'block_page_tracker');

        if (is_null(self::$ticks)) {
            $ticks = new StdClass();
            $ticks->image = $OUTPUT->image_url('tick_green_big', 'block_page_tracker');
            $ticks->imagepartial = $OUTPUT->image_url('tick_green_big_partial', 'block_page_tracker');
            $ticks->imageempty = $OUTPUT->image_url('tick_green_big_empty', 'block_page_tracker');
            self::$ticks = $ticks;
        }
    }

    public function specialization() {
        if (!empty($this->config)) {
            if (!empty($this->config->title)) {
                $this->title = format_string($this->config->title);
            }

            if (!isset($this->config->allowlinks)) {
                $this->config->allowlinks = PAGE_TRACKER_LINKS;
            }

            if (!isset($this->config->depth)) {
                @$this->config->depth = 100;
            }

            $this->reldepth = $this->config->depth;
        }
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => false, 'course' => true, 'mod-*' => true);
    }

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

        $filteropt = new stdClass;
        $filteropt->noclean = true;

        $this->content = new stdClass;
        $template = $this->get_summary();
        $template->level = 0;
        $template->blockid = $this->instance->id;
        $template->courseid = $COURSE->id;
        $template->showmarks = !$this->config->hideaccessbullets;

        $this->content->text = $OUTPUT->render_from_template('block_page_tracker/pagelist', $template);
        $this->content->footer = '';

        return $this->content;
    }

    protected function initialize_config() {
        $config = get_config('block_page_tracker');
        $this->config = new StdClass;
        $this->config->initialexpanded = true;
        $this->config->allowlinks = $config->defaultallowlinks;
        $this->config->hidedisabledlinks = $config->defaulthidedisabledlinks;
        $this->config->depth = 100;
        $this->config->usemenulabels = $config->defaultusemenulabels;
        $this->config->hideaccessbullets = $config->defaulthideaccessbullets;
        $this->config->showanyway = true;

        $this->instance_config_save($this->config);
    }

    /**
     * Generates the bloc's full page summary as a template. Initiates first hierarchy level.
     */
    public function get_summary() {
        global $CFG, $USER, $COURSE, $DB, $OUTPUT;

        $template = new StdClass;

        $this->context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        if (!$courseid = $COURSE->id) {
            $courseid = $this->instance->pageid;
        }

        if (!isset($this->config->startpage)) {
            @$this->config->startpage = 0;
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
                $flat[$current->parent]->childs = array($current->id => $current);
            }
            $flat[$current->id] = $current;

            // block_page_tracker_debug_print_tree($pages);
        } else {
            // Take all pages from absolute root.
            $pages = course_page::get_all_pages($courseid, 'nested');
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
            if (!empty($this->tracks) && in_array($pid, $this->tracks)) {
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

        // debug_trace(block_page_tracker_debug_print_tree($template));
        return $template;
    }

    /**
     * Recursive printing of children pages.
     * @param objectref &$page the parent station
     * @param int $currentdepth the depth in hierarchy of the current page.
     */
    public function get_sub_stations(&$page) {
        global $CFG, $COURSE, $OUTPUT;

        $debug = optional_param('debug', false, PARAM_BOOL);
        if ($debug) {
            debug_trace("Sub stations for page $page->id ", TRACE_DEBUG);
        }

        $currentpage = optional_param('page', 0, PARAM_INT);

        $coursecontext = context_course::instance($COURSE->id);

        $template = $this->export_page_template($page);
        $template->hassubs = false;
        $children = $page->get_children();
        if (!empty($children)) {
            foreach ($children as $child) {

                debug_trace(" => Child $child->id ", TRACE_DEBUG_FINE);

                $displaymenu = $child->displaymenu;
                if (empty($displaymenu)) {
                    debug_trace(" => Child {$child->id} not displayed by page setting ", TRACE_DEBUG_FINE);
                    continue;
                }

                if (empty($this->config->showanyway)) {
                    if (!$child->is_visible(false)) {
                        if (!has_capability('format/page:editpages', $coursecontext)) {
                            debug_trace(" => Child {$child->id} not visible ", TRACE_DEBUG_FINE);
                            continue;
                        }
                    }
                }

                $template->hassubs = true; // At least first visible child must trigger.
                $childtpl = $this->export_page_template($child);

                if ($this->is_visible($child)) {
                    $this->reldepth--;
                    // $childtpl->subs = null;
                    if ($this->reldepth > 0) {
                        $template->pages[] = $this->get_sub_stations($child);
                    }
                    $this->reldepth++;
                } else {
                    $childtpl->hassubs = false;
                    // $childtpl->subs = null;
                    debug_trace(" => Child link not visible page tracker config  ", TRACE_DEBUG_FINE);
                }
            }
        }
        return $template;
    }

    /**
     * Exports all template data for one page to print in list.
     */
    protected function export_page_template($page) {
        global $COURSE, $OUTPUT, $SESSION;

        $pagetpl = new Stdclass;
        $pagetpl->id = $page->id;

        $realvisible = $page->is_visible(true);
        $pagetpl->class = ($realvisible) ? '' : 'shadow';
        $pagetpl->class .= ($this->current->id == $page->id) ? 'is-current-page' : '';
        $isenabled = $page->check_activity_lock();

        $pagetpl->parent = $page->get_parent(true);
        $pagetpl->initialexpanded = (@$this->config->initialexpanded) ? 'true' : '';
        $pagetpl->initialtoggleclass = (@$this->config->initialexpanded) ? '' : 'collapsed';
        $initialicon = (@$this->config->initialexpanded) ? 'minus' : 'plus';
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
                if ($page->complete) {
                    $pagetpl->markurl = self::$ticks->image;
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

        $pagetpl->level = 0 + @$page->get_page_depth();
        $pagetpl->pageurl = new moodle_url('/course/view.php', array('id' => $COURSE->id, 'page' => $page->id));
        $pagetpl->islink = $this->is_link($page);

        $parentid = $page->get_parent(true);
        $pagetpl->parent = $parentid;

        return $pagetpl;
    }

    /**
     * Recursive down scann into children to check if some
     * have been accessed already.
     * @param objectref &$page the parent course page
     */
    public function check_childs_access(&$page) {
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

    protected function is_link($page) {

        switch ($this->config->allowlinks) {
            case PAGE_TRACKER_LINKS: {
                return true;
            }

            case PAGE_TRACKER_LINKSVISITED: {
                if ($page->accessed) {
                    return true;
                }
            }

            case PAGE_TRACKER_NOLINKS: {
                return false;
            }
        }
    }

    protected function is_visible($child) {
        return empty($this->config->hidedisabledlinks) ||
                $child->accessed ||
                        $this->config->allowlinks != PAGE_TRACKER_LINKSVISITED ||
                                has_capability('block/page_tracker:accessallpages', $this->context);
    }

    /**
     * Get distinct pages that have been viewed by the current user
     * @return an array of page ids or null if empty.
     */
    protected function get_tracks() {
        global $DB, $COURSE, $USER;

        $params = array('courseid' => $COURSE->id, 'userid' => $USER->id);
        if ($tracks = $DB->get_records('block_page_tracker', $params, 'id', 'DISTINCT pageid,pageid')) {
            $this->tracks = array_keys($tracks);
        }
    }

    public function get_required_javascript() {
        global $PAGE;

        $PAGE->requires->js_call_amd('block_page_tracker/pagetracker', 'init');
    }
}
