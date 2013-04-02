<?php

    /** 
    * This view provides a summary for the teacher
    * 
    * @package mod-flashcard
    * @category mod
    * @author Valery Fremaux, Gustav Delius
    * @contributors
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    // security
    if (!defined('MOODLE_INTERNAL')){
        error("Illegal direct access to this screen");
    }

    if ($action != ''){
        include "{$CFG->dirroot}/mod/flashcard/usersummaryview.controller.php";
    }

    $courseusers = get_course_users($COURSE->id);
        
    $struser = get_string('username');
    $strdeckstates = get_string('deckstates', 'flashcard');
    $strcounts = get_string('counters', 'flashcard');

    $table->head = array("<b>$struser</b>", "<b>$strdeckstates</b>", "<b>$strcounts</b>");
    $table->size = array('30%', '50%', '20%');
    $table->width = "90%";
    
    if (!empty($courseusers)){
        foreach($courseusers as $auser){
            $status = flashcard_get_deck_status($flashcard, $auser->id);
            // if (has_capability('mod/flashcard:manage', $context, $auser->id)) continue;
            $userbox = print_user_picture ( $auser, $COURSE->id, true, false, true, true, '', true); 
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
        print_table($table);
    } else {
        echo "<center>";
        print_box(get_string('nousers', 'flashcard'));
        echo "</center>";
    }
    
?>