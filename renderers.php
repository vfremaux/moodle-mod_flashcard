<?php

require_once($CFG->dirroot.'/repository/lib.php');

function flashcard_filepicker($elname, $value, $contextid, $filearea, $cardid, $maxbytes, $accepted_types = '*'){
	global $COURSE, $PAGE, $OUTPUT, $USER;

    $html = '';

	$usercontext = context_user::instance($USER->id);
	$fs = get_file_storage();
	
    // no existing area info provided - let's use fresh new draft area
	if ($value){
	    $draftitemid = file_get_submitted_draft_itemid($filearea);
		$maxbytes = 100000;
		file_prepare_draft_area($draftitemid, $contextid, 'mod_flashcard', $filearea, $cardid, array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1));
	} else {
        $draftitemid = file_get_unused_draft_itemid();
	}

    if ($COURSE->id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
    }

    $client_id = uniqid();

    $args = new stdClass();
    // need these three to filter repositories list
    $args->accepted_types = $accepted_types ;
    $args->return_types = FILE_INTERNAL;
    $args->itemid = $draftitemid;
    $args->maxbytes = $maxbytes;
    $args->buttonname = $elname.'choose';
    $args->elementname = $elname;
    $id = $elname;

    $fp = new file_picker($args);
    $options = $fp->options;
    $options->context = $PAGE->context;
    $html .= $OUTPUT->render($fp);
    $html .= '<input type="hidden" name="'.$elname.'" id="'.$id.'" value="'.$draftitemid.'" class="filepickerhidden"/>';

    $module = array(
    	'name' => 'form_filepicker', 
    	'fullpath' => '/lib/form/filepicker.js', 
    	'requires' => array('core_filepicker', 'node', 'node-event-simulate')
    );

    $PAGE->requires->js_init_call('M.form_filepicker.init', array($fp->options), true, $module);

    $nonjsfilepicker = new moodle_url('/repository/draftfiles_manager.php', array(
        'env' => 'filepicker',
        'action' => 'browse',
        'itemid' => $draftitemid,
        'subdirs' => 0,
        'maxbytes' => $options->maxbytes,
        'maxfiles' => 1,
        'ctx_id' => $PAGE->context->id,
        'course' => $PAGE->course->id,
        'sesskey' => sesskey(),
        ));

    // non js file picker
    $html .= '<noscript>';
    $html .= "<div><object type='text/html' data='$nonjsfilepicker' height='160' width='600' style='border:1px solid #000'></object></div>";
    $html .= '</noscript>';
    
    return $html;
}

