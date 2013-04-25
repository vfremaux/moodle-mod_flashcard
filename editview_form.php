<?php

include_once $CFG->libdir.'/formslib.php';

class CardEdit_Form extends moodleform{
	
	function definition(){
		global $DB, $OUTPUT;
		
		$mform = $this->_form;
		$flashcard = $this->_customdata['flashcard'];

		$mform->addElement('hidden', 'id'); // course module id
		$mform->addElement('hidden', 'what', 'update'); // action keyword

		$from = optional_param('from', 0, PARAM_INT);		
		$pagesize = 30;
		
		$cards = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id), 'id', '*', $from, $pagesize);
		
		$cmid = $this->_customdata['cmid'];
		if ($cards){
            $repeatno = count($cards);
            $repeatno += 2;
        } else {
            $repeatno = 5;
        }

		$cardids = array_keys($cards);
		$newid = 0;

		for($i = 0 ; $i < $repeatno; $i++){
			if (isset($cardids[$i])) {
				$cid = $cardids[$i];
			} else {
				$cid = '_new_'.$newid;
				$newid++;
			}
			$cardnum = $i + 1 + $from;
			$mform->addElement('header', 'card'.$i, get_string('card', 'flashcard'). ' '.$cardnum);
			$mform->addElement('html', '<table width=100%">');
			$mform->addElement('html', '<tr><td>');
			if (isset($cardids[$i])){
				$mform->addElement('advcheckbox', 'items_'.$cardids[$i], '', null, array('group' => 1));
			}
			$mform->addElement('html', '</td><td>');
			$this->build_card_element('question', $cid);
			$mform->addElement('html', '</td><td>');
			$this->build_card_element('answer', $cid);
			$mform->addElement('html', '</td><td>');
			if (isset($cardids[$i])){
	            $commands = "<a href=\"view.php?id={$cmid}&amp;what=delete&amp;items={$cid}&amp;view=edit\"><img src=\"".$OUTPUT->pix_url('delete','flashcard')."\" /></a>";
	        } else {
	        	$commands = '';
	        }
			$mform->addElement('html', $commands);			
			$mform->addElement('html', '</td></tr></table>');
		}

		$mform->addElement('header', 'end', '');
		$this->add_checkbox_controller(1);
		$mform->addElement('submit', 'deletesel', get_string('deleteselected', 'flashcard'));
		
		$this->add_action_buttons(true);
	}
	
	function build_card_element($side, $cardid){
		global $COURSE;
		
		$mform = $this->_form;
		
		$sideprefix = substr($side, 0, 1);
		$key = $side.'smediatype';
		$mediatype = $this->_customdata['flashcard']->$key;

		$maxbytes = 100000;
		
        if ($mediatype == FLASHCARD_MEDIA_IMAGE){
        	$mform->addElement('filepicker', $sideprefix.$cardid, '', null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.jpg', '.png', '.gif')));
        } elseif ($mediatype == FLASHCARD_MEDIA_SOUND){
        	$mform->addElement('filepicker', $sideprefix.$cardid, '', null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.mp3', '.swf')));
        } elseif ($mediatype == FLASHCARD_MEDIA_VIDEO){
        	$mform->addElement('filepicker', $sideprefix.$cardid, '', null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('video')));
        } elseif ($mediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
        	$mform->addElement('filepicker', $sideprefix.'i'.$cardid, get_string('image', 'flashcard'), null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.jpg', '.png', '.gif')));
			$mform->addElement('filepicker', $sideprefix.'s'.$cardid, get_string('sound', 'flashcard'), null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.mp3', '.swf')));
        } else {
        	$mform->addElement('textarea', $sideprefix.$cardid, '', array('cols' => 60, 'rows' => 4));
        }
	}

	/**
	* preloads existing images
	*
	*/
	function set_data($data){
		global $DB;
		
		$flashcard = $this->_customdata['flashcard'];
		
		$from = optional_param('from', 0, PARAM_INT);		
		$pagesize = 30;
		
		$cards = $DB->get_records('flashcard_deckdata', array('flashcardid' => $flashcard->id), 'id', '*', $from, $pagesize);
		
		if ($flashcard->questionsmediatype != FLASHCARD_MEDIA_TEXT){
			foreach($cards as $card){
				$this->setup_card_filearea($card, 'question', $flashcard->questionsmediatype, $data);
			}
		} else {
			foreach($cards as $card){
				$key = 'q'.$card->id;
				$data->$key = $card->questiontext;
			}
		}

		if ($flashcard->answersmediatype != FLASHCARD_MEDIA_TEXT){
			foreach($cards as $card){
				$this->setup_card_filearea($card, 'answer', $flashcard->answersmediatype, $data);
			}
		} else {
			foreach($cards as $card){
				$key = 'a'.$card->id;
				$data->$key = $card->answertext;
			}
		}
				
		parent::set_data($data);
		
	}
	
	/**
	* prepares preloaded file area with existing file, or new filearea for the filepicker
	*
	*/
	function setup_card_filearea(&$card, $side, $mediatype, &$data){
		global $COURSE;
		
		$sideprefix = substr($side, 0, 1);
		$context = context_module::instance($data->coursemodule);

		switch($mediatype){
			case FLASHCARD_MEDIA_IMAGE:
				$filearea = $side.'imagefile';
				break;
			case FLASHCARD_MEDIA_SOUND:
				$filearea = $side.'soundfile';
				break;
			case FLASHCARD_MEDIA_VIDEO:
				$filearea = $side.'videofile';
				break;
			case FLASHCARD_MEDIA_IMAGE_AND_SOUND:
				$filearea = $side.'imagefile';
				$filearea2 = $side.'soundfile';
				break;
		}
		if ($mediatype != FLASHCARD_MEDIA_IMAGE_AND_SOUND){
			$elmname = $sideprefix.$card->id;
			$draftitemid = file_get_submitted_draft_itemid($sideprefix.$card->id);
			$maxbytes = 100000;
			file_prepare_draft_area($draftitemid, $context->id, 'mod_flashcard', $filearea, $card->id, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1));		 
			$data->$elmname = $draftitemid;
		} elseif ($mediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
			$elmname = $sideprefix.'i'.$card->id;
			$draftitemid = file_get_submitted_draft_itemid($elmname);
			$maxbytes = 100000;
			file_prepare_draft_area($draftitemid, $context->id, 'mod_flashcard', $filearea, $card->id, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1));
			$data->$elmname = $draftitemid;

			$elmname = $sideprefix.'s'.$card->id;
			$draftitemid = file_get_submitted_draft_itemid($elmname);
			$maxbytes = 100000;
			file_prepare_draft_area($draftitemid, $context->id, 'mod_flashcard', $filearea2, $card->id, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1));
			$data->$elmname = $draftitemid;
		}
	}
}