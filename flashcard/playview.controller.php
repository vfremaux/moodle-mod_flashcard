<?php

/** 
* a controller for the play view
* 
* @package mod-flashcard
* @category mod
* @author Valery Fremaux
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
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
    if ($initials = get_records_menu('flashcard_card', "flashcardid = {$flashcard->id} AND userid = {$USER->id} AND deck = {$deck}")){   
        $_SESSION['flashcard_initials'] = implode("','", array_keys($initials));
    }
    unset($_SESSION['flashcard_consumed']);
}
/*---------------------------------- reset a deck ---------------------------------*/
if ($action == 'reset'){
    $initials = $_SESSION['flashcard_initials'];
    set_field_select('flashcard_card', 'deck', $deck, "id IN ('{$initials}')");
    unset($_SESSION['flashcard_consumed']);
}
/*---------------------------------- a card was declared right --------------------*/
if ($action == 'igotit'){       
    $card->id = required_param('cardid', PARAM_INT);
    $card = get_record('flashcard_card', 'id', $card->id);
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
    if (!update_record('flashcard_card', $card)){
        error("Could not change card info");
    }
}
if ($action == 'ifailed'){
    $card->id = required_param('cardid', PARAM_INT);
    $card = get_record('flashcard_card', 'id', $card->id);
    $card->lastaccessed = time();
    $card->accesscount++ ;
    if (!update_record('flashcard_card', $card)){
        error("Could not change card info");
    }
    if (array_key_exists('flashcard_consumed', $_SESSION)){
        $_SESSION['flashcard_consumed'] .= ','.$card->id;
    } else {
        $_SESSION['flashcard_consumed'] = $card->id;
    }
}
?>