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
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * generates a menu list of child pages ("stations") for a paged format course
 */

require_once($CFG->dirroot.'/course/format/page/lib.php');

class block_page_tracker extends block_list {

    public function init() {
        $this->title = get_string('blockname', 'block_page_tracker');
    }

    public function specialization() {
        if (!empty($this->config) && !empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function instance_allow_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return true;
    }

    function applicable_formats() {
        return array('all' => false, 'course' => true, 'mod-*' => true);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!isset($this->config->depth)) {
            @$this->config->depth = 100;
        }

        $filteropt = new stdClass;
        $filteropt->noclean = true;

        $this->content = new stdClass;
        $this->content->text = $this->generate_summary();
        $this->content->footer = '';

        return $this->content;
    }

    function generate_summary() {
        global $CFG, $USER, $COURSE, $DB, $OUTPUT;

        $context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        if (!$courseid = $COURSE->id) {
            $courseid = $this->instance->pageid;
        }

        if (!isset($this->config->startpage)) {
            @$this->config->startpage = 0;
        }

        if ($this->config->startpage) {
            $startpage = course_page::get($this->config->startpage, $COURSE->id);
            $pages = $startpage->get_children();
        } else {
            $pages = course_page::get_all_pages($courseid, 'nested');
        }

        $current = course_page::get_current_page($courseid);

        if (empty($pages)) {
            return '';
        }

        // Resolve tickimage locations.
        $ticks = new StdClass();
        $ticks->image = $OUTPUT->pix_url('tick_green_big', 'block_page_tracker');
        $ticks->imagepartial = $OUTPUT->pix_url('tick_green_big_partial', 'block_page_tracker');
        $ticks->imageempty = $OUTPUT->pix_url('tick_green_big_empty', 'block_page_tracker');

        $this->content->items = array();
        $this->content->icons = array();

        // TODO : if in my learning paths check completion for tick display.

        $logmanger = get_log_manager();
        $readers = $logmanger->get_readers('\core\log\sql_select_reader');
        $reader = reset($readers);

        // Pre scans page for completion compilation.
        foreach (array_keys($pages) as $pid) {
            $page = $pages[$pid];
            if ($reader instanceof \logstore_standard\log\store) {
                $courseparm = 'courseid';
                if ($DB->record_exists_select('logstore_standard_log', " userid = ? AND $courseparm = ? AND component = 'format_page' AND action = 'viewed' AND objectid = ? ", array($USER->id, $courseid, $pid))) {
                    $pages[$pid]->accessed = 1;
                }
            } elseif ($reader instanceof \logstore_legacy\log\store) {
                $courseparm = 'course';
                if ($DB->record_exists_select('log', " userid = ? AND $courseparm = ? AND action = 'viewpage' AND info = ? ", array($USER->id, $courseid, "{$courseid}:{$pid}"))) {
                    $pages[$pid]->accessed = 1;
                }
            } else {
                $pages[$pid]->accessed = 0;
            }

            if ($page->has_children()) {
                $pages[$pid]->complete = ($pages[$pid]->accessed && $this->check_childs_access($pages[$pid]));
            } else {
                $pages[$pid]->complete = $page->accessed;
            }
        }

        foreach ($pages as $page) {
            if (!$page->is_visible(false)) {
                if (!has_capability('format/page:editpages', $coursecontext)) {
                    continue;
                }
            }

            $realvisible = $page->is_visible(true);
            $class = ($realvisible) ? '' : 'shadow';
            $class .= ($current->id == $page->id) ? 'current' : '';
            $isenabled = $page->check_activity_lock();
            if ($page->accessed) {
                if ($page->complete) {
                    $image = $ticks->image;
                } else {
                    $image = $ticks->imagepartial;
                }
            } else {
                $image = $ticks->imageempty;
            }

            if (!empty($this->config->usemenulabels)) {
                $pagename = format_string($page->nametwo);
                if (empty($pagename)) {
                    $pagename = format_string($page->nameone);
                }
            } else {
                $pagename = format_string($page->nameone);
            }

            if (((@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $page->accessed)) && $isenabled) || has_capability('block/page_tracker:accessallpages', $context)) {
                $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$page->get_page_depth().'"><a href="/course/view.php?id='.$courseid.'&amp;page='.$page->id.'" class="block-pagetracker '.$class.'">'.$pagename.'</a></div>';
                if (empty($this->config->hideaccessbullets)) {
                    $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                }
            } else {
                if (empty($this->config->hidedisabledlinks)) {
                    $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$page->get_page_depth().'">'.$pagename.'</div>';
                    if (empty($this->config->hideaccessbullets)) {
                        $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                    }
                }
            }

            if ($page->has_children() && ($this->config->depth - 1 > 0)) {
                $this->print_sub_stations($page, $ticks, $current, $this->config->depth - 2);
            }
        }

        return $this->content;
    }

   /**
    * Recursive down scann
    */
    function check_childs_access(&$page) {
        global $USER, $COURSE, $DB;

        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_select_reader');
        $reader = reset($readers);

        $complete = true;
        $children = $page->get_children();
        foreach ($children as &$child) {

            if ($reader instanceof \logstore_standard\log\store) {
                $courseparm = 'courseid';
                $child->accessed = $DB->record_exists_select('logstore_standard_log', " userid = ? AND $courseparm = ? AND component = 'format_page' AND action = 'viewed' AND objectid = ? ", array($USER->id, $COURSE->id, $child->id));
            } elseif ($reader instanceof \logstore_legacy\log\store) {
                $courseparm = 'course';
                $child->accessed = $DB->record_exists_select('log', " userid = ? AND $courseparm = ? AND action = 'viewpage' AND info = ? ", array($USER->id, $COURSE->id, "{$COURSE->id}:{$child->id}"));
            } else {
                $child->accessed = false;
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

    function print_sub_stations(&$page, &$ticks, $current, $currentdepth) {
        global $CFG, $COURSE, $OUTPUT;

        $context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        $children = $page->get_children();
        foreach ($children as &$child) {
            if (!$child->is_visible(false)) {
                if (!has_capability('format/page:editpages', $coursecontext)) {
                    continue;
                }
            }
            $realvisible = $child->is_visible(false);
            $class = ($realvisible) ? '' : 'shadow ';
            $class .= ($current->id == $child->id) ? 'current' : '';
            $isenabled = $child->check_activity_lock();
            if (@$child->accessed) {
                if ($child->complete) {
                    $image = $ticks->image;
                } else {
                    $image = $ticks->imagepartial;
                }
            } else {
                $image = $ticks->imageempty;
            }

            if (!empty($this->config->usemenulabels)) {
                $childname = format_string($child->nametwo);
                if (empty($childname)) {
                    $childname = format_string($child->nameone);
                }
            } else {
                $childname = format_string($child->nameone);
            }

            if (((@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $child->accessed)) && $isenabled) || has_capability('block/page_tracker:accessallpages', $context)) {
                $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$child->get_page_depth().'"><a href="/course/view.php?id='.$COURSE->id.'&amp;page='.$child->id.'" class="block-pagetracker '.$class.'">'.$childname.'</a></div>';
                if (empty($this->config->hideaccessbullets)) {
                    $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                }
            } else {
                if (empty($this->config->hidedisabledlinks)) {
                    $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$child->get_page_depth().'">'.$childname.'</div>';
                    if (empty($this->config->hideaccessbullets)) {
                        $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                    }
                }
            }

            if ($child->has_children() && ($currentdepth > 0)) {
                $this->print_sub_stations($child, $ticks, $current, $currentdepth - 1);
            }
        }
    }
}
