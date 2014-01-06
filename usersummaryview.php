<?php

    /** 
    * This view provides a summary for the teacher
    * 
    * @package mod-flashcard
    * @category mod
    * @author Valery Fremaux, Gustav Delius, Tomasz Muras
    * @contributors
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    * @version Moodle 2.0
    */
    

    // security
    if (!defined('MOODLE_INTERNAL')){
        die("Illegal direct access to this screen");
    }

    echo $out; // deffered header()

	// small controller here...
    if ($action == 'reset'){
        $userid = required_param('userid', PARAM_INT);
        $DB->delete_records('flashcard_card', array('flashcardid' => $flashcard->id, 'userid' => $userid));
 
		$completion = new completion_info($course);
	 	if (($flashcard->completionallgood || $flashcard->completionallviewed) && $completion->is_enabled($cm)){			
			// Unmark completion state
		    $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
		}

    }

    require_once($CFG->dirroot.'/enrol/locallib.php');
    
    $coursecontext = context_course::instance($COURSE->id);
    $course = $DB->get_record('course', array('id' => $COURSE->id), '*', MUST_EXIST);

	$groupmode = groups_get_activity_groupmode($cm, $COURSE);
	if ($groupmode != NOGROUPS){
	    $groupid = groups_get_activity_group($cm, true);
		groups_print_activity_menu($cm, $url.'&view=summary&page=byusers');
	} else {
		$groupid = 0;
	}
    $courseusers = get_enrolled_users($coursecontext, '', $groupid);

    $struser = get_string('username');
    $strdeckstates = get_string('deckstates', 'flashcard');
    $strcounts = get_string('counters', 'flashcard');
    
    $table = new html_table();
    $table->head = array("<b>$struser</b>", "<b>$strdeckstates</b>", "<b>$strcounts</b>");
    $table->size = array('30%', '50%', '20%');
    $table->width = '100%';
    
    if (!empty($courseusers)){
        foreach($courseusers as $auser){
            $status = flashcard_get_deck_status($flashcard, $auser->id);
            $userbox = $OUTPUT->user_picture($auser);
            $userbox .= fullname($auser);
            if ($status){
                $flashcard->cm = &$cm;
                $deckbox = flashcard_print_deck_status($flashcard, $auser->id, $status, true);
                $countbox = flashcard_print_deckcounts($flashcard, true, $auser->id);
            } else {
                $deckbox = get_string('notinitialized', 'flashcard');
                $countbox = '';
            }
            $table->data[] = array($userbox, $deckbox, $countbox);
        }    
        echo html_writer::table($table);
    } else {
        echo '<center>';
        echo $OUTPUT->box(get_string('nousers', 'flashcard'));
        echo '</center>';
    }

