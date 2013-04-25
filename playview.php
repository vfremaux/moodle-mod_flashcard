<?php

    /** 
    * This view allows playing with a deck
    * 
    * @package mod-flashcard
    * @category mod
    * @author Gustav Delius
    * @contributors Valery Fremaux
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    * @version Moodle 2.0
    */

/// Security

    if (!defined('MOODLE_INTERNAL')){
        die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
    }

/// Invoke controller

    // we need it in controller
    $deck = required_param('deck', PARAM_INT);

    if ($action != ''){
        include $CFG->dirroot.'/mod/flashcard/playview.controller.php';
    }

    $subquestions = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));
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
	if ($cards = $DB->get_records_select('flashcard_card', $select, $params)){
    	foreach($cards as $card){
    	    $obj = new stdClass();
    	    $obj->entryid = $card->entryid;
    	    $obj->cardid = $card->id;
    	    $subquestions[] = $obj;
    	}
    } else {
    	echo $out;
        echo $OUTPUT->continue_button(get_string('nomorecards', 'flashcard'), $thisurl."?view=checkdecks&amp;id={$cm->id}");
        echo $OUTPUT->footer($course);
        die;
        // redirect($thisurl."?view=checkdecks&amp;id={$cm->id}");
    }

/// print deferred header

	echo $out;
    
/// randomize and get a question (obviously it is not a consumed question).
    
    $random = rand(0, count($subquestions) - 1);
    $subquestion = $DB->get_record('flashcard_deckdata', array('id' => $subquestions[$random]->entryid));

    if ($flashcard->flipdeck){
        // flip card side values
        $tmp = $subquestion->answertext;
        $subquestion->answertext = $subquestion->questiontext;
        $subquestion->questiontext = $tmp;
        // flip media types
        $tmp = $flashcard->answersmediatype;
        $flashcard->answersmediatype = $flashcard->questionsmediatype;
        $flashcard->questionsmediatype = $tmp;
    }

    $autoplay = ($flashcard->audiostart) ? 'true' : 'false';
?>

		<script type="text/javascript">
		var qtype = "<?php echo $flashcard->questionsmediatype ?>";
		var atype = "<?php echo $flashcard->answersmediatype ?>";
		</script>

        <div id="flashcard_board" style="text-align: center;">
          <div id="flashcard_header">
          <?php echo $OUTPUT->heading($flashcard->name);  ?>
            <p> <?php print_string('instructions', 'flashcard'); ?></p>

          </div>

        <div id="questiondiv" class="flashcard-question backside-default" onclick="javascript:togglecard()"  style="display:block;">
            <?php
            if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
                flashcard_print_image($flashcard, $subquestion->questiontext);
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND){
                flashcard_play_sound($flashcard, $subquestion->questiontext, $autoplay, false, 'bell_q');
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO){
                flashcard_play_video($flashcard, $subquestion->questiontext, $autoplay, false, 'bell_q');
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
                list($image, $sound) = split('@', $subquestion->questiontext);
                flashcard_print_image($flashcard, $image);
                echo "<br/>";
                flashcard_play_sound($flashcard, $sound, $autoplay, false, 'bell_q');
            } else {
                echo format_text($subquestion->questiontext, FORMAT_HTML);
            }
            ?>
        </div>
        <div id="answerdiv" class="flashcard-answer frontside-default" onclick="javascript:togglecard()" style="display:none;">
            <?php
            if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
                flashcard_print_image($flashcard, $subquestion->answertext);
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND){
                flashcard_play_sound($flashcard, $subquestion->answertext, $autoplay, false, 'bell_a');
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO){
                flashcard_play_video($flashcard, $subquestion->answertext, $autoplay, false, 'bell_a');
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
                list($image, $sound) = split('@', $subquestion->answertext);
                flashcard_print_image($flashcard, $image);
                echo "<br/>";
                flashcard_play_sound($flashcard, $sound, $autoplay, false, 'bell_a');
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
	        $options['id'] = $cm->id;
	        $options['what'] = 'reset';
	        $options['view'] = 'play';
	        $options['deck'] = $deck;
	        echo $OUTPUT->single_button(new moodle_url('view.php', $options), get_string('reset', 'flashcard'), 'post');
	        ?>
	        <br/>
	        <a href="<?php echo $thisurl ?>?id=<?php echo $cm->id ?>&amp;view=checkdecks"><?php print_string('backtodecks', 'flashcard') ?></a>
	        - <a href="<?php echo $CFG->wwwroot ?>/course/view.php?id=<?php echo $course->id ?>"><?php print_string('backtocourse', 'flashcard') ?></a>
        </div>
    </div>
