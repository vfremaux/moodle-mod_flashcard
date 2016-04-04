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


if (!defined('MOODLE_INTERNAL')) die ('You cannot use this script this way');

if ($action) {
    include($CFG->dirroot.'/mod/flashcard/manageview.controller.php');
}

$pagesize = 20;
$allcards = $DB->count_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));

$page = optional_param('page', 0, PARAM_INT);
$from = $page * $pagesize;

$PAGE->requires->js('/mod/flashcard/players/flowplayer/flowplayer.js');

echo $out;

echo '<link href="'.$CFG->wwwroot.'/mod/flashcard/players/flowplayer/skin/minimalist.css" rel="stylesheet" type="text/css" />';

$cards = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id), 'id', '*', $from, $pagesize);

$backstr = get_string('backside', 'flashcard');
$frontstr = get_string('frontside', 'flashcard');

$table = new html_table();
$table->head = array('', "<b>$backstr</b>", "<b>$frontstr</b>", '');
$table->size = array('10%', '40%', '40%', '10%');
$table->width = '100%';
$table->align = array('center', 'center', 'center', 'center');

$editurl = $CFG->wwwroot.'/mod/flashcard/view.php?id='.$id.'&view=edit';

$i = 0;
if ($cards) {
    foreach ($cards as $card) {
        $check = "<input type=\"checkbox\" name=\"items[]\" value=\"{$card->id}\" />";

        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
            $back = $renderer->print_image($flashcard, "questionimagefile/{$card->id}", true);
        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND) {
            $back = $renderer->play_sound($flashcard, "questionsoundfile/{$card->id}", 'false', true, "bell_b$i");
        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO) {
            $back = $renderer->play_video($flashcard, "questionvideofile/{$card->id}", 'false', true, "bell_b$i", true);
        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
            $back = $renderer->print_image($flashcard, "questionimagefile/{$card->id}", true);
            $back .= "<br/>";
            $back = $renderer->play_sound($flashcard, "questionsoundfile/{$card->id}", 'false', true, "bell_b$i");
        } else {
            $back = format_text($card->questiontext, FORMAT_MOODLE);
        }

        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
            $front = $renderer->print_image($flashcard, "answerimagefile/{$card->id}", true);
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
            $front = $renderer->play_sound($flashcard, "answersoundfile/{$card->id}", 'false', true, "bell_f$i");
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
            $front = $renderer->play_video($flashcard, "answervideofile/{$card->id}", 'false', true, "bell_f$i", true);
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
            $front = $renderer->print_image($flashcard, "answerimagefile/{$card->id}", true);
            $front .= "<br/>";
            $front = $renderer->play_sound($flashcard, "answersoundfile/{$card->id}", 'false', true, "bell_f$i");
        } else {
            $front = format_text($card->answertext, FORMAT_MOODLE);
        }

        $command = "<a href=\"{$editurl}&what=update&cardid={$card->id}\"><img src=\"".$OUTPUT->pix_url('t/edit').'" /></a>';
        $command .= " <a href=\"{$url}?id={$id}&view=manage&what=delete&items[]={$card->id}\"><img src=\"".$OUTPUT->pix_url('t/delete').'" /></a>';
        $table->data[] = array($check, $back, $front, $command);
        $i++;
    }

    echo '<center>';
    echo $OUTPUT->paging_bar($allcards, $page, $pagesize, $url.'?id='.$id.'&view=manage', 'page');
    echo '</center>';
    echo '<form name="deletecards" action="'.$url.'" method="get">';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="view" value="manage" />';
    echo '<input type="hidden" name="what" value="delete" />';
    echo '<input type="hidden" name="id" value="'.$id.'" />';
    echo html_writer::table($table);
    echo '</form>';
    echo '<center>';
    echo $OUTPUT->paging_bar($allcards, $page, $pagesize, $url.'?id='.$id.'&view=manage', 'page');
    echo '</center>';
} else {
    echo $OUTPUT->box(get_string('nocards', 'flashcard'));
    echo '<br/>';
}

$addone = get_string('addone', 'flashcard');
$addthree = get_string('addthree', 'flashcard');
$deleteselectionstr = get_string('deleteselection', 'flashcard');
$sesskey = sesskey();
echo '<div class=\"rightlinks\">';
if ($cards) {
    echo "<a href=\"javascript:document.forms['deletecards'].submit();\">$deleteselectionstr</a> - ";
}
echo "<a href=\"{$editurl}&what=addone&sesskey={$sesskey}\">$addone</a> - <a href=\"{$editurl}&what=addthree&sesskey={$sesskey}\">$addthree</a></div>";
