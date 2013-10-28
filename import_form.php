<?php

    /** 
    * This view allows checking deck states
    * 
    * @package mod-flashcard
    * @category mod
    * @author Valery Fremaux (valery.fremaux@club-internet.fr)
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

require_once($CFG->libdir.'/formslib.php');

class flashcard_import_form extends moodleform{

    function definition(){
        
        $mform =& $this->_form;
        $mform->addElement('hidden', 'what'); 
        $mform->addElement('hidden', 'view'); 
        $mform->addElement('hidden', 'id'); 
        
        $mform->addElement('header', 'cardimport', ''); 
        
        $fieldsepoptions[0] = ',';
        $fieldsepoptions[1] = ':';
        $fieldsepoptions[2] = ';';
        $mform->addElement('select', 'fieldsep', get_string('fieldsep', 'flashcard'), $fieldsepoptions);

        $mform->addElement('textarea', 'import', get_string('imported', 'flashcard'), array('ROWS' => 20, 'COLS' => 60));

        $mform->addElement('checkbox', 'confirm', get_string('confirm', 'flashcard'), get_string('importadvice', 'flashcard'));

        $this->add_action_buttons(true, get_string('import', 'flashcard'));
    }

}
