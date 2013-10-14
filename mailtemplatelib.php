<?php

/**
* This library is a third-party proposal for standardizing mail
* message constitution for third party modules. It is actually used
* by all ethnoinformatique.fr module. It relies on mail and message content
* templates that should reside in a mail/{$lang} directory within the 
* module space.
*
* @package extralibs
* @category third-party libs
* @author Valery Fremaux (France) (valery@valeisti.fr)
* @date 2008/03/03
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/**
* useful templating functions from an older project of mine, hacked for Moodle
* @param template the template's file name from $CFG->sitedir
* @param infomap a hash containing pairs of parm => data to replace in template
* @return a fully resolved template where all data has been injected
*/
function flashcard_compile_mail_template($template, $infomap, $lang = '') {
    global $USER;
    
    if (empty($lang)) $lang = $USER->lang; 
    $lang = substr($lang, 0, 2); // be sure we are in moodle 2
    
    $notification = implode('', flashcard_get_mail_template($template, $lang));
    foreach($infomap as $aKey => $aValue){
        $notification = str_replace("<%%$aKey%%>", $aValue, $notification);
    }
    return $notification;
}

/*
* resolves and get the content of a Mail template, acoording to the user's current language.
* @param virtual the virtual mail template name
* @param module the current module
* @param lang if default language must be overriden
* @return string the template's content or false if no template file is available
*/
function flashcard_get_mail_template($virtual, $lang = ''){
    global $CFG;

    if ($lang == '') {
        $lang = $CFG->lang;
    }
    $templateName = "{$CFG->dirroot}/mod/flashcard/mails/{$lang}/{$virtual}.tpl";
    if (file_exists($templateName))
        return file($templateName);

    debugging("template $templateName not found");
    return array();
}