/**
* prints a deck depending on deck status
* @param reference $cm the coursemodule
* @param int $deck the deck number
* @uses $CFG
*/
function flashcard_print_deck(&$flashcard, &$cm, $deck){
    global $CFG, $OUTPUT;
    
    $emptydeckurl = $OUTPUT->pix_url('emptydeck', 'flashcard');
    if ($flashcard->customreviewemptyfileid){
    	$emptydeckurl = flashcard_get_file_url($flashcard->customreviewemptyfileid);
    }
    
    $decktoreviewurl = $OUTPUT->pix_url('enableddeck', 'flashcard');
    if ($flashcard->customreviewfileid){
    	$decktoreviewurl = flashcard_get_file_url($flashcard->customreviewfileid);
    }

    $deckreviewedurl = $OUTPUT->pix_url('disableddeck', 'flashcard');
    if ($flashcard->customreviewedfileid){
    	$deckreviewedurl = flashcard_get_file_url($flashcard->customreviewedfileid);
    }
    
    if ($deck == 0){
        echo "<img src=\"$emptydeckurl\"/>";
    }

    if ($deck > 0){
        echo "<a href=\"view.php?view=play&amp;id={$cm->id}&amp;deck={$deck}&amp;what=initialize\" title=\"".get_string('playwithme', 'flashcard')."\"><img src=\"$decktoreviewurl\"/></a>";
    }

    if ($deck < 0){
        $deck = -$deck;
        echo "<a href=\"view.php?view=play&amp;id={$cm->id}&amp;deck={$deck}&amp;what=initialize\" title=\"".get_string('reinforce', 'flashcard')."\"><img src=\"{$deckreviewedurl}\"/></a>";
    }
}
/**
* prints the deck status for use in teacher's overview
* @param reference $flashcard the flashcard object
* @param int $userid the user for which printing status
* @param object $status a status object to be filled by the function
* @param boolean $return if true, returns the produced HTML, elsewhere prints it.
* @uses $CFG
*/
function flashcard_print_deck_status(&$flashcard, $userid, &$status, $return){
    global $CFG, $OUTPUT;

    $str = '';
    
    $str = "<table width=\"100%\"><tr valign=\"bottom\"><td width=\"30%\" align=\"center\">";
    
    // print for deck 1
    if ($status->decks[0]->count){
        $image = ($status->decks[0]->reactivate) ? 'topenabled' : 'topdisabled' ;
        $height = $status->decks[0]->count * 3;
        $str .= "<table cellspacing=\"2\"><tr><td><div style=\"padding-bottom: {$height}px\" class=\"graphdeck\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/{$image}.png\" title=\"".get_string('cardsindeck', 'flashcard', $status->decks[0]->count)."\"/></div></td><td>";
        $dayslateness = floor((time() - $status->decks[0]->lastaccess) / DAYSECS);
        // echo "late 1 : $dayslateness";
        $timetoreview = round(max(0, ($status->decks[0]->lastaccess + ($flashcard->deck1_delay * HOURSECS) - time()) / DAYSECS));
        $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
        for($i = 0 ; $i < min($dayslateness, floor($flashcard->deck1_delay / 24)) ; $i++){
            $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/clock.png\" valign=\"bottom\" title=\"$strtimetoreview\" />";
        }
        if ($dayslateness < $flashcard->deck1_delay / 24){
            for(; $i < $flashcard->deck1_delay / 24 ; $i++){
                $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/shadowclock.png\" valign=\"bottom\"  title=\"$strtimetoreview\" />";
            }
        } elseif ($dayslateness > $flashcard->deck1_delay / 24){
            // Deck 1 has no release limit as cards can stay here as long as not viewed.
            for($i = 0; $i < min($dayslateness - floor($flashcard->deck1_delay / 24), 4) ; $i++){
                $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/overtime.png\" valign=\"bottom\" />";
            }
        }
        $str .= '</td></tr></table>';
    } else {
        $str .= "<div height=\"12px\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topempty.png\" /></div>";
    }
    
    $str .= "</td><td>". $OUTPUT->pix_icon('a/r_breadcrumb', 'right breadcrumb icon') ."</td><td width=\"30%\" align=\"center\">";

    // print for deck 2
    if ($status->decks[1]->count){
        $image = ($status->decks[1]->reactivate) ? 'topenabled' : 'topdisabled' ;
        $height = $status->decks[1]->count * 3;
        $str .= "<table cellspacing=\"2\"><tr><td><div style=\"padding-bottom: {$height}px\" class=\"graphdeck\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/{$image}.png\" title=\"".get_string('cardsindeck', 'flashcard', $status->decks[1]->count)."\"/></div></td><td>";
        $dayslateness = floor((time() - $status->decks[1]->lastaccess) / DAYSECS);
        // echo "late 2 : $dayslateness ";
        $timetoreview = round(max(0, ($status->decks[1]->lastaccess + ($flashcard->deck2_delay * HOURSECS) - time()) / DAYSECS));
        $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
        for($i = 0 ; $i < min($dayslateness, floor($flashcard->deck2_delay / 24)) ; $i++){
            $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/clock.png\" valign=\"bottom\" title=\"$strtimetoreview\" />";
        }
        if ($dayslateness < $flashcard->deck2_delay / 24){
            for(; $i < $flashcard->deck2_delay / 24 ; $i++){
                $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/shadowclock.png\" valign=\"bottom\"  title=\"$strtimetoreview\" />";
            }
        } elseif ($dayslateness > $flashcard->deck2_delay / 24){
            for($i = 0; $i < min($dayslateness - floor($flashcard->deck2_delay / 24), $flashcard->deck2_release / 24) ; $i++){
                $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/overtime.png\" valign=\"bottom\" />";
            }
        }
        $str .= '</td></tr></table>';
    } else {
        $str .= "<div height=\"12px\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topempty.png\" /></div>";
    }

    if ($flashcard->decks >= 3){
        $str .= "</td><td>".$OUTPUT->pix_icon('a/r_breadcrumb', 'right breadcrumb icon')."</td><td width=\"30%\" align=\"center\">";

        // print for deck 3
        if ($status->decks[2]->count){
            $image = ($status->decks[2]->reactivate) ? 'topenabled' : 'topdisabled' ;
            $height = $status->decks[2]->count * 3;
            $str .= "<table cellspacing=\"2\"><tr><td><div style=\"padding-bottom: {$height}px\" class=\"graphdeck\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/{$image}.png\" title=\"".get_string('cardsindeck', 'flashcard', $status->decks[2]->count)."\"/></div></td><td>";
            $dayslateness = floor((time() - $status->decks[2]->lastaccess) / DAYSECS);
            // echo "late 3 : $dayslateness ";
            $timetoreview = round(max(0, ($status->decks[2]->lastaccess + ($flashcard->deck3_delay * HOURSECS) - time()) / DAYSECS));
            $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
            for($i = 0 ; $i < min($dayslateness, floor($flashcard->deck3_delay / 24)) ; $i++){
                $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/clock.png\" valign=\"bottom\" />";
            }
            if ($dayslateness < $flashcard->deck3_delay / 24){
                for(; $i < $flashcard->deck3_delay / 24 ; $i++){
                    $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/shadowclock.png\" valign=\"bottom\"  title=\"$strtimetoreview\" />";
                }
            } elseif ($dayslateness > $flashcard->deck3_delay / 24){
                for($i = 0; $i < min($dayslateness - floor($flashcard->deck3_delay / 24), $flashcard->deck3_release / 24) ; $i++){
                    $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/overtime.png\" valign=\"bottom\" />";
                }
            }
            $str .= '</td></tr></table>';
        } else {
            $str .= "<div height=\"12px\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topempty.png\"  title=\"$strtimetoreview\" /></div>";
        }
    }
    if ($flashcard->decks >= 4){
        $str .= "</td><td>".$OUTPUT->pix_icon('a/r_breadcrumb', 'right breadcrumb icon')."</td><td width=\"30%\" align=\"center\">";

        // print for deck 4
        if ($status->decks[3]->count){
            $image = ($status->decks[3]->reactivate) ? 'topenabled' : 'topdisabled' ;
            $height = $status->decks[3]->count * 3;
            $str .= "<table cellspacing=\"2\"><tr><td><div style=\"padding-bottom: {$height}px\" class=\"graphdeck\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/{$image}.png\" title=\"".get_string('cardsindeck', 'flashcard', $status->decks[3]->count)."\"/></div></td><td>";
            $dayslateness = floor((time() - $status->decks[3]->lastaccess) / DAYSECS);
            $timetoreview = round(max(0, ($status->decks[3]->lastaccess + ($flashcard->deck4_delay * HOURSECS) - time()) / DAYSECS));
            $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
            for($i = 0 ; $i < min($dayslateness, floor($flashcard->deck4_delay / 24)) ; $i++){
                $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/clock.png\" valign=\"bottom\" />";
            }
            if ($dayslateness < $flashcard->deck4_delay / 24){
                for(; $i < $flashcard->deck4_delay / 24 ; $i++){
                    $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/shadowclock.png\" valign=\"bottom\" />";
                }
            } elseif ($dayslateness > $flashcard->deck4_delay / 24){
                for($i = 0; $i < min($dayslateness - floor($flashcard->deck4_delay / 24), $flashcard->deck4_release / 24) ; $i++){
                    $str .= "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/overtime.png\" valign=\"bottom\" />";
                }
            }
            $str .= '</td></tr></table>';
        } else {
            $str .= "<div height=\"12px\" align=\"top\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topempty.png\" /></div>";
        }
    }
    $str .= '</td></tr></table><br/>';
    
    $options['id']      = $flashcard->cm->id;
    $options['view']    = 'summary';
    $options['what']    = 'reset';
    $options['userid']  = $userid;
    $str .= $OUTPUT->single_button(new moodle_url("view.php", $options), get_string('reset'), 'get');

    if ($return)
        return $str;    
    echo $str;
}

