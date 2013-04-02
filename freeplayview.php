<?php

    /** 
    * This view allows free playing with a deck
    * 
    * @package mod-flashcard
    * @category mod
    * @author Gustav Delius
    * @contributors Valery Fremaux
    */
    
    require_js($CFG->wwwroot.'/mod/flashcard/js/flashcard.js');


    // Security
    if (!defined('MOODLE_INTERNAL')){
        die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
    }

    $subquestions = get_records('flashcard_deckdata', 'flashcardid', $flashcard->id);
    if (empty($subquestions)) {
        notice(get_string('nosubquestions', 'flashcard'));
        return;          
    }
    $subquestions = draw_rand_array($subquestions, count($subquestions));

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

<script language="javascript">
//<![CDATA[

// passing vars to javascript

var currentitem = 0;
maxitems = <?php echo count($subquestions); ?>;
remaining = maxitems;

var qtype = "<?php echo $flashcard->questionsmediatype ?>";
var atype = "<?php echo $flashcard->answersmediatype ?>";
var oldtype = 'f';

var cards = new Array(maxitems);
for(i = 0 ; i < maxitems ; i++){
    cards[i] = true;
}

//]]>
</script>

<p>
<?php print_string('freeplayinstructions', 'flashcard'); ?>.
</p>
<table class="flashcard_board" width="100%">
    <tr>
        <td rowspan="6">
<?php
$i = 0;

if ($flashcard->flipdeck){
    // flip media types once
    $tmp = $flashcard->answersmediatype;
    $flashcard->answersmediatype = $flashcard->questionsmediatype;
    $flashcard->questionsmediatype = $tmp;
}

foreach($subquestions as $subquestion){
    if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND || 
            $flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND || 
                $flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND ||
                    $flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
        $questiononclickhandler = '';
        $answeronclickhandler = '';
    } else {
        $questiononclickhandler = "javascript:clicked('f', '{$i}')";
        $answeronclickhandler = "javascript:clicked('b', '{$i}')";
    }

    echo '<center>';
    $divid = "f$i";
    $divstyle = ($i > 0) ? 'display:none' : 'display:block' ;
	$divstyle .= ";background:url('{$backpixpath}') no-repeat";
    echo "<div id=\"{$divid}\" style=\"{$divstyle}\" class=\"backside\"";
    echo " onclick=\"$questiononclickhandler\">";
    
    if ($flashcard->flipdeck){
        // flip card side values
        $tmp = $subquestion->answertext;
        $subquestion->answertext = $subquestion->questiontext;
        $subquestion->questiontext = $tmp;
    }
?>
            <table class="flashcard_question" width="100%" height="100%">
                <tr>
                    <td align="center" valign="center">
                        <?php
                        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
                            flashcard_print_image($flashcard, $subquestion->questiontext);
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND){                            
                            flashcard_play_sound($flashcard, $subquestion->questiontext, 'false', false, "bell_f$i");
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){                            
                            list($image, $sound) = split('@', $subquestion->questiontext);
                            flashcard_print_image($flashcard, $image);
                            echo "<br/>";
                            flashcard_play_sound($flashcard, $sound, 'false', false, "bell_f$i");
                        } else {
                            echo format_string($subquestion->questiontext);
                        }
                        ?>
                    </td>
                </tr>
            </table>
            </div>
            </center>
            <center>
<?php
        echo "<div id=\"b{$i}\" style=\"display: none;background:url('{$frontpixpath}') no-repeat\" class=\"frontside\"";
        echo " onclick=\"$answeronclickhandler\">";
?>
    		<table class="flashcard_answer" width="100%" height="100%">
    		    <tr>
    		        <td align="center" valign="center" style="">
    		            <?php 
                        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
                            flashcard_print_image($flashcard, $subquestion->answertext);
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND){                          
                            flashcard_play_sound($flashcard, $subquestion->answertext, 'false', false, "bell_b$i");
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){                            
                            list($image, $sound) = split('@', $subquestion->answertext);
                            flashcard_print_image($flashcard, $image);
                            echo "<br/>";
                            flashcard_play_sound($flashcard, $sound, 'false', false, "bell_b$i");
                        } else {
                            echo format_string($subquestion->answertext);
                        }
                        ?>
    		        </td>
    		    </tr>
    		</table>
    		</div>
    		</center>
<?php
    $i++;
}
?>
            <center>

            <?php 
            if (empty($questiononclickhandler)){
                $togglestr = get_string('togglelink', 'flashcard');
                echo "<p><a href=\"Javascript:freetogglecard();\">$togglestr</a></p>";
            } 
            ?>

            <div id="finished" style="display: none;" class="finished">
            <table width="100%" height="100%">
                <tr>
                    <td align="center" valign="middle" class="emptyset">
                        <?php print_string('emptyset', 'flashcard'); ?>
                    </td>
                </tr>
            </table>
            </div>
            </center>
    
        </td>
    </tr>
    <tr>
        <td width="200px">
            <p><?php print_string('cardsremaining', 'flashcard'); ?>: <span id="remain"><?php echo count($subquestions);?></span></p>
        </td>
    </tr>
    <tr>
        <td width="200px">
            <input id="next" type="button" value="<?php print_string('next', 'flashcard') ?>" onclick="javascript:next()" />
        </td>
    </tr>
    <tr>
        <td width="200px">
            <input id="previous" type="button" value="<?php print_string('previous', 'flashcard') ?>" onclick="javascript:previous()" />
        </td>
    </tr>
    <tr>
        <td width="200px">
            <input id="remove" type="button" value="<?php print_string('removecard', 'flashcard') ?>" onclick="javascript:remove()" />
        </td>
    </tr>
    <tr>
        <td width="200px">
            <input type="button" value="<?php print_string('reset', 'flashcard') ?>" onclick="javascript:location.reload()" />
        </td>
    </tr>
    <tr>
        <td width="200px" align="center" colspan="2">
            <br/><a href="<?php echo $CFG->wwwroot ?>/course/view.php?id=<?php echo $course->id ?>"><?php print_string('backtocourse', 'flashcard') ?></a>
        </td>
      </tr>
</table>
