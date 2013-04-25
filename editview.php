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

    /* @var $OUTPUT core_renderer */

    if (!defined('MOODLE_INTERNAL')){
        die("Illegal direct access to this screen");
    }
    
    $form = new CardEdit_Form($CFG->wwwroot.'/mod/flashcard/view.php?view=edit', array('flashcard' => $flashcard, 'cmid' => $cm->id));
    $cards = $DB->count_records('flashcard_deckdata', array('flashcardid' => $flashcard->id));

    if (($data = $form->get_data()) || $action){
        $result = include "{$CFG->dirroot}/mod/flashcard/editview.controller.php";
    }

	/*    
    $strquestionnum = get_string('num', 'flashcard');
    $strquestion = get_string('question', 'flashcard');
    $stranswer = get_string('answer', 'flashcard');
    $strcommands = get_string('commands', 'flashcard');
	*/
    
/// Print deferred header
    
    echo $out;

	if ($cards){
		$formdata->coursemodule = $cm->id;
		$formdata->id = $cm->id;
		$form->set_data($formdata);
	} else {
	    echo $OUTPUT->box(get_string('nocards', 'flashcard'));
	}
	$form->display();

