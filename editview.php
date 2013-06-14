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
	require_once 'editview_form.php';

    $usercontext = context_user::instance($USER->id);

    /* @var $OUTPUT core_renderer */

    if (!defined('MOODLE_INTERNAL')) die("Illegal direct access to this screen");

	if ($cardid = optional_param('cardid', 0, PARAM_INT)){
		$card = $DB->get_record('flashcard_deckdata', array('id' => $cardid));
	}
    $mform = new CardEdit_Form($CFG->wwwroot.'/mod/flashcard/view.php?view=edit', array('flashcard' => $flashcard, 'cmid' => $cm->id, 'cmd' => $action, 'cardid' => $cardid));
    
/// Print deferred header

	if ($mform->is_cancelled()){
		redirect($CFG->wwwroot.'/mod/flashcard/view.php?id='.$id.'&view=manage');
	}
	
	if ($data = $mform->get_data()){

		$validqkeys = array();

		print_object($data);
		// add or update cards
		
		if ($data->what == 'update'){
			$akeys[] = 'a0';
			$validqkeys['q0'] = $DB->get_record('flashcard_deckdata', array('id' => $data->cardid));
		} else {

			// prepare all new cards we need
		
		    $keys = array_keys((array)$data);    // get the key value of all the fields submitted
		
		    $qkeys = preg_grep('/^q/', $keys);   // filter out only the status
		    $akeys = preg_grep('/^a/', $keys);   // filter out only the assigned updating
		    
		    foreach($qkeys as $qkey){
	    
				// for new cards : get a new record, insert it and use it for update    	
		    	if (preg_match('/^qs/', $qkey)){
		    		continue; // sounds will be processed at same time than images
		    	}

		    	if (preg_match('/qi?/', $qkey)){
	
					// if empty question, do not add
					if ($_REQUEST[$qkey] == ''){
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

					$validqkeys[$qkey] = $card; // validate the input param	and store card
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
						$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionvideofile', $card->id, '', false);
						$savedfile = array_pop($savedfiles);
			        	$card->questiontext = $savedfile->get_id();
			        }
		        } else {
		            // combine image and sound in one single field
		            $filepickeritemid = required_param($qkey, PARAM_INT);
		            $imagesavedid = '';
					if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
						file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionimagefile', $card->id);
						$imagesavedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id, '', false);
						$imagesavedfile = array_pop($imagesavedfiles);
						$imagesavedid = $imagesavedfile->get_id();
					}
		            $soundsavedid = '';
		            $filepickeritemid = required_param(preg_replace('/^qi/', 'qs', $qkey), PARAM_INT);
					if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
						file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'questionsoundfile', $card->id);
						$soundsavedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'questionimagefile', $card->id, '', false);
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
	        } else {
	    		if (empty($fs)) $fs = get_file_storage();

		        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
		            $filepickeritemid = required_param($akey, PARAM_INT);
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answerimagefile', $card->id);
					$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'answerimagefile', $card->id, '', false);
					// there should be only one
					$savedfile = array_pop($savedfiles);
		        	$card->answertext = $savedfile->get_id();
		        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
		            $filepickeritemid = required_param($akey, PARAM_INT);
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answersoundfile', $card->id);
					$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'answersoundfile', $card->id, '', false);
					// there should be only one
					$savedfile = array_pop($savedfiles);
		        	$card->answertext = $savedfile->get_id();
		        } elseif ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
		            $filepickeritemid = required_param($akey, PARAM_INT);
					file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answervideofile', $card->id);
					$savedfiles = $fs->get_area_files($context->id, 'mod_flashcard', 'answervideofile', $card->id, '', false);
					// there should be only one
					$savedfile = array_pop($savedfiles);
		        	$card->answertext = $savedfile->get_id();
		        } else {
		            // combine image and sound in one single field
		            $imagesavedid = '';
		            $filepickeritemid = required_param($akey, PARAM_CLEANHTML);
					if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
						file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answerimagefile', $card->id);
						$imagesavedfiles = $fs->get_area_file($context->id, 'mod_flashcard', 'answerimagefile', $card->id, '', false);
						$imagesavedfile = array_pop($imagesavedfiles);
						$imagesavedid = $imagesavedfile->get_id();
					}

		            $soundsavedid = '';
		            $filepickeritemid = required_param(preg_replace('/^ai/', 'as', $akey), PARAM_CLEANHTML);
					if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $filepickeritemid, true)){
						file_save_draft_area_files($filepickeritemid, $context->id, 'mod_flashcard', 'answersoundfile', $card->id);
						$soundsavedfiles = $fs->get_area_file($context->id, 'mod_flashcard', 'answersoundfile', $card->id, '', false);
						$soundsavedfile = array_pop($soundsavedfiles);
						$soundsavedid = $soundsavedfile->get_id();
					}
	        		$card->answertext = $imagesavedid.'@'.$soundsavedid;
		        }
		    }
	        if (!$DB->update_record('flashcard_deckdata', $card)) {
	            print_error('errorupdatecard', 'flashcard');
	        }
	    }
		
		echo $OUTPUT->continue_button($CFG->wwwroot.'/mod/flashcard/view.php?id='.$id.'&view=manage');
		// redirect($CFG->wwwroot.'/mod/flashcard/view.php?id='.$id.'&view=manage');
	}
    
    echo $out;

	// if cardid, load card into form
	if ($cardid){
		$card->cardid = $card->id;
		$card->id = $cm->id;
		$mform->set_data($card);
	} else {
		$data = new StdClass;
		$data->id = $cm->id;
		$mform->set_data($data);
	}
	$mform->display();

