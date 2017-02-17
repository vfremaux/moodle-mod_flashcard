<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This view allows playing with a deck
 *
 * @package mod_flashcard
 * @category mod
 * @author Gustav Delius
 * @author Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version Moodle 2.0
 */
defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js('/mod/flashcard/players/flowplayer/flowplayer.js');

// Invoke controller.

// We need it in controller.
$deck = required_param('deck', PARAM_INT);

$subquestions = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));

if ($action != '') {
    include($CFG->dirroot.'/mod/flashcard/playview.controller.php');
}

// Unmark state for this deck.
$params = array('userid' => $USER->id, 'flashcardid' => $flashcard->id, 'deck' => $deck);
$DB->delete_records('flashcard_userdeck_state', $params); 

if (empty($subquestions)) {
    echo $out;
    echo $OUTPUT->box_start();
    echo print_string('undefinedquestionset', 'flashcard');
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer($course);
    return;
}

$consumed = explode(',', @$_SESSION['flashcard_consumed']);
$subquestions = array();
list($usql, $params) = $DB->get_in_or_equal($consumed, SQL_PARAMS_QM, 'param0000', false); // Negative IN.

$select = "
    flashcardid = {$flashcard->id} AND
    userid = {$USER->id} AND
    deck = {$deck} AND
    id $usql
";

if ($cards = $DB->get_records_select('flashcard_card', $select, $params)) {
    foreach ($cards as $card) {
        $obj = new stdClass();
        $obj->entryid = $card->entryid;
        $obj->cardid = $card->id;
        $subquestions[] = $obj;
    }
} else {
    echo $out;
    echo $OUTPUT->box(get_string('nomorecards', 'flashcard'), 'notify');
    echo $OUTPUT->continue_button($thisurl."?view=checkdecks&amp;id={$cm->id}");
    echo $OUTPUT->footer($course);
    die;
}

// Print deferred header.

echo $out;

// Randomize and get a question (obviously it is not a consumed question).

$random = rand(0, count($subquestions) - 1);
$subquestion = $DB->get_record('flashcard_deckdata', array('id' => $subquestions[$random]->entryid));

$back = 'question';
$front = 'answer';

if ($flashcard->flipdeck) {
    // Flip card side values.
    $tmp = $subquestion->answertext;
    $subquestion->answertext = $subquestion->questiontext;
    $subquestion->questiontext = $tmp;
    $back = 'answer';
    $front = 'question';
    // Flip media types.
    $tmp = $flashcard->answersmediatype;
    $flashcard->answersmediatype = $flashcard->questionsmediatype;
    $flashcard->questionsmediatype = $tmp;
}

$acardvideoclass = ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) ? '-video' : '';
$qcardvideoclass = ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) ? '-video' : '';

$autoplay = ($flashcard->audiostart) ? 'true' : 'false';

echo $renderer->playview();