<?php

    /** 
    * This view provides a way for editing questions
    * 
    * @package mod-flashcard
    * @category mod
    * @author Gustav Delius
    * @contributors Valery Fremaux
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

	require_once 'renderers.php';

    /* @var $OUTPUT core_renderer */

    if (!defined('MOODLE_INTERNAL')){
        die("Illegal direct access to this screen");
    }

    if ($action != ''){
        $result = include "{$CFG->dirroot}/mod/flashcard/editview.controller.php";
    }
    
    $cards = $DB->get_records('flashcard_deckdata', array('flashcardid'=> $flashcard->id), 'id');

	$fs = get_file_storage();
    
    $strquestionnum = get_string('num', 'flashcard');
    $strquestion = get_string('question', 'flashcard');
    $stranswer = get_string('answer', 'flashcard');
    $strcommands = get_string('commands', 'flashcard');

    $table = new html_table();
    $table->head = array('', "<b>$strquestionnum</b>", "<b>$strquestion</b>", "<b>$stranswer</b>", "<b>$strcommands</b>");
    $table->size = array('1%', '10%', '40%', '40%', '9%');
    $table->width = '100%';
    $i = 1;
    if ($cards){
        $strselect = get_string('choose');
        foreach($cards as $card){
            $checkbox = "<input type=\"checkbox\" name=\"items[]\" value=\"{$card->id}\" />";
            if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE){
            	$currentfile = ($card->questiontext) ? $fs->get_file_by_id($card->questiontext) : null ;
                $questioninput = flashcard_filepicker("q{$card->id}", 1000000, array('image'), $currentfile);
                $questioninput .= "<br/>";
                $questioninput .= flashcard_print_image($flashcard, $card->questiontext, true);
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND){
            	$currentfile = ($card->questiontext) ? $fs->get_file_by_id($card->questiontext) : null ;
                // $questioninput = "<input type=\"text\" name=\"q{$card->id}\" value=\"{$text}\" style=\"width: 300px\" />";
                $questioninput = flashcard_filepicker("q{$card->id}", 1000000, array('.mp3', '.swf'), $currentfile);
                $questioninput .= "<br/>";
                $questioninput .= flashcard_play_sound($flashcard, $card->questiontext, 'false', true);
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO){
            	$currentfile = ($card->questiontext) ? $fs->get_file_by_id($card->questiontext) : null ;
                // $questioninput = "<input type=\"text\" name=\"q{$card->id}\" value=\"{$text}\" style=\"width: 300px\" />";
                $questioninput = flashcard_filepicker("q{$card->id}", 10000000, array('video'), $currentfile);
                $questioninput .= "<br/>";
                $questioninput .= flashcard_play_video($flashcard, $card->questiontext, 'false', true);
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
            	list($imagefileid, $soundfileid) = explode('@', $card->questiontext);
            	$imagecurrentfile = ($imagefileid) ? $fs->get_file_by_id($imagefileid) : null ;
                $questioninput = flashcard_filepicker("qi{$card->id}", 1000000, array('image'), $imagecurrentfile);
                $questioninput .= "<br/>";
                $questioninput .= flashcard_print_image($flashcard, $imagefileid, $card->id, true);
            	$soundcurrentfile = ($soundfileid) ? $fs->get_file_by_id($soundfileid) : null ;
                $questioninput = flashcard_filepicker("qs{$card->id}", 1000000, array('.mp3', '.swf'), $soundcurrentfile);
                $questioninput .= "<br/>";
                $questioninput .= flashcard_play_sound($flashcard, $soundfileid, 'false', true);
            } else {
            	$text = htmlentities($card->questiontext, ENT_NOQUOTES, 'utf-8');
                $questioninput = "<textarea name=\"q{$card->id}\" style=\"width: 100%\" rows=\"3\">{$text}</textarea>";
            }
            
            // make answer part of form
            if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE){
            	$currentfile = ($card->answertext) ? $fs->get_file_by_id($card->answertext) : null ;
                $questioninput = flashcard_filepicker("a{$card->id}", 1000000, array('image'), $currentfile);
                $answerinput .= "<br/>";
                $answerinput .= flashcard_print_image($flashcard, $card->answertext, true);
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND){
            	$currentfile = ($card->answertext) ? $fs->get_file_by_id($card->answertext) : null ;
                $questioninput = flashcard_filepicker("a{$card->id}", 1000000, array('.mp3', '.swf'), $currentfile);
                $answerinput .= "<br/>";
                $answerinput .= flashcard_play_sound($flashcard, $card->answertext, 'false', true);
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO){
            	$currentfile = ($card->answertext) ? $fs->get_file_by_id($card->answertext) : null ;
                $questioninput = flashcard_filepicker("a{$card->id}", 10000000, array('video'), $currentfile);
                $answerinput .= "<br/>";
                $answerinput .= flashcard_play_video($flashcard, $card->answertext, 'false', true);
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
            	list($imagefileid, $soundfileid) = explode('@', $card->answertext);
            	$imagecurrentfile = ($imagefileid) ? $fs->get_file_by_id($imagefileid) : null ;
                $questioninput = flashcard_filepicker("ai{$card->id}", 1000000, array('image'), $imagecurrentfile);
                $answerinput .= "<br/>";
                $answerinput .= flashcard_print_image($flashcard, $imagecurrentfile, true);
            	$soundcurrentfile = ($soundfileid) ? $fs->get_file_by_id($soundfileid) : null ;
                $questioninput = flashcard_filepicker("as{$card->id}", 1000000, array('.mp3', '.swf'), $soundcurrentfile);
                $answerinput .= "<br/>";
                $answerinput .= flashcard_play_sound($flashcard, $sound, 'false', true);
                
            } else {
            	$text = htmlentities($card->answertext, ENT_NOQUOTES, 'utf-8');
                $answerinput = "<textarea name=\"a{$card->id}\" style=\"width: 100%\" rows=\"3\">{$text}</textarea>";
            }
            $commands = "<a href=\"view.php?id={$cm->id}&amp;what=delete&amp;items={$card->id}&amp;view=edit\"><img src=\"".$OUTPUT->pix_url('delete','flashcard')."\" /></a>";
            $table->data[] = array($checkbox, $i, $questioninput, $answerinput, $commands);
            $i++;
        }
    }
?>
<center>
<div style="width: 90%">
<form name="editcard" method="POST" action="view.php">
<input type="hidden" name="what" value="save" />
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="view" value="edit" />
<?php    
if (!empty($cards)){
    echo html_writer::table($table);
?>
</center>
<p><a href="Javascript:document.forms['editcard'].what.value = 'delete' ; document.forms['editcard'].submit()"><?php print_string('deleteselection', 'flashcard') ?></a></p>
<?php
} else {
    echo $OUTPUT->box(get_string('nocards', 'flashcard'));
}
?>
</div>
</form>

<center>
<form name="adddata" method="GET" action="view.php">
<input type="hidden" name="what" value="add" />
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="view" value="edit" />
<input type="hidden" name="add" value="" />
<!-- not in this form, but for display it is better here -->
<?php
if (!empty($cards)){
?>
<input type="button" name="add_btn" value="<?php print_string('update') ?>" onclick="document.forms['editcard'].submit()" />
<?php
}
?>
<input type="button" name="add_btn" value="<?php print_string('addone', 'flashcard') ?>" onclick="document.forms['adddata'].add.value = 1 ; document.forms['adddata'].submit()" />&nbsp;
<input type="button" name="add_btn" value="<?php print_string('addthree', 'flashcard') ?>" onclick="document.forms['adddata'].add.value = 3 ; document.forms['adddata'].submit()" />
</form>
</center>