/**
* prints some statistic counters about decks
* @param reference $flashcard
* @param boolean $return
* @param int $userid
* @uses $USER
* @uses $CFG
* @uses $DB
*/
function flashcard_print_deckcounts($flashcard, $return, $userid = 0){
    global $USER, $CFG, $DB;
    
    if ($userid == 0) $userid = $USER->id;

    $sql = "
        SELECT 
            MIN(accesscount) AS minaccess,
            MAX(accesscount) AS maxaccess,
            AVG(accesscount) AS avgaccess,
            SUM(accesscount) AS sumaccess
        FROM
            {flashcard_card}
        WHERE
            flashcardid = ? AND
            userid = ?
    ";
    
    $rec = $DB->get_record_sql($sql, array($flashcard->id, $userid));
    
    $strminaccess = get_string('minaccess', 'flashcard');
    $strmaxaccess = get_string('maxaccess', 'flashcard');
    $stravgaccess = get_string('avgaccess', 'flashcard');
    $strsumaccess = get_string('sumaccess', 'flashcard');
    
    $str = "<table><tr valign=\"top\"><td class=\"smalltext\"><b>$strminaccess</b>:</td>";
    $str .= "<td class=\"smalltext\">{$rec->minaccess}</td></tr>";
    $str .= "<tr valign=\"top\"><td class=\"smalltext\"><b>$strmaxaccess</b>:</td>";
    $str .= "<td class=\"smalltext\">{$rec->maxaccess}</td></tr>";
    $str .= "<tr valign=\"top\"><td class=\"smalltext\"><b>$stravgaccess</b>:</td>";
    $str .= "<td class=\"smalltext\">{$rec->avgaccess}</td></tr>";
    $str .= "<tr valign=\"top\"><td class=\"smalltext\"><b>$strsumaccess</b>:</td>";
    $str .= "<td class=\"smalltext\">{$rec->sumaccess}</td></tr></table>";

    if ($return)
        return $str;    
    echo $str;
}

