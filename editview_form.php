<?php

include_once $CFG->libdir.'/formslib.php';

class CardEdit_Form extends moodleform{
	
	function definition(){
		global $DB, $OUTPUT;
		
		$mform = $this->_form;
		$flashcard = $this->_customdata['flashcard'];

		$mform->addElement('hidden', 'id'); // course module id
		$mform->addElement('hidden', 'what', $this->_customdata['cmd']); // action keyword
		
		$num = 1;
		if ($this->_customdata['cmd'] == 'addthree'){
			$num = 3;
		}
		if (!empty($this->_customdata['cardid'])){
			$mform->addElement('hidden', 'cardid'); // course module id
		}

		for($i = 0; $i < $num ; $i++){
			$cardnum = $i + 1;
			$mform->addElement('header', 'card'.$i, get_string('card', 'flashcard'). ' '.$cardnum);
			$mform->addElement('html', '<table width=100%">');
			$mform->addElement('html', '<tr><td width="50%">');

			$this->build_card_element('question', $i);

			$mform->addElement('html', '</td><td width="50%">');

			$this->build_card_element('answer', $i);

			$mform->addElement('html', '</td></tr></table>');
		}
		
		$this->add_action_buttons(true);
	}
	
	function build_card_element($side, $podid){
		global $COURSE;
		
		$mform = $this->_form;
		
		$sideprefix = substr($side, 0, 1);
		$key = $side.'smediatype';
		$mediatype = $this->_customdata['flashcard']->$key;

		$maxbytes = 100000;
		
        if ($mediatype == FLASHCARD_MEDIA_IMAGE){
        	$mform->addElement('filepicker', $sideprefix.$podid, '', null, array('maxfiles' => 1, 'maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.jpg', '.png', '.gif')));
        } elseif ($mediatype == FLASHCARD_MEDIA_SOUND){
        	$mform->addElement('filepicker', $sideprefix.$podid, '', null, array('maxfiles' => 1, 'maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.mp3', '.swf')));
        } elseif ($mediatype == FLASHCARD_MEDIA_VIDEO){
        	$mform->addElement('filepicker', $sideprefix.$podid, '', null, array('maxfiles' => 1, 'maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('video')));
        } elseif ($mediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
        	$mform->addElement('filepicker', $sideprefix.'i'.$podid, get_string('image', 'flashcard'), null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.jpg', '.png', '.gif')));
			$mform->addElement('filepicker', $sideprefix.'s'.$podid, get_string('sound', 'flashcard'), null, array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.mp3', '.swf')));
        } else {
        	$mform->addElement('textarea', $sideprefix.$podid, '', array('cols' => 60, 'rows' => 4));
        }
	}

	/**
	* preloads existing images
	*
	*/
	function set_data($data){
		global $DB;
		
		if ($cardid = $this->_customdata['cardid']){				
			$card = $DB->get_record('flashcard_deckdata', array('id' => $cardid));
	
			$flashcard = $this->_customdata['flashcard'];
			
			if ($flashcard->questionsmediatype != FLASHCARD_MEDIA_TEXT){
				$this->setup_card_filearea($card, 'question', $flashcard->questionsmediatype, $data);
			} else {
				$data->q0 = $card->questiontext;
			}
	
			if ($flashcard->answersmediatype != FLASHCARD_MEDIA_TEXT){
				$this->setup_card_filearea($card, 'answer', $flashcard->answersmediatype, $data);
			} else {
				$data->a0 = $card->answertext;
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
		$cmid = $this->_customdata['cmid'];
		$context = context_module::instance($cmid);

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
			default:
				print_error('errorunsupportedformat', 'flashcard');
		}
		if ($mediatype != FLASHCARD_MEDIA_IMAGE_AND_SOUND){
			$elmname = $sideprefix.'0';
			$draftitemid = file_get_submitted_draft_itemid($elmname);
			$maxbytes = 100000;
			file_prepare_draft_area($draftitemid, $context->id, 'mod_flashcard', $filearea, $card->id, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1));		 
			$data->$elmname = $draftitemid;
		} elseif ($mediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND){
			$elmname = $sideprefix.'i0';
			$draftitemid = file_get_submitted_draft_itemid($elmname);
			$maxbytes = 100000;
			file_prepare_draft_area($draftitemid, $context->id, 'mod_flashcard', $filearea, $card->id, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1));
			$data->$elmname = $draftitemid;

			$elmname = $sideprefix.'s0';
			$draftitemid = file_get_submitted_draft_itemid($elmname);
			$maxbytes = 100000;
			file_prepare_draft_area($draftitemid, $context->id, 'mod_flashcard', $filearea2, $card->id, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1));
			$data->$elmname = $draftitemid;
		}
	}
}