<?PHP

function xmldb_flashcard_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    $result = true;

    // this should patch the question_match anyway

    $table = new XMLDBTable('question_match');
    $field = new XMLDBField('numquestions');
    $field->setAttributes (XMLDB_TYPE_INTEGER, '10', 'true', 'true', null, null, null, '0');
    if (!field_exists($table, $field)){
        add_field($table, $field, true, true);
    }

    if ($oldversion < 2008050400){
    
    /// Define field starttime to be added to flashcard
        $table = new XMLDBTable('flashcard');

    /// Launch add field starttime
        $field = new XMLDBField('starttime');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, null, 'timemodified');
        $result = $result && add_field($table, $field);

    /// Launch add field endtime
        $field = new XMLDBField('endtime');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, null, 'starttime');
        $result = $result && add_field($table, $field);

    /// Launch add field autodowngrade
        $field = new XMLDBField('autodowngrade');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 1, 'questionid');
        $result = $result && add_field($table, $field);
        
    /// Launch add field deck2_release
        $field = new XMLDBField('deck2_release');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 96, 'autodowngrade');
        $result = $result && add_field($table, $field);

    /// Launch add field deck3_release
        $field = new XMLDBField('deck3_release');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 96, 'deck2_release');
        $result = $result && add_field($table, $field);

    /// Launch add field deck1_delay
        $field = new XMLDBField('deck1_delay');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 48, 'deck3_release');
        $result = $result && add_field($table, $field);

    /// Launch add field deck2_delay
        $field = new XMLDBField('deck2_delay');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 96, 'deck1_delay');
        $result = $result && add_field($table, $field);    

    /// Launch add field deck3_delay
        $field = new XMLDBField('deck3_delay');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 168, 'deck2_delay');
        $result = $result && add_field($table, $field);

    /// Define table flashcard_card to be created
        $table = new XMLDBTable('flashcard_card');

    /// Adding fields to table flashcard_card
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('flashcardid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('entryid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('deck', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('lastaccessed', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Adding keys to table flashcard_card
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for flashcard_card
        $result = $result && create_table($table);
    }

    if ($oldversion < 2008050500){
    
    /// Define field starttime to be added to flashcard
        $table = new XMLDBTable('flashcard');

    /// Launch add field deck4_release
        $field = new XMLDBField('deck4_release');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 96, 'deck3_release');
        $result = $result && add_field($table, $field);

    /// Launch add field deck4_delay
        $field = new XMLDBField('deck4_delay');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 336, 'deck3_delay');
        $result = $result && add_field($table, $field);

    /// Launch add field questionsasimages
        $field = new XMLDBField('questionsasimages');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'deck4_delay');
        $result = $result && add_field($table, $field);

    /// Launch add field answersasimages
        $field = new XMLDBField('answersasimages');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'questionsasimages');
        $result = $result && add_field($table, $field);
    }

    if ($oldversion < 2008050501){
    
    /// Define field starttime to be added to flashcard
        $table = new XMLDBTable('flashcard');

    /// Launch add field decks
        $field = new XMLDBField('decks');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '3', 'autodowngrade');
        $result = $result && add_field($table, $field);    
    }

    if ($result && $oldversion < 2008050800) {

    /// Define table flashcard_deckdata to be created
        $table = new XMLDBTable('flashcard_deckdata');

    /// Adding fields to table flashcard_deckdata
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('flashcardid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('questiontext', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);
        $table->addFieldInfo('answertext', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);

    /// Adding keys to table flashcard_deckdata
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for flashcard_deckdata
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2008050900) {

    /// Define field accesscount to be added to flashcard_card
        $table = new XMLDBTable('flashcard_card');
        $field = new XMLDBField('accesscount');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'lastaccessed');

    /// Launch add field accesscount
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008051100) {

    /// Rename field questionsasimages on table flashcard to questionsmediatype
        $table = new XMLDBTable('flashcard');
        $field = new XMLDBField('questionsasimages');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'deck4_delay');

    /// Launch rename field questionsmediatype
        $result = $result && rename_field($table, $field, 'questionsmediatype');

    /// Rename field answersasimages on table flashcard to answersmediatype
        $table = new XMLDBTable('flashcard');
        $field = new XMLDBField('answersasimages');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'deck4_delay');

    /// Launch rename field questionsmediatype
        $result = $result && rename_field($table, $field, 'answersmediatype');

    /// Define field flipdeck to be added to flashcard
        $table = new XMLDBTable('flashcard');
        $field = new XMLDBField('flipdeck');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'answersmediatype');
        $result = $result && add_field($table, $field);
	}
	
    if ($result && $oldversion < 2011100601) {
    /// Define field flipdeck to be added to flashcard
        $table = new XMLDBTable('flashcard');
        $field = new XMLDBField('audiostart');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'answersmediatype');
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>
