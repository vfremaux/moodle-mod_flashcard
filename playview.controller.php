<?php

/** 
* a controller for the play view
* 
* @package mod-flashcard
* @category mod
* @author Valery Fremaux
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @version Moodle 2.0
*
* @usecase initialize
* @usecase reset
* @usecase igotit
* @usecase ifailed
*/

// security
if (!defined('MOODLE_INTERNAL')){
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

/*---------------------------------- initialize a deck ---------------------------------*/
if ($action == 'initialize'){
    if ($initials = $DB->get_records_select('flashcard_card', "flashcardid = ? AND userid = ? AND deck = ? ", array($flashcard->id, $USER->id, $deck))){   
        $_SESSION['flashcard_initials'] = implode("','", array_keys($initials));
    }
    unset($_SESSION['flashcard_consumed']);
}
/*---------------------------------- reset a deck ---------------------------------*/
if ($action == 'reset'){
    $initials = explode("','", $_SESSION['flashcard_initials']);
    list($usql, $params) = $DB->get_in_or_equal(array_keys($initials));
    $DB->set_field_select('flashcard_card', 'deck', $deck, "id $usql ", $params);
    unset($_SESSION['flashcard_consumed']);         
}
/*---------------------------------- a card was declared right --------------------*/
if ($action == 'igotit'){
	$card = new StdClass;     
    $card->id = required_param('cardid', PARAM_INT);
    $card = $DB->get_record('flashcard_card', array('id' => $card->id));    
    $olddeck = $card->deck;
    if ($card->deck < $flashcard->decks){
        $card->deck = $deck + 1;
    } else {
        // if in last deck, consume it !!
        if (array_key_exists('flashcard_consumed', $_SESSION)){
            $_SESSION['flashcard_consumed'] .= ','.$card->id;
        } else {
            $_SESSION['flashcard_consumed'] = $card->id;
        }
    }
    $card->lastaccessed = time();
    $card->accesscount++ ;
    if (!$DB->update_record('flashcard_card', $card)){
        print_error('dbcouldnotupdate', 'flashcard', '', get_string('cardinfo', 'flashcard'));
    }

	// If pre-last deck, we need check the completion condition for "all good", that is this is the
	// last card that is passing to the last deck
	// note we will also confirm in checkview.php

	$completion = new completion_info($course);
	if ($completion->is_enabled($cm)){
		
		if ($flashcard->completionallgood){
		
			if ($olddeck == $flashcard->decks - 1){
				
				$lastdeckcards = $DB->count_records('flashcard_card', array('userid' => $USER->id, 'flashcardid' => $flashcard->id, 'deck' => $flashcard->decks));
				$allcards = count($subquestions); // @see playview.php
				
				if ($lastdeckcards == $allcards){ 
					// whatever the status of the last deck, we have brought all the cards there	
					// Update completion state
				    $completion->update_state($cm, COMPLETION_COMPLETE);
				}
			}
		} elseif ($flashcard->completionallviewed){
 			// allgood superseedes allviewed
 			// deck does not matter here, all viewed cards in all decks... usually the first one
			$allseencards = $DB->count_records('flashcard_card', array('userid' => $USER->id, 'flashcardid' => $flashcard->id));
			$allcards = count($subquestions); // @see playview.php
			if ($seencards >= min($allcards, $flashcard->completionallviewed)){
				// Update completion state
				$completion = new completion_info($course);
			    $completion->update_state($cm, COMPLETION_COMPLETE);
			}
		}
	}
}
/*------------------------------ a card was declared wrong -----------------------*/
if ($action == 'ifailed'){
	$card = new StdClass();
    $card->id = required_param('cardid', PARAM_INT);
    $card = $DB->get_record('flashcard_card', array('id' => $card->id));
    $card->lastaccessed = time();
    $card->accesscount++ ;
    // $card->deck--; // just for debug
    if (!$DB->update_record('flashcard_card', $card)){
        print_error('dbcouldnotupdate', 'flashcard', '', get_string('cardinfo', 'flashcard'));
    }
    if (array_key_exists('flashcard_consumed', $_SESSION)){
        $_SESSION['flashcard_consumed'] .= ','.$card->id;
    } else {
        $_SESSION['flashcard_consumed'] = $card->id;
    }

	// note allgood cannot win with a failed card as card do not move
	$completion = new completion_info($course);
	if ($completion->is_enabled($cm)){		
		if ($flashcard->completionallviewed){
			// deck does not matter here, all viewed cards in all decks... usually the first one
			$allseencards = $DB->count_records('flashcard_card', array('userid' => $USER->id, 'flashcardid' => $flashcard->id));
			$allcards = count($subquestions); // @see playview.php
			if ($seencards >= min($allcards, $flashcard->completionallviewed)){
				// Update completion state
			    $completion->update_state($cm, COMPLETION_COMPLETE);
			}
		}
	}
}
