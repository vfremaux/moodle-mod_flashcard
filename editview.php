<?php
// This file is part of the learningtimecheck plugin for Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

/**
 * This view provides a way for editing questions
 *
 * @package mod_flashcard
 * @category mod
 * @author Gustav Delius
 * @contributors Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/mod/flashcard/editview_form.php');

$usercontext = context_user::instance($USER->id);

/* @var $OUTPUT core_renderer */

if ($cardid = optional_param('cardid', 0, PARAM_INT)) {
    $card = $DB->get_record('flashcard_deckdata', array('id' => $cardid));
}
$mform = new CardEdit_Form($CFG->wwwroot.'/mod/flashcard/view.php?view=edit', array('flashcard' => $flashcard, 'cmid' => $cm->id, 'cmd' => $action, 'cardid' => $cardid));

// Print deferred header.

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/flashcard/view.php', array('id' => $id, 'view' => 'manage')));
}

if ($data = $mform->get_data()) {

    $validqkeys = array();

    // Add or update cards.

    if ($data->what == 'update') {
        $akeys[] = 'a0';
        $validqkeys['q0'] = $DB->get_record('flashcard_deckdata', array('id' => $data->cardid));
    } else {

        // Prepare all new cards we need.

        $keys = array_keys((array)$data);    // Get the key value of all the fields submitted.

        $qkeys = preg_grep('/^q/', $keys);   // Filter out only the status.
        $akeys = preg_grep('/^a/', $keys);   // Filter out only the assigned updating.

        $deckusers = $DB->get_records('flashcard_card', array('flashcardid' => $flashcard->id), 'id', 'DISTINCT userid, id');

        foreach ($qkeys as $qkey) {

            // For new cards : get a new record, insert it and use it for update.
            if (preg_match('/^qs/', $qkey)) {
                continue; // sounds will be processed at same time than images
            }

            if (preg_match('/qi?/', $qkey)) {

                // if empty question, do not add
                if ($_REQUEST[$qkey] == '') {
                    continue;
                }

                // do NOT try to add unfilled cards
                // ugly hack to get back some data lost in form bounce...
                $data->$qkey = $_REQUEST[$qkey];
                $akey = preg_replace('/^q/', 'a', $qkey);
                $data->$akey = $_REQUEST[$akey];

                $card = new StdClass;
                $card->flashcardid = $flashcard->id;
                $card->questiontext = ''; // empty field values will be filled later
                $card->answertext = ''; // empty field values will be filled later
                $card->id = $DB->insert_record('flashcard_deckdata', $card); // pre save the card record

                $validqkeys[$qkey] = $card; // validate the input param and store card

                // Add card to all student decks and reset state of deck 1 for all
                if ($deckusers) {
                    foreach (array_keys($deckusers) as $duid) {
                        $usercard = new StdClass();
                        $usercard->flashcardid = $flashcard->id;
                        $usercard->userid = $duid;
                        $usercard->deck = 1;
                        $usercard->entryid = $card->id;
                        $usercard->lastaccessed = 0;
                        $usercard->accesscount = 0;
                        $DB->insert_record('flashcard_card', $usercard);

                        $DB->set_field('flashcard_userdeck_state', 'state', 0, array('flashcardid' => $flashcard->id, 'userid' => $duid));
                    }
                }
            }
        }
    }

    // process all validated new cards
    foreach ($validqkeys as $qkey => $card) {

        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_TEXT) {
            $card->questiontext = required_param($qkey, PARAM_CLEANHTML);
        } else {
            $fs = get_file_storage();

            if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
                $filepickeritemid = required_param($qkey, PARAM_INT);
                $card->questiontext = '';
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionimagefile', $card->id);
                    $savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id);
                    $savedfile = array_pop($savedfiles);
                    // $card->questiontext = $savedfile->id;
                }
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND) {
                $filepickeritemid = required_param($qkey, PARAM_INT);
                $card->questiontext = '';
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)) {
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
                    $savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
                    $savedfile = array_pop($savedfiles);
                    // $card->questiontext = $savedfile->get_id();
                }
            } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO) {
                $filepickeritemid = required_param($qkey, PARAM_INT);
                $card->questiontext = '';
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)) {
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionvideofile', $card->id);
                    $savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionvideofile', $card->id, '', false);
                    $savedfile = array_pop($savedfiles);
                    // $card->questiontext = $savedfile->get_id();
                }
            } else {
                // combine image and sound in one single field
                $filepickeritemid = required_param($qkey, PARAM_INT);
                $imagesavedid = '';
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)) {
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionimagefile', $card->id);
                    $imagesavedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id, '', false);
                    $imagesavedfile = array_pop($imagesavedfiles);
                    // $imagesavedid = $imagesavedfile->get_id();
                }
                $soundsavedid = '';
                $filepickeritemid = required_param(preg_replace('/^qi/', 'qs', $qkey), PARAM_INT);
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)) {
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
                    $soundsavedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id, '', false);
                    $soundsavedfile = array_pop($soundsavedfiles);
                    // $soundsavedid = $soundsavedfile->get_id();
                }
                // $card->questiontext = $imagesavedid.'@'.$soundsavedid;
            }
        }

        $akey = preg_replace('/^q/', 'a', $qkey);

        // Get answer side related files.
        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_TEXT) {
            $card->answertext = required_param($akey, PARAM_CLEANHTML);
        } else {
            if (empty($fs)) $fs = get_file_storage();

            if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
                $filepickeritemid = required_param($akey, PARAM_INT);
                file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answerimagefile', $card->id);
                // $savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'answerimagefile', $card->id, '', false);
                // there should be only one
                // $savedfile = array_pop($savedfiles);
                // $card->answertext = $savedfile->get_id();
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
                $filepickeritemid = required_param($akey, PARAM_INT);
                file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answersoundfile', $card->id);
                // $savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'answersoundfile', $card->id, '', false);
                // there should be only one
                // $savedfile = array_pop($savedfiles);
                // $card->answertext = $savedfile->get_id();
            } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
                $filepickeritemid = required_param($akey, PARAM_INT);
                file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answervideofile', $card->id);
                // $savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'answervideofile', $card->id, '', false);
                // there should be only one
                // $savedfile = array_pop($savedfiles);
                // $card->answertext = $savedfile->get_id();
            } else {
                // Combine image and sound in one single field.
                $imagesavedid = '';
                $filepickeritemid = required_param($akey, PARAM_CLEANHTML);
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answerimagefile', $card->id);
                    // $imagesavedfiles = $fs->get_area_file($context->id, 'mod_flashcard', 'answerimagefile', $card->id, '', false);
                    // $imagesavedfile = array_pop($imagesavedfiles);
                    // $imagesavedid = $imagesavedfile->get_id();
                }

                // $soundsavedid = '';
                $filepickeritemid = required_param(preg_replace('/^ai/', 'as', $akey), PARAM_CLEANHTML);
                if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)) {
                    file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answersoundfile', $card->id);
                    // $soundsavedfiles = $fs->get_area_file($context->id, 'mod_flashcard', 'answersoundfile', $card->id, '', false);
                    // $soundsavedfile = array_pop($soundsavedfiles);
                    // $soundsavedid = $soundsavedfile->get_id();
                }
                // $card->answertext = $imagesavedid.'@'.$soundsavedid;
            }
        }
        if (!$DB->update_record('flashcard_deckdata', $card)) {
            print_error('errorupdatecard', 'flashcard');
        }
    }

    /*
    echo $out;
    echo $OUTPUT->continue_button($CFG->wwwroot.'/mod/flashcard/view.php?id='.$id.'&view=manage');
    echo $OUTPUT->footer();
    die;
    */
    redirect(new moodle_url('/mod/flashcard/view.php', array('id' => $id, 'view' => 'manage')));
}

echo $out;

// if cardid, load card into form
if ($cardid) {
    $card->cardid = $card->id;
    $card->id = $cm->id;
    $mform->set_data($card);
} else {
    $data = new StdClass;
    $data->id = $cm->id;
    $mform->set_data($data);
}
echo '<div id="flashcard-card-form">';
$mform->display();
echo '</div>';