/**
* prints an image on card side.
* @param reference $flashcard the flashcard object
* @param string $imagename
* @param boolean $return
* @uses $CFG
* @uses $COURSE
*/
function flashcard_print_image(&$flashcard, $imagefileid, $return = false){
    global $CFG, $COURSE;

    $strmissingimage = get_string('missingimage', 'flashcard');

    $fs = get_file_storage();
    
    if (!$imagefile = $fs->get_file_by_id($imagefileid)){
        return "<span class=\"error\">$strmissingimage</span>";
    }
    
    $filename = $soundfile->get_filename();
    $contextid = $soundfile->get_contextid();
    $filearea = $soundfile->get_filearea();
    $itemid = $soundfile->get_itemid();
        
    $magic = rand(0,100000);
    if ($htmlname == '') $htmlname = "bell_{$magic}";
    
    $imagefileurl = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";

    $imagehtml = "<img src=\"{$imagefileurl}\" />";

    if (!$return) echo $imagehtml;
    return $imagehtml;
}

/**
* plays a soundcard 
* @param reference $flashcard
* @param string $soundname the local name of the sound file. Should be wav or any playable sound format.
* @param string $autostart if 'true' the sound starts playing immediately
* @param boolean $return if true returns the html string
* @uses $CFG
* @uses $COURSE
*/
function flashcard_play_sound(&$flashcard, $soundfileid, $autostart = 'false', $return = false, $htmlname = ''){
    global $CFG, $COURSE;

    $strmissingsound = get_string('missingsound', 'flashcard');
    
    $fs = get_file_storage();
    
    if (!$soundfile = $fs->get_file_by_id($soundfileid)){
        return "<span class=\"error\">$strmissingsound</span>";
    }
    
    $filename = $soundfile->get_filename();
    $contextid = $soundfile->get_contextid();
    $filearea = $soundfile->get_filearea();
    $itemid = $soundfile->get_itemid();
        
    $magic = rand(0,100000);
    if ($htmlname == '') $htmlname = "bell_{$magic}";
    
    $soundfileurl = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";
    
    if (!preg_match('/\.mp3$/i', $filename)){
        $soundhtml = "<embed src=\"{$soundfileurl}\" autostart=\"{$autostart}\" hidden=\"false\" id=\"{$htmlname}\" height=\"20\" width=\"200\" />";
        $soundhtml = "<a href=\"{$soundfileurl}\" autostart=\"{$autostart}\" hidden=\"false\" id=\"{$htmlname}\" height=\"20\" width=\"200\" />";
    } else {
		$soundhtml = flashcard_mp3_player($flashcard, $soundfileurl);
    }

    if (!$return) echo $soundhtml;
    return $soundhtml;
}

function flashcard_get_file_url($filerecid, $asobject = false){
	global $CFG;
	
	$fs = get_file_storage();

    $file = $fs->get_file_by_id($filerecid);
    $filename = $file->get_filename();
    $contextid = $file->get_contextid();
    $filearea = $file->get_filearea();
    $itemid = $file->get_itemid();
        
    $url = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";
    
    if ($asobject){
	    $f->pathname = $file->get_pathname();    	
	    $f->filename = $filename;
	    $f->url = $url;
	    return $f;
    }
    
    return $url;
}