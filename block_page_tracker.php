<?php //$Id: block_page_tracker.php,v 1.5 2012-02-16 19:53:54 vf Exp $

// generates a menu list of child pages ("stations") for a paged format course

require_once($CFG->dirroot.'/course/format/page/lib.php');

class block_page_tracker extends block_list {

    function init() {
        $this->title = get_string('blockname', 'block_page_tracker') ;
    }

    function specialization() {
        if (!empty($this->config) && !empty($this->config->title)) $this->title = format_string($this->config->title) ;
    }
    
    function instance_allow_config(){
    	return true;
    }

    function instance_allow_multiple(){
    	return true;
    }

    function applicable_formats() {
        return array('all' => false, 'course-view-page' => true);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        
        if (!isset($this->config->depth)){
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

		if (!$courseid = $COURSE->id){
	        $courseid = $this->instance->pageid;
	    }
	    
	    if (!isset($this->config->startpage)) @$this->config->startpage = 0;
	    
        if ($this->config->startpage){
        	$startpage = course_page::get($this->config->startpage, $COURSE->id);
	        $pages = $startpage->get_children();
	    } else {
	    	$pages = course_page::get_all_pages($courseid, 'nested');
	    }

        $current = course_page::get_current_page($courseid);
        
        if (empty($pages)){
        	return '';
        }

		// Resolve tickimage locations
		$ticks = new StdClass;
		$ticks->image = $OUTPUT->pix_url('tick_green_big', 'block_page_tracker');
		$ticks->imagepartial = $OUTPUT->pix_url('tick_green_big_partial', 'block_page_tracker');
		$ticks->imageempty = $OUTPUT->pix_url('tick_green_big_empty', 'block_page_tracker');
        
        $this->content->items = array();
        $this->content->icons = array();

        // todo: if in my learning paths check completion for tick display 

		// pre scans page for completion compilation
        foreach ($pages as $page) {
        	$page->accessed = $DB->record_exists_select('log', " userid = ? AND course = ? AND action = 'viewpage' AND info = ? ", array($USER->id, $courseid, "{$courseid}:{$page->id}"));
        	if ($page->has_children()){
	        	$page->complete = $page->accessed && $this->check_childs_access($page);
	        } else {
	        	$page->complete = $page->accessed;
	        }
        }

        foreach ($pages as $page) {
        	$class = ($current->id == $page->id) ? 'current' : '' ;
            $isenabled = $page->check_activity_lock();
            if ($page->accessed){
            	if ($page->complete){
            		$image = $ticks->image; 
            	} else {
            		$image = $ticks->imagepartial;
            	}
            } else {
            	$image = $ticks->imageempty;
            }
            
            if (!empty($this->config->usemenulabels)){
            	$pagename = $page->nametwo;
            	if (empty($pagename)){
            		$pagename = $page->nameone;
            	}
            } else {
            	$pagename = $page->nameone;
            }

            if ((@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $page->accessed)) && $isenabled){
	            $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$page->get_page_depth().'"><a href="/course/view.php?id='.$courseid.'&amp;page='.$page->id.'" class="block-pagetracker '.$class.'">'.$pagename.'</a></div>';
	            if (empty($this->config->hideaccessbullets)){
		            $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
		        }
	        } else {
	        	if (empty($this->config->hidedisabledlinks)){
		            $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$page->get_page_depth().'">'.$pagename.'</div>';
		            if (empty($this->config->hideaccessbullets)){
				    	$this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
					}
		        }
	        }
	        
	        if ($page->has_children() && ($this->config->depth - 1 > 0)){
		        $this->print_sub_stations($page, $ticks, $current, $this->config->depth - 2);
		    }
        }

        return $this->content;
	}

   /**
    * Recursive down scann
    */
    function check_childs_access(&$page){
		global $USER, $COURSE, $DB;
		
		$complete = true;
		$children = $page->get_children();
    	foreach($children as &$child){
        	$child->accessed = $DB->record_exists_select('log', " userid = ? AND course = ? AND action = 'viewpage' AND info = ? ", array($USER->id, $COURSE->id, "{$COURSE->id}:{$child->id}"));
        	if ($child->has_children()){
	        	$child->complete = $child->accessed && $this->check_childs_access($child);
	        } else {
	        	$child->complete = $child->accessed;
	        }
	        $complete = $complete && $child->accessed;
    	}
    	
    	return $complete;
    }
    
    function print_sub_stations(&$page, &$ticks, $current, $currentdepth){
    	global $CFG, $COURSE, $OUTPUT;
    	
    	$children = $page->get_children();
    	foreach($children as &$child){
        	$class = ($current->id == $child->id) ? 'current' : '' ;
            $isenabled = $child->check_activity_lock();
            if (@$child->accessed){
            	if ($child->complete){
            		$image = $ticks->image;
            	} else {
            		$image = $ticks->imagepartial;
            	}
            } else {
            	$image = $ticks->imageempty;
            }

            if (!empty($this->config->usemenulabels)){
            	$childname = $child->nametwo;
            	if (empty($childname)){
            		$childname = $child->nameone;
            	}
            } else {
            	$childname = $child->nameone;
            }

            if ((@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $child->accessed)) && $isenabled){
	            $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$child->get_page_depth().'"><a href="/course/view.php?id='.$COURSE->id.'&amp;page='.$child->id.'" class="block-pagetracker '.$class.'">'.$childname.'</a></div>';
	            if (empty($this->config->hideaccessbullets)){
		            $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
		        }
	        } else {
	        	if (empty($this->config->hidedisabledlinks)){
		            $this->content->items[] = '<div class="block-pagetracker '.$class.' pagedepth'.@$child->get_page_depth().'">'.$childname.'</div>';
	            	if (empty($this->config->hideaccessbullets)){
		            	$this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
		            }
		        }
	        }
	        
	        if ($child->has_children() && ($currentdepth > 0)){
		        $this->print_sub_stations($child, $ticks, $current, $currentdepth - 1);
		    }
    	}
    }
}

