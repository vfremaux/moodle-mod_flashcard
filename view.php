<?php  // $Id: view.php,v 1.1 2011-10-15 12:26:00 vf Exp $

    /** 
    * This page prints a particular instance of a flashcard
    * 
    * @package mod-flashcard
    * @category mod
    * @author Gustav Delius
    * @contributors Valery Fremaux
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    require_once("../../config.php");
    require_once("{$CFG->dirroot}/mod/flashcard/lib.php");
    require_once("{$CFG->dirroot}/mod/flashcard/locallib.php");

    $id = optional_param('id', '', PARAM_INT);    // Course Module ID, or
    $a = optional_param('a', '', PARAM_INT);     // flashcard ID
    $view = optional_param('view', 'checkdecks', PARAM_ACTION);     // view
    $page = optional_param('page', '', PARAM_ACTION);     // page
    $action = optional_param('what', '', PARAM_ACTION);     // command

    if ($id) {
        if (! $cm = get_record('course_modules', 'id', $id)) {
            error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (! $flashcard = get_record('flashcard', 'id', $cm->instance)) {
            error("Course module is incorrect");
        }
    } else {
        if (! $flashcard = get_record('flashcard', 'id', $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $flashcard->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("flashcard", $flashcard->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    add_to_log($course->id, 'flashcard', 'view', "view.php?id=$cm->id", "$flashcard->name");

/// Print the page header

    $strflashcards = get_string('modulenameplural', 'flashcard');
    $strflashcard  = get_string('modulename', 'flashcard');

    $navlinks = array(array('name' => $flashcard->name, 'link' => '', 'type' => 'title'));
    $navigation = build_navigation($navlinks);
    print_header("$course->shortname: $flashcard->name", 
                 "$course->fullname", 
                 $navigation, 
                 '', 
                 '', 
                 true, 
                 update_module_button($cm->id, $course->id, $strflashcard), 
                  navmenu($course, $cm));

/// non visible trap for timerange (security)
    if (!has_capability('moodle/course:viewhiddenactivities', $context) && !$cm->visible){
        error("This page cannot be accessed while module is not visible");
    }

/// non manager trap for timerange

    if (!has_capability('mod/flashcard:manage', $context)){
        $now = time();
        if (($flashcard->starttime != 0 && $now < $flashcard->starttime) || ($flashcard->endtime != 0 && $now > $flashcard->endtime)){
            error(get_string('outoftimerange', 'flashcard'));
        }
    }    

/// loads customisation styles

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
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$localstyleurl}\" />";
    }

/// Determine the current tab

    switch($view){
        case 'checkdecks' : $currenttab = 'play'; break;
        case 'play' : $currenttab = 'play'; break;
        case 'freeplay' : $currenttab = 'freeplay'; break;
        case 'summary' : $currenttab = 'summary'; break;
        case 'edit' : $currenttab = 'edit'; break;
        default : $currenttab = 'play';
    }

/// print tabs
	if (has_capability('mod/flashcard:canplayfree', $context)){
		helpbutton('freegame', get_string('freegame', 'flashcard'), 'flashcard', true, true, '', false);
	}
	helpbutton('leitnergame', get_string('leitnergame', 'flashcard'), 'flashcard', true, true, '', false);    
	if (!preg_match("/summary|freeplay|play|checkdecks|edit/", $view)) $view = 'checkdecks';
    $tabname = get_string('leitnergame', 'flashcard');
    $tabnametext = get_string('leitnergame', 'flashcard');
    $row[] = new tabobject('play', "view.php?id={$cm->id}&amp;view=checkdecks", $tabnametext, $tabname);
	if (has_capability('mod/flashcard:canplayfree', $context)){
	    $tabname = get_string('freegame', 'flashcard');
	    $tabnametext = get_string('freegame', 'flashcard');
	    $row[] = new tabobject('freeplay', "view.php?view=freeplay&amp;id={$cm->id}", $tabnametext, $tabname);
	}
    if (has_capability('mod/flashcard:manage', $context)){
        $tabname = get_string('teachersummary', 'flashcard');
        $row[] = new tabobject('summary', "view.php?view=summary&amp;id={$cm->id}&amp;page=byusers", $tabname);
        $tabname = get_string('edit', 'flashcard');
        $row[] = new tabobject('edit', "view.php?view=edit&amp;id={$cm->id}", $tabname);
    }
    $tabrows[] = $row;
    
    $activated = array();

/// print second line

    if ($view == 'summary'){
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
        $row1[] = new tabobject('byusers', "view.php?id={$cm->id}&amp;view=summary&amp;page=byusers", $tabname);
        $tabname = get_string('bycards', 'flashcard');
        $row1[] = new tabobject('bycards', "view.php?id={$cm->id}&amp;view=summary&amp;page=bycards", $tabname);
        $tabrows[] = $row1;
    }

    print_tabs($tabrows, $currenttab, null, $activated);

/// print summary

    if (!empty($flashcard->summary)) {
        print_box_start();
        echo format_text($flashcard->summary, $flashcard->summaryformat, NULL, $course->id);
        print_box_end();
    }

/// print active view

    switch ($view){
        case 'summary' : 
            if (!has_capability('mod/flashcard:manage', $context)){
                redirect("view.php?view=checkdecks&amp;id={$cm->id}");
            }
            if ($page == 'bycards'){
                include "cardsummaryview.php";
            } else {
                include "usersummaryview.php";
            }
            break;
        case 'edit' : 
            if (!has_capability('mod/flashcard:manage', $context)){
                redirect("view.php?view=checkdecks&amp;id={$cm->id}");
            }
            include "editview.php";
            break;
        case 'freeplay' :
            include "freeplayview.php";
            break;
        case 'play' :
            include "playview.php";
            break;
        default :
            include "checkview.php";
    }

/// Finish the page

    print_footer($course);
?>