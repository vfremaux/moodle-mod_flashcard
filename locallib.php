<?php
/* @var $DB mysqli_native_moodle_database */
/* @var $OUTPUT core_renderer */
/* @var $PAGE moodle_page */
?>
<?php

/**
* internal library of functions and constants for module flashcard
* @package mod-flashcard
* @category mod
* @author Gustav Delius
* @contributors Valery Fremaux
* @version Moodle 2.0
*/

/**
* Includes and requires
*/

/**
*
*/
define('FLASHCARD_MEDIA_TEXT', 0); 
define('FLASHCARD_MEDIA_IMAGE', 1); 
define('FLASHCARD_MEDIA_SOUND', 2); 
define('FLASHCARD_MEDIA_IMAGE_AND_SOUND', 3); 
define('FLASHCARD_MEDIA_VIDEO', 4); 

/**
* computes the last accessed date for a deck as the oldest card being in the deck
* @param reference $flashcard the flashcard object
* @param int $deck the deck number
* @param int $userid the user the deck belongs to
* @uses $USER for setting default user
* @uses $CFG, $DB
*/
function flashcard_get_lastaccessed(&$flashcard, $deck, $userid = 0){
    global $USER, $CFG, $DB;
    
    if ($userid == 0) $userid = $USER->id;
    
    $sql = "
        SELECT 
            MIN(lastaccessed) as lastaccessed
        FROM
            {flashcard_card}
        WHERE
            flashcardid = ? AND
            userid = ? AND
            deck = ?
    ";
    $rec = $DB->get_record_sql($sql, array($flashcard->id, $userid, $deck));
    return $rec->lastaccessed;
}



/**
* initialize decks for a given user. The initialization is soft as it will 
* be able to add new subquestions
* @param reference $flashcard
* @param int $userid
* @ues $DB
*/
function flashcard_initialize(&$flashcard, $userid){
    global $DB;
    
    // get all cards (all decks)
    $cards = $DB->get_records_select('flashcard_card', 'flashcardid = ? AND userid = ?', array($flashcard->id, $userid));
    $registered = array();
    if (!empty($cards)){
        foreach($cards as $card){
            $registered[] = $card->entryid;
        }
    }

    // get all subquestions
    if ($subquestions = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id), '', 'id,id')){
        foreach($subquestions as $subquestion){
            if (in_array($subquestion->id, $registered)) continue;
            $card = new StdClass();
            $card->userid = $userid;
            $card->flashcardid = $flashcard->id;
            $card->lastaccessed = time() - ($flashcard->deck1_delay * HOURSECS);
            $card->deck = 1;
            $card->entryid = $subquestion->id;
            if (! $DB->insert_record('flashcard_card', $card)){
                print_error('dbcouldnotinsert', 'flashcard');
            }
        }
    } else {
        return false;
    }

    return true;
}

/**
* imports data into the deck from a matching question. This allows making a quiz with questions
* then importing data to form a card deck.
* @param reference $flashcard
* @uses $DB
* @return void
*/
function flashcard_import(&$flashcard){
    global $DB;
    
	$question = $DB->get_record('question', array('id' => $flashcard->questionid));
	
	if ($question->qtype != 'match'){
	    notice("Not a match question. Internal error");
	    return;
	}

    $options = $DB->get_record('question_match', array('question' => $question->id));
    list($usql, $params) = $DB->get_in_or_equal(explode(",",$options->subquestions));
    if ($subquestions = $DB->get_records_select('question_match_sub', "id $usql AND answertext != '' AND questiontext != ''", $params)){
    	
        // cleanup the flashcard
        $DB->delete_records('flashcard_card', array('flashcardid' => $flashcard->id));
        $DB->delete_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));

        
        
        // transfer data
        foreach($subquestions as $subquestion){
            $deckdata->flashcardid = $flashcard->id;
            $deckdata->questiontext = $subquestion->questiontext;
            $deckdata->answertext = $subquestion->answertext;
            $deckdata->lastaccessed = 0;
            $DB->insert_record('flashcard_deckdata', $deckdata); 
        }
    }
    return true;
}

