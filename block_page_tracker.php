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
        global $CFG, $USER, $COURSE, $DB, $OUTPUT;

		if (!$courseid = $COURSE->id){
	        $courseid = $this->instance->pageid;
	    }
	    
	    if (!isset($this->config->startpage)) @$this->config->startpage = 0;
	    
        $toppage = course_page::get_default_page($courseid);
        // $pages = page_filter_child_pages($toppage->id, course_page::get_all_pages($courseid, 'flat'));
        if ($this->config->startpage){
        	$startpage = course_page::get($this->config->startpage, $COURSE->id);
	        $pages = $startpage->get_children();
	    } else {
	    	$pages = course_page::get_master_pages($courseid);
	    }

        $current = course_page::get_current_page($courseid);
        
        if (empty($pages)){
        	return '';
        }
        
        $this->content->items = array();
        $this->content->icons = array();

        // todo: if in my learning paths check completion for tick display 

        foreach ($pages as $page) {
        	$class = ($current->id == $page->id) ? 'current' : '' ;
            $hasbeenaccessed = $DB->count_records_select('log', "userid = ? AND course = ? AND action = 'viewpage' AND info = ? ", array($USER->id, $courseid, "{$courseid}:{$page->id}"));
            $tickimage = ($hasbeenaccessed) ? $OUTPUT->pix_url('tick_green_big', 'block_page_tracker') : $OUTPUT->pix_url('spacer') ;
            if (@$this->config->allowlinks == 2 || (@$this->config->allowlinks == 1 && $hasbeenaccessed)){
	            $this->content->items[] = '<a href="/course/view.php?id='.$courseid.'&amp;page='.$page->id.'" class="block-pagetracker '.$class.'">'.format_string($page->get_name()).'</a>';
	            $this->content->icons[] = '<img border="0" align="left" src="'.$tickimage.'" width="15" />';
	        } else {
	            $this->content->items[] = '<span class="block-pagetracker '.$class.'">'.format_string($page->get_name()).'</span>';
	            $this->content->icons[] = '<img border="0" align="left" src="'.$tickimage.'" width="15" />';
	        }
        }

        return $this->content;
    }
}
?>
