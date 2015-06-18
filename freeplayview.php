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
 * This view allows free playing with a deck
 *
 * @package mod-flashcard
 * @category mod
 * @author Gustav Delius
 * @author Valery Fremaux
 */

// Security.

if (!defined('MOODLE_INTERNAL')) {
    die("Illegal direct access to this screen");
}

$subquestions = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));
if (empty($subquestions)) {
    echo $out;
    echo $OUTPUT->notification(get_string('nosubquestions', 'flashcard'));
    echo $OUTPUT->footer();
    die;
}
$subquestions = draw_rand_array($subquestions, count($subquestions));

// Print deferred header.

echo $out;

// Print summary.

if (!empty($flashcard->summary)) {
    echo $OUTPUT->box_start();
    echo format_text($flashcard->summary, $flashcard->summaryformat, NULL, $course->id);
    echo $OUTPUT->box_end();
}

?>

<script language="javascript">
//<![CDATA[
currentitem = 0;
maxitems = <?php echo count($subquestions); ?>;
remaining = maxitems;

var qtype = "<?php echo $flashcard->questionsmediatype ?>";
var atype = "<?php echo $flashcard->answersmediatype ?>";
//]]>
</script>
<script src="<?php echo $CFG->wwwroot.'/mod/flashcard/js/module.js' ?>"></script>

<p><?php print_string('freeplayinstructions', 'flashcard'); ?>.</p>

<style>
    <?php echo $flashcard->extracss ?>
</style>

<table class="flashcard_board" width="100%">
    <tr>
        <td rowspan="6">
<?php
$i = 0;

if ($flashcard->flipdeck) {
    // Flip media types once.
    $tmp = $flashcard->answersmediatype;
    $flashcard->answersmediatype = $flashcard->questionsmediatype;
    $flashcard->questionsmediatype = $tmp;
}

foreach ($subquestions as $subquestion) {
    echo '<center>';
    $divid = "f$i";
    $divstyle = ($i > 0) ? 'display:none' : '' ;
    echo '<div id="'.$divid.'" ';
    echo 'class="flashcard-question" style="'.$divstyle.';background-repeat:no-repeat;background-image:url('.$renderer->print_custom_url($flashcard, 'customback', 0).')" ';
    echo ' onclick="javascript:clicked(\'f\', \''.$i.'\')">';

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

    if ($flashcard->flipdeck) {
        // Flip card side values.
        $tmp = $subquestion->answertext;
        $subquestion->answertext = $subquestion->questiontext;
        $subquestion->questiontext = $tmp;
    }
?>
            <table width="100%" height="100%">
                <tr>
                    <td align="center" valign="center">
                        <?php
                        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
                            echo $renderer->print_image($flashcard, "{$back}imagefile/{$subquestion->id}");
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND) {
                            echo $renderer->play_sound($flashcard, "{$back}soundfile/{$subquestion->id}", 'false', false, "bell_b$i");
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO) {
                            echo $renderer->play_video($flashcard, "{$back}videofile/{$subquestion->id}", $autoplay, false, "bell_b$i");
                        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
                            echo $renderer->print_image($flashcard, "{$back}imagefile/{$subquestion->id}");
                            echo "<br/>";
                            echo $renderer->play_sound($flashcard, "{$back}soundfile/{$subquestion->id}", $autoplay, false, "bell_b$i");
                        } else {
                            echo format_text($subquestion->questiontext,FORMAT_HTML);
                        }
                        ?>
                    </td>
                </tr>
            </table>
            </div>
            </center>
            <center>
<?php
        echo "<div id=\"b{$i}\" ";
        echo 'class="flashcard-answer" style="display:none;background-repeat:no-repeat;background-image:url('.$renderer->print_custom_url($flashcard, 'customfront', 0).')" ';
        echo " onclick=\"javascript:clicked('b', '{$i}')\">";
?>
            <table width="100%" height="100%">
                <tr>
                    <td align="center" valign="center" style="">
                        <?php 
                        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
                            echo $renderer->print_image($flashcard, "{$front}imagefile/{$subquestion->id}");
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
                            echo $renderer->play_sound($flashcard, "{$front}soundfile/{$subquestion->id}", 'false', false, "bell_f$i");
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
                            echo $renderer->play_video($flashcard, "{$front}videofile/{$subquestion->id}", $autoplay, false, "bell_f$i");
                        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
                            echo $renderer->print_image($flashcard, "{$front}imagefile/{$subquestion->id}");
                            echo "<br/>";
                            echo $renderer->play_sound($flashcard, "{$front}soundfile/{$subquestion->id}", $autoplay, false, "bell_f$i");
                        } else {
                            echo format_text($subquestion->answertext,FORMAT_HTML);
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
            <input id="next" type="button" value="<?php print_string('next', 'flashcard') ?>" onclick="javascript:next_card()" />
        </td>
    </tr>
    <tr>
        <td width="200px">
            <input id="previous" type="button" value="<?php print_string('previous', 'flashcard') ?>" onclick="javascript:previous_card()" />
        </td>
    </tr>
    <tr>
        <td width="200px">
            <input id="remove" type="button" value="<?php print_string('removecard', 'flashcard') ?>" onclick="javascript:remove_card()" />
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