/**
* get count, last access time and reactivability for all decks
* @param reference $flashcard
* @param int $userid
* @uses $USER
* @uses $DB
*/
function flashcard_get_deck_status(&$flashcard, $userid = 0){
    global $USER, $DB;
    
    if ($userid == 0) $userid = $USER->id;
    
    unset($status);
    
    $dk3 = 0;
    $dk4 = 0;
    $dk1 = $DB->count_records('flashcard_card', array('flashcardid' => $flashcard->id, 'userid' => $userid, 'deck' => 1));
    $status = new StdClass();
    $status->decks[0] = new StdClass();
    $status->decks[0]->count = $dk1;
    $status->decks[0]->deckid = 1;
    $dk2 = $DB->count_records('flashcard_card', array('flashcardid' => $flashcard->id, 'userid' => $userid, 'deck' => 2));
    $status->decks[1] = new StdClass();
    $status->decks[1]->count = $dk2;
    $status->decks[1]->deckid = 2;
    if ($flashcard->decks >= 3){
        $dk3 = $DB->count_records('flashcard_card', array('flashcardid' => $flashcard->id, 'userid'=> $userid, 'deck' => 3));
    	$status->decks[2] = new StdClass();
        $status->decks[2]->count = $dk3;
        $status->decks[2]->deckid = 3;
    }
    if ($flashcard->decks >= 4){
        $dk4 = $DB->count_records('flashcard_card', array('flashcardid' => $flashcard->id, 'userid' => $userid, 'deck' => 4));
    	$status->decks[3] = new StdClass();
        $status->decks[3]->count = $dk4;
        $status->decks[3]->deckid = 4;
    }
    
    // not initialized for this user
    if ($dk1 + $dk2 + $dk3 + $dk4 == 0){
        return null;
    }

    if ($dk1 > 0){
        $status->decks[0]->lastaccess = flashcard_get_lastaccessed($flashcard, 1, $userid);
        $status->decks[0]->reactivate = (time() > ($status->decks[0]->lastaccess + $flashcard->deck1_delay * HOURSECS));
    }
    if ($dk2 > 0){
        $status->decks[1]->lastaccess = flashcard_get_lastaccessed($flashcard, 2, $userid);
        $status->decks[1]->reactivate = (time() > ($status->decks[1]->lastaccess + $flashcard->deck2_delay * HOURSECS));
    }
    if ($flashcard->decks >= 3 && $dk3 > 0){
        $status->decks[2]->lastaccess = flashcard_get_lastaccessed($flashcard, 3, $userid);
        $status->decks[2]->reactivate = (time() > ($status->decks[2]->lastaccess + $flashcard->deck3_delay * HOURSECS));
    }
    if ($flashcard->decks >= 4 && $dk4 > 0){
        $status->decks[3]->lastaccess = flashcard_get_lastaccessed($flashcard, 4, $userid);
        $status->decks[3]->reactivate = (time() > ($status->decks[3]->lastaccess + $flashcard->deck4_delay));
    }
       
    return $status;
}

/**
* get card status structure
* @param reference $flashcard
* @uses $CFG
* @uses $DB
*/
function flashcard_get_card_status(&$flashcard){
    global $CFG, $DB;
    
    // get decks by card
    $sql = "
        SELECT
           dd.questiontext,
           COUNT(c.id) as amount,
           c.deck AS deck
        FROM
            {flashcard_deckdata} dd
        LEFT JOIN
            {flashcard_card} c
        ON 
            c.entryid = dd.id
        WHERE
            c.flashcardid = ?
        GROUP BY
            c.entryid,
            c.deck
    ";
    $recs = $DB->get_records_sql($sql, array($flashcard->id));

    // get accessed by card
    $sql = "
        SELECT
           dd.questiontext,
           SUM(accesscount) AS accessed
        FROM
            {flashcard_deckdata} dd
        LEFT JOIN
            {flashcard_card} c
        ON 
            c.entryid = dd.id
        WHERE
            c.flashcardid = ?
        GROUP BY
            c.entryid
    ";
    $accesses = $DB->get_records_sql($sql, array($flashcard->id));
    
    $cards = array();
    foreach($recs as $question => $rec){
        if ($rec->deck == 1)
            $cards[$question]->deck[0] = $rec->amount;
        if ($rec->deck == 2)
            $cards[$question]->deck[1] = $rec->amount;
        if ($rec->deck == 3)
            $cards[$question]->deck[2] = $rec->amount;
        if ($rec->deck == 4)
            $cards[$question]->deck[3] = $rec->amount;
        $cards[$question]->accesscount = $accesses[$question]->accessed;
    }
    return $cards;
}

