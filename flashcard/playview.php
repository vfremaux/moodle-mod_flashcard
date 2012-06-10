<?php

    /** 
    * This view allows playing with a deck
    * 
    * @package mod-flashcard
    * @category mod
    * @author Gustav Delius
    * @contributors Valery Fremaux
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */
    
    require_js($CFG->wwwroot.'/mod/flashcard/js/flashcard.js');

    // Security
    if (!defined('MOODLE_INTERNAL')){
        error("Illegal direct access to this screen");
    }

    // we need it in controller
    $deck = required_param('deck', PARAM_INT);

    if ($action != ''){
        include $CFG->dirroot.'/mod/flashcard/playview.controller.php';
    }

    $subquestions = get_records('flashcard_deckdata', 'flashcardid', $flashcard->id);
    if (empty($subquestions)) {
        print_box_start();
        echo print_string('undefinedquestionset', 'flashcard');
        print_box_end();
        print_footer($course);
        return;
    }

    $consumed = @$_SESSION['flashcard_consumed'];
    $consumed = str_replace(',', "','", $consumed);
    $subquestions = array();
    $select = "
        flashcardid = {$flashcard->id} AND 
        userid = {$USER->id} AND 
        deck = {$deck} AND 
        id NOT IN ('$consumed')
    ";
	if ($cards = get_records_select('flashcard_card', $select)){
    	foreach($cards as $card){
    	    $obj = new stdClass();
    	    $obj->entryid = $card->entryid;
    	    $obj->cardid = $card->id;
    	    $subquestions[] = clone($obj);
    	}
    } else {
        notice(get_string('nomorecards', 'flashcard'), "view.php?view=checkdecks&amp;id={$cm->id}");
        redirect("view.php?view=checkdecks&amp;id={$cm->id}");
    }
    
/// randomize and get a question (obviously it is not a consumed question).
    
    $random = rand(0, count($subquestions) - 1);
    $subquestion = get_record('flashcard_deckdata', 'id', $subquestions[$random]->entryid);

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

/// compute image paths

	// standard location in module
	$backpixpath = 'pix/back.jpg';
	$frontpixpath = 'pix/front.jpg';

	// customized override
	if (file_exists($CFG->dataroot."/{$course->id}/moddata/flashcard/{$flashcard->id}/pix/back.jpg")){
		$backpixpath = $CFG->wwwroot."/file.php?file=".urlencode("/{$course->id}/moddata/flashcard/{$flashcard->id}/pix/back.jpg");
	}
	if (file_exists($CFG->dataroot."/{$course->id}/moddata/flashcard/{$flashcard->id}/pix/front.jpg")){
		$frontpixpath = $CFG->wwwroot."/file.php?file=".urlencode("/{$course->id}/moddata/flashcard/{$flashcard->id}/pix/front.jpg");
	}

?>
<script type="text/javascript">
//<![CDATA[

// passing vars to javascript

var qtype = "<?php echo $flashcard->questionsmediatype ?>";
var atype = "<?php echo $flashcard->answersmediatype ?>";
//]]>
</script>


<p>
<?php 
print_heading($flashcard->name); 
print_string('instructions', 'flashcard'); 

if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND || 
        $flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND || 
            $flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND ||
                $flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
    $onclickhandler = '';
} else {
    $onclickhandler = "javascript:togglecard()";
}

?>
</p>
<table class="flashcard_board" width="100%">
    <tr>
        <td rowspan="5">
        <center>

            <div id="questiondiv" style="display: block;background:url('<?php echo $backpixpath ?>') no-repeat" class="backside" onclick="<?php echo $onclickhandler ?>">
            <table class="flashcard_question" width="100%" height="100%">
                <tr>
                    <td align="center" valign="center">
                        <?php
                        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
                            flashcard_print_image($flashcard, $subquestion->questiontext);
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND){                            
                            flashcard_play_sound($flashcard, $subquestion->questiontext, 'false', false, 'bell_q');
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){                            
                            list($image, $sound) = split('@', $subquestion->questiontext);
                            flashcard_print_image($flashcard, $image);
                            echo "<br/>";
                            flashcard_play_sound($flashcard, $sound, 'false', false, 'bell_q');
                        } else {
                            echo format_string($subquestion->questiontext);
                        }
                        ?>
                    </td>
                </tr>
            </table>
            </div>

            <div id="answerdiv" style="display: none;background:url('<?php echo $frontpixpath ?>') no-repeat" class="frontside" onclick="<?php echo $onclickhandler ?>">
    		<table class="flashcard_answer" width="100%" height="100%">
    		    <tr>
    		        <td align="center" valign="center">
    		            <?php 
                        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
                            flashcard_print_image($flashcard, $subquestion->answertext);
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND){                            
                            flashcard_play_sound($flashcard, $subquestion->answertext, 'false', false, 'bell_a');
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){                            
                            list($image, $sound) = split('@', $subquestion->answertext);
                            flashcard_print_image($flashcard, $image);
                            echo "<br/>";
                            flashcard_play_sound($flashcard, $sound, 'false', false, 'bell_a');
                        } else {
                            echo format_string($subquestion->answertext);
                        }
                        ?>
    		        </td>
    		    </tr>
    		</table>
    		</div>

            <?php 
            if (empty($onclickhandler)){
                $togglestr = get_string('togglelink', 'flashcard');
                echo "<p><a href=\"Javascript:togglecard();\">$togglestr</a></p>";
            } ?>
    		</center>    
        </td>
    </tr>
    <tr>
        <td width="200px">
            <p><?php print_string('cardsremaining', 'flashcard'); ?>: <span id="remain"><?php echo count($subquestions);?></span></p>
        </td>
    </tr>
    <tr>
        <td>
            <?php 
            $options['id'] = $cm->id;
            $options['what'] = 'igotit';
            $options['view'] = 'play';
            $options['deck'] = $deck;
            $options['cardid'] = $subquestions[$random]->cardid;
            print_single_button('view.php', $options, get_string('igotit', 'flashcard')); 
            ?>
        </td>
    </tr>
    <tr>
        <td>
            <?php 
            $options['id'] = $cm->id;
            $options['what'] = 'ifailed';
            $options['view'] = 'play';
            $options['deck'] = $deck;
            $options['cardid'] = $subquestions[$random]->cardid;
            print_single_button('view.php', $options, get_string('ifailed', 'flashcard')); 
            ?>
        </td>
    </tr>
    <tr>
        <td>
            <?php 
            $options['id'] = $cm->id;
            $options['what'] = 'reset';
            $options['view'] = 'play';
            $options['deck'] = $deck;
            print_single_button('view.php', $options, get_string('reset', 'flashcard')); 
            ?>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="2">
            <br/><a href="<?php echo $CFG->wwwroot ?>/mod/flashcard/view.php?id=<?php echo $cm->id ?>&amp;view=checkdecks"><?php print_string('backtodecks', 'flashcard') ?></a>
            - <a href="<?php echo $CFG->wwwroot ?>/course/view.php?id=<?php echo $course->id ?>"><?php print_string('backtocourse', 'flashcard') ?></a>
        </td>
      </tr>
</table>
