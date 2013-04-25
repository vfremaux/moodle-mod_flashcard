<?php

/**
 * a controller for the play view
 * 
 * @package mod-flashcard
 * @category mod
 * @author Valery Fremaux
 * @author Tomasz Muras
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * @usecase add
 * @usecase delete
 * @usecase save
 * @usecase import
 * @usecase doimport
 */
/* @var $OUTPUT core_renderer */

if (!defined('MOODLE_INTERNAL')) {
    die("Illegal direct access to this screen");
}

/* * ****************************** Add new blank fields **************************** */
if ($action == 'add') {
    $add = required_param('add', PARAM_INT);
    $card->flashcardid = $flashcard->id;
    $users = $DB->get_records_menu('flashcard_card', array('flashcardid' => $flashcard->id), '', 'DISTINCT userid, id');
    for ($i = 0; $i < $add; $i++) {
        if (!$newcardid = $DB->insert_record('flashcard_deckdata', $card)) {
            print_error('erroraddcard', 'flashcard');
        }
        if ($users) {
            foreach (array_keys($users) as $userid) {
                $deckcard->flashcardid = $flashcard->id;
                $deckcard->entryid = $newcardid;
                $deckcard->userid = $userid;
                $deckcard->lastaccessed = 0;
                $deckcard->deck = 1;
                $deckcard->accesscount = 0;
                if (!$DB->insert_record('flashcard_card', $deckcard)) {
                    print_error('errorbindcard', 'flashcard', '', $userid);
                }
            }
        }
    }
}
/* * ****************************** Delete a set of records **************************** */
if (isset($data->deletesel)){
	$keys = array_keys((array)$data);
	$keyeditems = preg_grep('/^items_/', $keys);
	foreach($keyeditem as $it){
		if ($data->$it){
			$items[] = str_replace('items_', '', $it);
		}
	}
	$action = 'delete';
}

