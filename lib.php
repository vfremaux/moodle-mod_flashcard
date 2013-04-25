<?php
/**
 * Library of functions and constants for module flashcard
 * @package mod-flashcard
 * @category mod
 * @author Gustav Delius
 * @contributors Valery Fremaux
 * @version Moodle 2.0
 */
/**
 * Includes and requires
 */

require_once($CFG->dirroot . '/lib/ddllib.php');
require_once($CFG->dirroot . '/mod/flashcard/locallib.php');

/**
 * Indicates API features that the forum supports.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function flashcard_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_RATE:                    return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}


/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 * @uses $COURSE, $DB
 */
function flashcard_add_instance($flashcard) {
    global $COURSE, $DB;

    $flashcard->timemodified = time();

    if (!isset($flashcard->starttimeenable)) {
        $flashcard->starttime = 0;
    }

    if (!isset($flashcard->endtimeenable)) {
        $flashcard->endtime = 0;
    }

	// saves draft customization image files into definitive filearea    
    $customimages = array('custombackfileid', 'customfrontfileid', 'customemptyfileid', 'customreviewfileid', 'customreviewedfileid', 'customreviewemptyfileid');
    foreach($customimages as $ci){
	    flashcard_save_draft_customimage($flashcard, $ci);
	}

    $newid = $DB->insert_record('flashcard', $flashcard);

    // Import all information from question
    if (isset($flashcard->forcereload) && $flashcard->forcereload) {
        flashcard_import($flashcard);
    }

    return $newid;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 * @uses $COURSE, $DB
 *
 */
function flashcard_update_instance($flashcard) {
    global $COURSE, $DB;

    $flashcard->timemodified = time();
    $flashcard->id = $flashcard->instance;

    // update first deck with questions that might be added

    if (isset($flashcard->forcereload) && $flashcard->forcereload) {
        flashcard_import($flashcard);
    }

    if (!isset($flashcard->starttimeenable)) {
        $flashcard->starttime = 0;
    }

    if (!isset($flashcard->endtimeenable)) {
        $flashcard->endtime = 0;
    }

	// saves draft customization image files into definitive filearea    
    $customimages = array('custombackfileid', 'customfrontfileid', 'customemptyfileid', 'customreviewfileid', 'customreviewedfileid', 'customreviewemptyfileid');
    foreach($customimages as $ci){
	    flashcard_save_draft_customimage($flashcard, $ci);
	}
	
    $return = $DB->update_record('flashcard', $flashcard);

    return $return;
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it.  
 * @uses $COURSE, $DB
 */
function flashcard_delete_instance($id) {
    global $COURSE, $DB;

    if (!$flashcard = $DB->get_record('flashcard', array('id' => $id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('flashcard', $flashcard->id)) {
        return false;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $result = true;

    // Delete any dependent records here
    $DB->delete_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));
    $DB->delete_records('flashcard_card', array('flashcardid' => $flashcard->id));

    // now get rid of all files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    if (!$DB->delete_records('flashcard', array('id' => $flashcard->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 */
function flashcard_user_outline($course, $user, $mod, $flashcard) {
    return $return;
}

/**
 * Print a detailed representation of what a  user has done with 
 * a given particular instance of this module, for user activity reports.
 */
function flashcard_user_complete($course, $user, $mod, $flashcard) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in flashcard activities and print it out. 
 * Return true if there was output, or false is there was none.
 * @uses $CFG
 */
function flashcard_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 * @uses $CFG
 *
 */
function flashcard_cron() {
    global $CFG, $DB;

    // get all flashcards
    $flashcards = $DB->get_records('flashcard');

    foreach ($flashcards as $flashcard) {
        if (!$flashcard->autodowngrade) continue;
        if ($flashcard->starttime != 0 && time() < $flashcard->starttime) continue;
        if ($flashcard->endtime != 0 && time() > $flashcard->endtime) continue;

        $cards = $DB->get_records_select('flashcard_card', 'flashcardid = ? AND deck > 1', array($flashcard->id));
        foreach ($cards as $card) {
            // downgrades to deck 3 (middle low)
            if ($flashcard->decks > 3) {
                if ($card->deck == 4 && time() > $card->lastaccessed + ($flashcard->deck4_delay * HOURSECS + $flashcard->deck4_release * HOURSECS)) {
                    $DB->set_field('flashcard_card', 'deck', 3, array('id' => $card->id));
                }
            }
            // downgrades to deck 2 (middle)
            if ($flashcard->decks > 2) {
                if ($card->deck == 3 && time() > $card->lastaccessed + ($flashcard->deck3_delay * HOURSECS + $flashcard->deck3_release * HOURSECS)) {
                    $DB->set_field('flashcard_card', 'deck', 2, array('id' => $card->id));
                }
            }
            // downgrades to deck 1 (difficult)
            if ($card->deck == 2 && time() > $card->lastaccessed + ($flashcard->deck2_delay * HOURSECS + $flashcard->deck2_release * HOURSECS)) {
                $DB->set_field('flashcard_card', 'deck', 1, array('id' => $card->id));
            }
        }
    }

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 *
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 */
function flashcard_grades($flashcardid) {
    return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of flashcard. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 * @uses $DB
 */
function flashcard_get_participants($flashcardid) {
    global $DB;

    $userids = $DB->get_records_menu('flashcard_card', array('flashcardid' => $flashcardid), '', 'userid,id');
    if ($userids) {
        $users = $DB->get_records_list('user', 'id', array_keys($userids));
    }

    if (!empty($users)) return $users;

    return false;
}

/**
 * This function returns if a scale is being used by one flashcard
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 */
function flashcard_scale_used($flashcardid, $scaleid) {

    $return = false;

    //$rec = get_record("flashcard","id","$flashcardid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

/**
 * Serves the files included in flashcard. Implements needed access control ;-)
 *
 * There are several situations in general where the files will be sent.
 * 1) filearea = 'questionsoundfile', 
 * 2) filearea = 'questionimagefile',
 * 3) filearea = 'questionvideofile',
 * 4) filearea = 'answersoundfile', 
 * 5) filearea = 'answerimagefile',
 * 6) filearea = 'answervideofile'
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function flashcard_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

	require_login($course);
	
    if ($context->contextlevel != CONTEXT_MODULE) {
    	return false;
    }

    if (!in_array($filearea, array('questionsoundfile', 'questionimagefile', 'questionvideofile', 'answersoundfile', 'answerimagefile', 'answervideofile', 'customfront', 'customempty', 'customback', 'customreview', 'customreviewed', 'customreviewempty'))) {
        return false;
    }

	$itemid = (int)array_shift($args);

    $fs = get_file_storage();
    
    if ($files = $fs->get_area_files($context->id, 'mod_flashcard', $filearea, $itemid, "sortorder, itemid, filepath, filename", false)){
    	$file = array_pop($files);

	    // finally send the file
	    send_stored_file($file, 0, 0, $forcedownload);
    }


    return false;
}

/**
 * Obtains the automatic completion state for this flashcard 
 *
 * @global object
 * @global object
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function flashcard_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get flashcard details
    if (!($flashcard = $DB->get_record('flashcard', array('id' => $cm->instance)))) {
        throw new Exception("Can't find flashcard {$cm->instance}");
    }

    $result = $type; // Default return value
    
    // completion condition 1 is have no cards in deck

    if ($flashcard->completionallviewed) {

		// match any card that may not be viewed by this user    	
    	$sql = "
    		SELECT
    			COUNT(*)
    		FROM
    			{flashcard_deckdata} dd
    		LEFT JOIN
    			{flashcard_card} c
    		ON 
    			c.entryid = dd.id AND
    			c.userid = ?
    		WHERE
    			dd.id is NULL    			
    	";
    	$unviewed = $DB->get_records_sql($sql, array($userid));

        if ($type == COMPLETION_AND) {
            $result = $result && empty($unviewed);
        } else {
            $result = $result || empty($unviewed);
        }
    }

    // completion condition 2 is : all cards in last deck (easiest)

    if ($flashcard->completionallgood) {

		// match any card that are NOT in last deck
    	$sql = "
    		SELECT
    			COUNT(*)
    		FROM
    			{flashcard_deckdata} dd
    		LEFT JOIN
    			{flashcard_card} c
    		ON 
    			c.entryid = dd.id AND
    			c.userid = ?
    		WHERE
    			dd.id is NULL OR
    			c.deck != ?
    	";
    	$nogood = $DB->get_records_sql($sql, array($userid, $flashcard->decks - 1));
		
        if ($type == COMPLETION_AND) {
            $result = $result && empty($nogood);
        } else {
            $result = $result || empty($nogood);
        }
    }

    return $result;
}
