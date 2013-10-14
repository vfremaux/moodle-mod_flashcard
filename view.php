<?php

    /** 
    * This page prints a particular instance of a flashcard
    * 
    * @package mod-flashcard
    * @category mod
    * @author Gustav Delius
    * @author Valery Fremaux
    * @author Tomasz Muras
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    * @version Moodle 2.0
    */
    
    /* @var $OUTPUT core_renderer */

    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/flashcard/lib.php');
    require_once($CFG->dirroot.'/mod/flashcard/locallib.php');
    require_once($CFG->dirroot.'/mod/flashcard/renderers.php');

    $PAGE->requires->js('/mod/flashcard/js/ufo.js', true);
    $PAGE->requires->js('/mod/flashcard/js/module.js', false);

    $id = optional_param('id', '', PARAM_INT);    // Course Module ID, or
    $f = optional_param('f', '', PARAM_INT);     // flashcard ID
    $view = optional_param('view', 'checkdecks', PARAM_ACTION);     // view
    $page = optional_param('page', '', PARAM_ACTION);     // page
    $action = optional_param('what', '', PARAM_ACTION);     // command
    
    $thisurl = $CFG->wwwroot.'/mod/flashcard/view.php';    
    $url = new moodle_url('/mod/flashcard/view.php', array('id' => $id));

    $PAGE->set_url($url);
    if ($id) {
        if (! $cm = $DB->get_record('course_modules', array('id' => $id))) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('coursemisconf');
        }
        if (! $flashcard = $DB->get_record('flashcard', array('id' => $cm->instance))) {
            print_error('errorinvalidflashcardid', 'flashcard');
        }
    } else {
        if (! $flashcard = $DB->get_record('flashcard', array('id' => $f))) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record('course', array('id' => $flashcard->course))) {
            print_error('coursemisconf');
        }
        if (! $cm = get_coursemodule_from_instance('flashcard', $flashcard->id, $course->id)) {
            print_error('errorinvalidflashcardid', 'flashcard');
        }
    }

    require_course_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

/// Print the page header

    $strflashcards = get_string('modulenameplural', 'flashcard');
    $strflashcard  = get_string('modulename', 'flashcard');
    $PAGE->set_title("$course->shortname: $flashcard->name");
    $PAGE->set_heading("$course->fullname");
    /* SCANMSG: may be additional work required for $navigation variable */
    $PAGE->navbar->add($strflashcards, $CFG->wwwroot."/mod/flashcard/index.php?id=".$course->id);
    $PAGE->navbar->add($flashcard->name);
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'flashcard'));
    $PAGE->set_headingmenu(navmenu($course, $cm));

    $out = $OUTPUT->header();

/// non visible trap for timerange (security)
    if (!has_capability('moodle/course:viewhiddenactivities', $context) && !$cm->visible){
    	echo $out;
        echo $OUTPUT->notification(get_string('activityiscurrentlyhidden'));
        echo $OUTPUT->footer();
        die;
    }

/// non manager trap for timerange

    if (!has_capability('mod/flashcard:manage', $context)){
        $now = time();
        if (($flashcard->starttime != 0 && $now < $flashcard->starttime) || ($flashcard->endtime != 0 && $now > $flashcard->endtime)){
        	echo $out;
            echo $OUTPUT->notification(get_string('outoftimerange', 'flashcard'));
	        echo $OUTPUT->footer();
	        die;
        }
    }    

/// loads "per instance" customisation styles

    $localstyle = "{$course->id}/moddata/flashcard/{$flashcard->id}/flashcard.css";
    if (file_exists("{$CFG->dataroot}/{$localstyle}")){
        if ($CFG->slasharguments) {
            $localstyleurl = $CFG->wwwroot.'/file.php/'.$localstyle;
        } else {
            if ($CFG->slasharguments){
                $localstyleurl = $CFG->wwwroot.'/file.php?file='.$localstyle;
            } else {
                $localstyleurl = $CFG->wwwroot.'/file.php'.$localstyle;
            }
        }
        $out .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$localstyleurl}\" />";
    }

/// Determine the current tab

    switch($view){
        case 'checkdecks' : $currenttab = 'play'; break;
        case 'play' : $currenttab = 'play'; break;
        case 'freeplay' : $currenttab = 'freeplay'; break;
        case 'summary' : $currenttab = 'summary'; break;
        case 'edit' : $currenttab = 'edit'; break;
        case 'manage' : $currenttab = 'manage'; break;
        default : $currenttab = 'play';
    }
    
    if($action == 'import') {
        $currenttab = 'import';
    }
    
