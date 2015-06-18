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
 * @package mod-flashcard
 * @category mod
 * @author Gustav Delius
 * @author Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version Moodle 2.0
 */

// Security.

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

$PAGE->requires->js('/mod/flashcard/players/flowplayer/flowplayer.js');

// Invoke controller.

// We need it in controller.
$deck = required_param('deck', PARAM_INT);

$subquestions = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));

if ($action != '') {
    include($CFG->dirroot.'/mod/flashcard/playview.controller.php');
}

// unmark state for this deck
$DB->delete_records('flashcard_userdeck_state', array('userid' => $USER->id, 'flashcardid' => $flashcard->id, 'deck' => $deck)); 

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
list($usql, $params) = $DB->get_in_or_equal($consumed, SQL_PARAMS_QM, 'param0000', false); // negative IN

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
    // redirect($thisurl."?view=checkdecks&amp;id={$cm->id}");
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

$acardvideoclass = ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) ? '-video' : '' ;
$qcardvideoclass = ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) ? '-video' : '' ;

$autoplay = ($flashcard->audiostart) ? 'true' : 'false';
?>

<script type="text/javascript">
var qtype = "<?php echo $flashcard->questionsmediatype ?>";
var atype = "<?php echo $flashcard->answersmediatype ?>";
var maxitems = <?php echo count($cards) ?>;
</script>

<style>
    <?php echo $flashcard->extracss ?>
</style>

<div id="flashcard_board">
    <div id="flashcard_header">
    <?php echo $OUTPUT->heading($flashcard->name);  ?>
    <p> <?php print_string('instructions', 'flashcard'); ?></p>
    </div>

    <center>

    <div id="questiondiv" style=";background-repeat:no-repeat;background-image:url(<?php echo $renderer->print_custom_url($flashcard, 'customback', 0) ?>)" class="flashcard-question<?php echo $qcardvideoclass ?>" onclick="javascript:togglecard()">
        <?php
        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
            echo $renderer->print_image($flashcard, "{$back}imagefile/{$subquestion->id}");
        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND) {
            echo $renderer->play_sound($flashcard, "{$back}soundfile/{$subquestion->id}", $autoplay, 'bell_q');
        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO) {
            echo $renderer->play_video($flashcard, "{$back}videofile/{$subquestion->id}", $autoplay, 'bell_q');
        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
            echo $renderer->print_image($flashcard, "{$back}imagefile/{$subquestion->id}");
            echo "<br/>";
            echo $renderer->play_sound($flashcard, "{$back}soundfile/{$subquestion->id}", $autoplay, 'bell_q');
        } else {
            echo format_text($subquestion->questiontext, FORMAT_HTML);
        }
        ?>
    </div>

    <div id="answerdiv" style="display:none;background-repeat:no-repeat;background-image:url(<?php echo $renderer->print_custom_url($flashcard, 'customfront', 0) ?>)" class="flashcard-answer<?php echo $acardvideoclass ?>" onclick="javascript:togglecard()">
        <?php
        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
            echo $renderer->print_image($flashcard, "{$front}imagefile/{$subquestion->id}");
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
            echo $renderer->play_sound($flashcard, "{$front}soundfile/{$subquestion->id}", $autoplay, 'bell_a');
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
            echo $renderer->play_video($flashcard, "{$front}videofile/{$subquestion->id}", $autoplay, 'bell_a');
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
            echo $renderer->print_image($flashcard, "{$front}imagefile/{$subquestion->id}");
            echo "<br/>";
            echo $renderer->play_sound($flashcard, "{$front}soundfile/{$subquestion->id}", $autoplay, 'bell_a');
        } else {
            echo format_text($subquestion->answertext,FORMAT_HTML);
        }
        ?>
    </div>

    <div id="flashcard_controls">
        <p><?php print_string('cardsremaining', 'flashcard'); ?>: <span id="remain"><?php echo count($subquestions);?></span></p>

        <?php
        $options['id'] = $cm->id;
        $options['what'] = 'igotit';
        $options['view'] = 'play';
        $options['deck'] = $deck;
        $options['cardid'] = $subquestions[$random]->cardid;
        echo $OUTPUT->single_button(new moodle_url('view.php',$options), get_string('igotit', 'flashcard'), 'post', array('class'=>'flashcard_playbutton'));
        ?>

        <?php
        $options['id'] = $cm->id;
        $options['what'] = 'ifailed';
        $options['view'] = 'play';
        $options['deck'] = $deck;
        $options['cardid'] = $subquestions[$random]->cardid;
        echo $OUTPUT->single_button(new moodle_url('view.php', $options), get_string('ifailed', 'flashcard'), 'post', array('class'=>'flashcard_playbutton'));
        ?>
        <br />
        <?php
        /*
        $options['id'] = $cm->id;
        $options['what'] = 'reset';
        $options['view'] = 'play';
        $options['deck'] = $deck;
        echo $OUTPUT->single_button(new moodle_url('view.php', $options), get_string('reset', 'flashcard'), 'post');
        */
        ?>
        <br/>
        <a href="<?php echo $thisurl ?>?id=<?php echo $cm->id ?>&amp;view=checkdecks"><?php print_string('backtodecks', 'flashcard') ?></a>
        - <a href="<?php echo $CFG->wwwroot ?>/course/view.php?id=<?php echo $course->id ?>"><?php print_string('backtocourse', 'flashcard') ?></a>
    </div>
</center>
</div>
