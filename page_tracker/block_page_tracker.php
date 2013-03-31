<?php //$Id: block_page_tracker.php,v 1.5 2012-02-16 19:53:54 vf Exp $

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
	    
        $toppage = page_get_default_page($courseid);
        // $pages = page_filter_child_pages($toppage->id, page_get_all_pages($courseid, 'flat'));
        if ($this->config->startpage){
	        $pages = page_get_children($this->config->startpage, 'flat', $courseid);
	    } else {
	    	$pages = page_get_master_pages($courseid, 0, DISP_PUBLISH);
	    }
        $stations = '<table class="pagetracker" width="100%">';
        
        $current = page_get_current_page($courseid);
        
        if (empty($pages)){
        	return '';
        }

        // todo: if in my learning paths check completion for tick display 

        foreach ($pages as $page) {
        	$class = ($current->id == $page->id) ? 'current' : '' ;
            $hasbeenaccessed = count_records_select('log', "userid = {$USER->id} AND course = $courseid AND action = 'viewpage' AND info = '{$courseid}:{$page->id}'");
            $tickimage = ($hasbeenaccessed) ? $CFG->pixpath.'/blocks/page_tracker/tick_green_big.gif' : $CFG->pixpath.'/spacer.gif' ;
            if (@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $hasbeenaccessed)){
	            $stations .= '<tr valign="middle"><td align="left"><a href="/course/view.php?id='.$courseid.'&amp;page='.$page->id.'" class="block-pagetracker '.$class.'">'.format_string($page->nametwo).'</a></td><td align="right"><img border="0" align="left" src="'.$tickimage.'" width="15" /></td></tr>';
	        } else {
	            $stations .= '<tr valign="middle"><td align="left"><span class="block-pagetracker '.$class.'">'.format_string($page->nametwo).'</span></td><td align="right"><img border="0" align="left" src="'.$tickimage.'" width="15" /></td></tr>';
	        }
        }

		$stations .= '</table>';

        $html = $stations;

        return $html;
    }
}
?>