/**
* prints a graphical represnetation of decks, proportionnaly to card count
* @param reference $flashcard
* @param object $card
* @param boolean $return
* @uses $CFG
*/
function flashcard_print_cardcounts(&$flashcard, $card, $return=false){
    global $CFG, $OUTPUT;
    
    $str = '';
    
    $topenabledpixurl = $OUTPUT->pix_url('topenabled', 'flashcard');
    
    $strs[] = "<td><img src=\"{$topenabledpixurl}\" /> (1) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[0]).'px"></div></td>';
    $strs[] = "<td><img src=\"{$topenabledpixurl}\" /> (2) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[1]).'px"></div></td>';
    if ($flashcard->decks >= 3){
        $strs[] = "<td><img src=\"{$topenabledpixurl}\" /> (3) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[2]).'px"></div></td>';
    }
    if ($flashcard->decks >= 4){
        $strs[] = "<td><img src=\"{$topenabledpixurl}\" /> (4) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[3]).'px"></div></td>';
    }
    
    $str = "<table cellspacing=\"2\"><tr valign\"middle\">".implode("</tr><tr valign=\"middle\">", $strs)."</tr></table>";
    
    if ($return) return $str;
    echo $str;
}

/**
* new media renderers cannot be used because not tunable in autoplay
* @TODO : remove as deprecated. Dewplayer more stable.
*/
function flashcard_mp3_player(&$flashcard, $url, $htmlid) {
    global $CFG, $THEME;

	$audiostart = ($flashcard->audiostart) ? 'no' : 'yes&autoPlay=yes' ;
    $c = 'bgColour=000000&btnColour=ffffff&btnBorderColour=cccccc&iconColour=000000&'.
         'iconOverColour=00cc00&trackColour=cccccc&handleColour=ffffff&loaderColour=ffffff&'.
         'waitForPlay='.$audiostart;

    static $count = 0;
    $count++;
    $id = ($htmlid) ? $htmlid : 'flashcard_filter_mp3_'.time().$count ; //we need something unique because it might be stored in text cache

    $url = addslashes_js($url);

    return '<span class="mediaplugin mediaplugin_mp3" id="'.$id.'_player">('.'mp3audio'.')</span>
<script type="text/javascript">
//<![CDATA[
  var FO = { movie:"'.$CFG->wwwroot.'/mod/flashcard/players/mp3player/mp3player.swf?src='.$url.'",
    width:"90", height:"15", majorversion:"6", build:"40", flashvars:"'.$c.'", quality: "high" };
  UFO.create(FO, "'.$id.'_player");
//]]>
</script>';
}

function flashcard_mp3_dewplayer(&$flashcard, $url, $htmlid){
    global $CFG, $THEME;
    
    $audiostart = ($flashcard->audiostart) ? 1 : 0;

	$playerflashurl = $CFG->wwwroot.'/mod/flashcard/players/dewplayer/dewplayer-mini.swf';
	$return = '<object type="application/x-shockwave-flash" data="'.$playerflashurl.'" width="160" height="20" id="'.$htmlid.'" name="dewplayer">';
	$return .= '<param name="wmode" value="transparent" />';
	$return .= '<param name="movie" value="dewplayer-mini.swf" />';
	$return .= '<param name="flashvars" value="mp3='.urlencode($url).'&amp;autostart='.$audiostart.'" />';
	$return .= '</object>';
	
	return $return;
}