/// print tabs
    if (!preg_match("/summary|freeplay|play|checkdecks|manage|edit/", $view)) $view = 'checkdecks';
    $tabname = get_string('leitnergame', 'flashcard');
    $row[] = new tabobject('play', $thisurl."?id={$cm->id}&amp;view=checkdecks", $tabname);
    $tabname = get_string('freegame', 'flashcard');
    $row[] = new tabobject('freeplay', $thisurl."?view=freeplay&amp;id={$cm->id}", $tabname);
    if (has_capability('mod/flashcard:manage', $context)){
        $tabname = get_string('teachersummary', 'flashcard');
        $row[] = new tabobject('summary', $thisurl."?view=summary&amp;id={$cm->id}&amp;page=byusers", $tabname);
        $tabname = get_string('edit', 'flashcard');
        $row[] = new tabobject('manage', $thisurl."?view=manage&amp;id={$cm->id}", $tabname);
        
        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_TEXT && $flashcard->answersmediatype == FLASHCARD_MEDIA_TEXT){
	        $tabname = get_string('import', 'flashcard');
	        $row[] = new tabobject('import', $thisurl."?what=import&amp;view=manage&amp;id={$cm->id}", $tabname);
	    }
    }
    $tabrows[] = $row;
    
    $activated = array();

/// print second line

    if ($view == 'edit'){
        $currenttab = 'manage';
    } elseif ($view == 'summary'){
        switch($page){
            case 'bycards' : {
                $currenttab = 'bycards';
                $activated[] = 'summary'; 
                break;
            }
            default : {
                $currenttab = 'byusers';
                $activated[] = 'summary';
            }
        }

        $tabname = get_string('byusers', 'flashcard');
        $row1[] = new tabobject('byusers', $thisurl."?id={$cm->id}&amp;view=summary&amp;page=byusers", $tabname);
        $tabname = get_string('bycards', 'flashcard');
        $row1[] = new tabobject('bycards', $thisurl."?id={$cm->id}&amp;view=summary&amp;page=bycards", $tabname);
        $tabrows[] = $row1;
    }

    $out .= print_tabs($tabrows, $currenttab, null, $activated, true);

/// print active view

    switch ($view){
        case 'summary' : 
            if (!has_capability('mod/flashcard:manage', $context)){
                redirect($thisurl."?view=checkdecks&amp;id={$cm->id}");
            }
    		add_to_log($course->id, 'flashcard', 'view summary', $thisurl."?id={$cm->id}", "{$flashcard->name}");
            if ($page == 'bycards'){
                include $CFG->dirroot.'/mod/flashcard/cardsummaryview.php';
            } else {
                include $CFG->dirroot.'/mod/flashcard/usersummaryview.php';
            }
            break;
        case 'manage' : 
            if (!has_capability('mod/flashcard:manage', $context)){
                redirect($thisurl."?view=checkdecks&amp;id={$cm->id}");
            }
    		add_to_log($course->id, 'flashcard', 'manage', $thisurl."?id={$cm->id}", "{$flashcard->name}");
            include $CFG->dirroot.'/mod/flashcard/managecards.php';
            break;
        case 'edit' : 
            if (!has_capability('mod/flashcard:manage', $context)){
                redirect($thisurl."?view=checkdecks&amp;id={$cm->id}");
            }
    		add_to_log($course->id, 'flashcard', 'edit', $thisurl."?id={$cm->id}", "{$flashcard->name}");
            include $CFG->dirroot.'/mod/flashcard/editview.php';
            break;
        case 'freeplay' :
    		add_to_log($course->id, 'flashcard', 'freeplay', $thisurl."?id={$cm->id}", "{$flashcard->name}");
            include $CFG->dirroot.'/mod/flashcard/freeplayview.php';
            break;
        case 'play' :
    		add_to_log($course->id, 'flashcard', 'play', $thisurl."?id={$cm->id}", "{$flashcard->name}");
            include $CFG->dirroot.'/mod/flashcard/playview.php';
            break;
        default :
    		add_to_log($course->id, 'flashcard', 'view', $thisurl."?id={$cm->id}", "{$flashcard->name}");
            include $CFG->dirroot.'/mod/flashcard/checkview.php';
    }

/// Finish the page

    echo $OUTPUT->footer($course);
?>
