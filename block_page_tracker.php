<?php //$Id: block_page_tracker.php,v 1.7 2013-04-29 16:03:43 vf Exp $

// generates a menu list of child pages ("stations") for a paged format course

require_once($CFG->dirroot.'/course/format/page/lib.php');

class block_page_tracker extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_page_tracker') ;
        $this->version = 2008090909;
    }
    
    function instance_allow_config(){
    	return true;
    }

    function instance_allow_multiple(){
    	return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $filteropt = new stdClass;
        $filteropt->noclean = true;

        $this->content = new stdClass;
        $this->content->text = $this->generate_summary();
        $this->content->footer = '';

        return $this->content;
    }

    function generate_summary() {
        global $CFG, $USER, $COURSE;

		if (!$courseid = $COURSE->id){
	        $courseid = $this->instance->pageid;
	    }
	    
	    if (!isset($this->config->startpage)) $this->config->startpage = 0;
	    
        if ($this->config->startpage){
	        $pages = page_get_children($this->config->startpage, 'nested', $courseid, 0);
	    } else {	    	
	    	$pages = page_get_all_pages($courseid, 'nested');
	    	$startdepth = 0;
	    }
	    
        $stations = '<table class="pagetracker" width="100%">';
        
        $current = page_get_current_page($courseid);
        
        if (empty($pages)){
        	return '';
        }

		// Resolve tickimage locations
		if (file_exists($CFG->dirroot.'/theme/'.current_theme().'/pix/blocks/page_tracker/tick_green_big.gif')){
			$ticks->image = $CFG->wwwroot.'/theme/'.current_theme().'/pix/blocks/page_tracker/tick_green_big.gif';
		} else {
			$ticks->image = $CFG->wwwroot.'/blocks/page_tracker/pix/tick_green_big.gif';
		}

		if (file_exists($CFG->dirroot.'/theme/'.current_theme().'/pix/blocks/page_tracker/tick_green_big_partial.gif')){
			$ticks->imagepartial = $CFG->wwwroot.'/theme/'.current_theme().'/pix/blocks/page_tracker/tick_green_big_partial.gif';
		} else {
			$ticks->imagepartial = $CFG->wwwroot.'/blocks/page_tracker/pix/tick_green_big_partial.gif';
		}

        // todo: if in my learning paths check completion for tick display 

		// pre scans page for completion compilation
        foreach ($pages as $page) {
        	$page->accessed = record_exists_select('log', " userid = {$USER->id} AND course = $courseid AND action = 'viewpage' AND info = '{$courseid}:{$page->id}'");
        	if (!empty($page->children)){
	        	$page->complete = $page->accessed && $this->check_childs_access($page);
	        } else {
	        	$page->complete = $page->accessed;
	        }
        }

        foreach ($pages as $page) {
        	$class = ($current->id == $page->id) ? 'current' : '' ;
            $isenabled = page_check_enabled($page);
            if ($page->accessed){
            	if ($page->complete){
            		$image = $ticks->image; 
            	} else {
            		$image = $ticks->imagepartial;
            	}
            } else {
            	$image = $CFG->pixpath.'/spacer.gif' ;
            }

            if ((@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $page->accessed)) && $isenabled){
	            $stations .= '<tr valign="middle"><td align="left" class="pagedepth'.@$page->depth.'"><a href="/course/view.php?id='.$courseid.'&amp;page='.$page->id.'" class="block-pagetracker '.$class.'">'.format_string($page->nametwo).'</a></td><td align="right"><img border="0" align="left" src="'.$image.'" width="15" /></td></tr>';
	        } else {
	            $stations .= '<tr valign="middle"><td align="left" class="pagedepth'.@$page->depth.'"><span class="block-pagetracker '.$class.'">'.format_string($page->nametwo).'</span></td><td align="right"><img border="0" align="left" src="'.$image.'" width="15" /></td></tr>';
	        }
	        
	        if ($page->children && ($this->config->depth - 1 > 0)){
		        $this->print_sub_stations($page, $ticks, $current, $stations, $this->config->depth - 2);
		    }
        }

		$stations .= '</table>';

        $html = $stations;

        return $html;
    }
    
    /**
    * Recursive down scann
    */
    function check_childs_access(&$page){
		global $USER, $COURSE;
		
		$complete = true;
    	foreach($page->children as &$child){
        	$child->accessed = record_exists_select('log', " userid = {$USER->id} AND course = $COURSE->id AND action = 'viewpage' AND info = '{$COURSE->id}:{$child->id}'");
        	if (!empty($child->children)){
	        	$child->complete = $child->accessed && $this->check_childs_access($child);
	        } else {
	        	$child->complete = $child->accessed;
	        }
	        $complete = $complete && $child->accessed;
    	}
    	
    	return $complete;
    }
    
    function print_sub_stations(&$page, &$ticks, $current, &$stations, $currentdepth){
    	global $CFG, $COURSE;
    	
    	foreach($page->children as &$child){
        	$class = ($current->id == $child->id) ? 'current' : '' ;
            $isenabled = page_check_enabled($child);
            if (@$child->accessed){
            	if ($child->complete){
            		$image = $ticks->image;
            	} else {
            		$image = $ticks->imagepartial;
            	}
            } else {
            	$image = $CFG->pixpath.'/spacer.gif' ;
            }

            if ((@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $child->accessed)) && $isenabled){
	            $stations .= '<tr valign="middle"><td align="left" class="pagedepth'.@$child->depth.'"><a href="/course/view.php?id='.$COURSE->id.'&amp;page='.$child->id.'" class="block-pagetracker '.$class.'">'.format_string($child->nametwo).'</a></td><td align="right"><img border="0" align="left" src="'.$image.'" width="15" /></td></tr>';
	        } else {
	            $stations .= '<tr valign="middle"><td align="left" class="pagedepth'.@$child->depth.'"><span class="block-pagetracker '.$class.'">'.format_string($child->nametwo).'</span></td><td align="right"><img border="0" align="left" src="'.$image.'" width="15" /></td></tr>';
	        }
	        
	        if ($child->children && ($currentdepth > 0)){
		        $this->print_sub_stations($child, $ticks, $current, $stations, $currentdepth - 1);
		    }
    	}
    }
}
?>