function flashcard_flowplayer($flashcard, $videofileurl, $videotype, $htmlname, $thumb){
 	global $CFG;
 	
 	$playerclass = ($thumb) ? 'flashcard-flowplayer-thumb' : 'flashcard-flowplayer' ;
 	
 	$str = '';
 	
 	$str .= '<div id="'.$htmlname.'_player" style="z-index:10000" data-swf="'.$CFG->wwwroot.'/mod/flashcard/players/flowplayer/flowplayer.swf" class="flowplayer '.$playerclass.' play-button" data-ratio="0.416">';
	$str .= '<video preload="none">';
	$str .= '<source type="video/'.$videotype.'" src="'.$videofileurl.'"/>';
	$str .= '</video>';
      
	$str .= '</div>';
	
	return $str;
}

function flashcard_delete_attached_files(&$cm, &$flashcard, $card){
	
	$fs = get_file_storage();

	$context = context_module::instance($cm->id);
	
	switch($flashcard->questionsmediatype){
		case FLASHCARD_MEDIA_TEXT :
			break;
		case FLASHCARD_MEDIA_SOUND :
			$fs->delete_area_files($context->id, 'flashcard', 'questionsoundfile', $card->id);
			break;
		case FLASHCARD_MEDIA_IMAGE :
			$fs->delete_area_files($context->id, 'flashcard', 'questionimagefile', $card->id);
			break;
		case FLASHCARD_MEDIA_VIDEO :
			$fs->delete_area_files($context->id, 'flashcard', 'questionvideofile', $card->id);
			break;
		case FLASHCARD_MEDIA_IMAGE_AND_SOUND :
			$fs->delete_area_files($context->id, 'flashcard', 'questionimagefile', $card->id);
			$fs->delete_area_files($context->id, 'flashcard', 'questionsoundfile', $card->id);
			break;
	}	

	switch($flashcard->answersmediatype){
		case FLASHCARD_MEDIA_TEXT :
			break;
		case FLASHCARD_MEDIA_SOUND :
			$fs->delete_area_files($context->id, 'flashcard', 'answersoundfile', $card->id);
			break;
		case FLASHCARD_MEDIA_IMAGE :
			$fs->delete_area_files($context->id, 'flashcard', 'answerimagefile', $card->id);
			break;
		case FLASHCARD_MEDIA_VIDEO :
			$fs->delete_area_files($context->id, 'flashcard', 'answervideofile', $card->id);
			break;
		case FLASHCARD_MEDIA_IMAGE_AND_SOUND :
			$fs->delete_area_files($context->id, 'flashcard', 'answersoundfile', $card->id);
			$fs->delete_area_files($context->id, 'flashcard', 'answerimagefile', $card->id);
			break;
	}	
}

function flashcard_save_draft_customimage(&$flashcard, $customimage){
	global $USER;
	
	$usercontext = context_user::instance($USER->id);
	$context = context_module::instance($flashcard->coursemodule);

    $filepickeritemid = optional_param($customimage, 0, PARAM_INT);
    
	if (!$filepickeritemid) return;
	
	$fs = get_file_storage();

    $flashcard->$customimage = 0;
	if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
		$filearea = str_replace('fileid', '', $customimage);
		file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', $filearea, 0);
		$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', $filearea, 0);
		$savedfile = array_pop($savedfiles);
    	$flashcard->$customimage = $savedfile->get_id();
	}	
}

/**
* this initializes a draft copy of the actual stored file
*
*/
/*
function flashcard_get_initialized_draft_zone($fileid){
	global $USER;
	
	$fs = get_file_storage();

	$usercontext = context_user::instance($USER->id);
	$fr->contextid = $usercontext->id;
	$fr->component = 'user';
	$fr->filearea = 'draft';
	$fr->itemid = file_get_unused_draft_itemid();

	create_file_from_storedfile($fr, $fileid);
	
	return $fr->itemid;
}
*/