if ($action == 'delete') {
	if (!isset($items))
    	$items = required_param_array('items', PARAM_INT);
    
    foreach($items as $item){
    
    	$card = $DB->get_record('flashcard_deckdata', array('id' => $item));

    	flashcard_delete_attached_files($card);
    	
	    if (!$DB->delete_records('flashcard_deckdata', array('id' => $item))) {
	        print_error('errordeletecard', 'flashcard');
	    }

	    if (!$DB->delete_records_select('flashcard_card', array('entryid' => $item))) {
	        print_error('errordeletecard', 'flashcard');
	    }
	}
}
/* ******************************* Save and update all questions **************************** */
if (($action == 'update') && $data) {
		
    $keys = array_keys((array)$data);    // get the key value of all the fields submitted

    $qkeys = preg_grep('/^q/', $keys);   // filter out only the status
    $akeys = preg_grep('/^a/', $keys);   // filter out only the assigned updating
    
    $usercontext = context_user::instance($USER->id);
    
    foreach ($qkeys as $qkey) {
    	
    	if (strstr('qs', $qkey) === 0) continue; // avoid processing 'qs' entries twice. Processed with 'qi'

		// for new cards : get a new record, insert it and use it for udate    	
    	if (preg_match('/qi?_new_/', $qkey)){
    		$card = new StdClass;
    		$card->flashcardid = $flashcard->id;
    		$card->questiontext = '';
    		$card->answertext = '';
    		$card->id = $DB->insert_record('flashcard_deckdata', $card);
    	} else {
	        preg_match("/[qi](\d+)/", $qkey, $matches);
	        $card->id = $matches[1];
	        $card->flashcardid = $flashcard->id;
	    }
        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_TEXT) {
            $card->questiontext = required_param($qkey, PARAM_CLEANHTML);
        } else{
    		$fs = get_file_storage();

        	if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
	            $filepickeritemid = required_param($qkey, PARAM_INT);
	            $card->questiontext = '';
				if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionimagefile', $card->id);
					$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id);
					$savedfile = array_pop($savedfiles);
		        	$card->questiontext = $savedfile->id;
				}
	        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND) {
	            $filepickeritemid = required_param($qkey, PARAM_INT);
	            $card->questiontext = '';
				if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
					$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
					$savedfile = array_pop($savedfiles);
		        	$card->questiontext = $savedfile->get_id();
		        }
	        } elseif ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO) {
	            $filepickeritemid = required_param($qkey, PARAM_INT);
	            $card->questiontext = '';
				if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionvideofile', $card->id);
					$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionvideofile', $card->id);
					$savedfile = array_pop($savedfiles);
		        	$card->questiontext = $savedfile->get_id();
		        }
	        } else {
	            // combine image and sound in one single field
	            $filepickeritemid = required_param($qkey, PARAM_INT);
	            $imagesavedid = '';
				if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionimagefile', $card->id);
					$imagesavedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id);
					$imagesavedfile = array_pop($imagesavedfiles);
					$imagesavedid = $imagesavedfile->get_id();
				}
	            $soundsavedid = '';
	            $filepickeritemid = required_param(preg_replace('/^qi/', 'qs', $qkey), PARAM_INT);
				if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
					$soundsavedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id);
					$soundsavedfile = array_pop($soundsavedfiles);
					$soundsavedid = $soundsavedfile->get_id();
				}
	        	$card->questiontext = $imagesavedid.'@'.$soundsavedid;
	        }
	    }
	    
	    $akey = preg_replace('/^q/', 'a', $qkey);

		// get answer side related files
		if ($flashcard->answersmediatype == FLASHCARD_MEDIA_TEXT) {
            $card->answertext = required_param($akey, PARAM_CLEANHTML);
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
            $filepickeritemid = required_param($akey, PARAM_INT);
			file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answerimagefile', $card->id);
			$savedfile = $fs->get_file($context->id, 'mod_flashcard', 'answerimagefile', $card->id);
        	$card->answertext = $savedfile->id;
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
            $filepickeritemid = required_param($akey, PARAM_INT);
			file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answersoundfile', $card->id);
			$savedfile = $fs->get_file($context->id, 'mod_flashcard', 'answersoundfile', $card->id);
        	$card->answertext = $savedfile->id;
        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
            $filepickeritemid = required_param($akey, PARAM_INT);
			file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answervideofile', $card->id);
			$savedfile = $fs->get_file($context->id, 'mod_flashcard', 'answervideofile', $card->id);
        	$card->answertext = $savedfile->id;
        } else {
            // combine image and sound in one single field
            $filepickeritemid = required_param($akey, PARAM_CLEANHTML);
			file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answerimagefile', $card->id);
			$imagesavedfile = $fs->get_file($context->id, 'mod_flashcard', 'answerimagefile', $card->id);
            $filepickeritemid = required_param(preg_replace('/^ai/', 'as', $akey), PARAM_CLEANHTML);
			file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answersoundfile', $card->id);
			$soundsavedfile = $fs->get_file($context->id, 'mod_flashcard', 'answersoundfile', $card->id);
        	$card->answertext = $imagesavedfile->id.'@'.$soundsavedfile->id;
        }
        if (!$DB->update_record('flashcard_deckdata', $card)) {
            print_error('errorupdatecard', 'flashcard');
        }
    }
}
/* * ****************************** Prepare import **************************** */
if ($action == 'import') {
    include 'import_form.php';
    $mform = new flashcard_import_form($flashcard->id);
    echo $OUTPUT->heading(get_string('importingcards', 'flashcard') . $OUTPUT->help_icon('import', 'flashcard'));
    $mform->display();
    echo $OUTPUT->footer($course);
    exit(0);
}
/* * ****************************** Perform import **************************** */
if ($action == 'doimport') {
    include 'import_form.php';
    $form = new flashcard_import_form($flashcard->id);

    $CARDSEPPATTERNS[0] = ':';
    $CARDSEPPATTERNS[1] = ';';
    $CARDSEPPATTERNS[2] = "\n";
    $CARDSEPPATTERNS[3] = "\r\n";

    $FIELDSEPPATTERNS[0] = ',';
    $FIELDSEPPATTERNS[1] = ':';
    $FIELDSEPPATTERNS[2] = " ";
    $FIELDSEPPATTERNS[3] = "\t";

    if ($data = $form->get_data()) {

        if (!empty($data->confirm)) {

            $cardsep = $CARDSEPPATTERNS[$data->cardsep];
            $fieldsep = $FIELDSEPPATTERNS[$data->fieldsep];

            // filters comments and non significant lines
            $data->import = preg_replace("/^#.*\$/m", '', $data->import);
            $data->import = preg_replace("/^\\/.*\$/m", '', $data->import);
            $data->import = preg_replace('/^\\s+$/m', '', $data->import);
            $data->import = preg_replace("/(\\r?\\n)\\r?\\n/", '$1', $data->import);
            $data->import = trim($data->import);

            $pairs = explode($cardsep, $data->import);
            if (!empty($pairs)) {
                /// first integrity check
                $report->cards = count($pairs);
                $report->badcards = 0;
                $report->goodcards = 0;
                $inputs = array();
                foreach ($pairs as $pair) {
                    if (strstr($pair, $fieldsep) === false) {
                        $report->badcards++;
                    } else {
                        $input = new StdClass;
                        list($input->question, $input->answer) = explode($fieldsep, $pair);
                        if (empty($input->question) || empty($input->answer)) {
                            $report->badcards++;
                        } else {
                            $inputs[] = $input;
                            $report->goodcards++;
                        }
                    }
                }

                if ($report->badcards == 0) {
                    /// everything ok
                    /// reset all data
                    $DB->delete_records('flashcard_card', array('flashcardid' => $flashcard->id));
                    $DB->delete_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));

                    // insert new cards
                    foreach ($inputs as $input) {
                        $deckcard->flashcardid = $flashcard->id;
                        $deckcard->questiontext = $input->question;
                        $deckcard->answertext = $input->answer;
                        $DB->insert_record('flashcard_deckdata', $deckcard);
                    }

                    // reset questionid in flashcard instance
                    $DB->set_field('flashcard', 'questionid', 0, array('id' => $flashcard->id));
                }

                $reportstr = get_string('importreport', 'flashcard') . '<br/>';
                $reportstr = get_string('cardsread', 'flashcard') . $report->cards . '<br/>';
                if ($report->badcards) {
                    $reportstr .= get_string('goodcards', 'flashcard') . $report->goodcards . '<br/>';
                    $reportstr .= get_string('badcards', 'flashcard') . $report->badcards . '<br/>';
                }

                echo "<center>";
                echo $OUTPUT->box($reportstr, 'reportbox');
                echo "</center>";
            }
        }
    }
}
