<?php

/**
* internal library of functions and constants for module flashcard
* @package mod-flashcard
* @category mod
* @author Gustav Delius
* @contributors Valery Fremaux
*/

/**
* Includes and requires
*/

/**
*
*/
define('FLASHCARD_MEDIA_TEXT', 0); 
define('FLASHCARD_MEDIA_IMAGE', 1); 
define('FLASHCARD_MEDIA_SOUND', 2); 
define('FLASHCARD_MEDIA_IMAGE_AND_SOUND', 3); 

/**
* computes the last accessed date for a deck as the oldest card being in the deck
* @param reference $flashcard the flashcard object
* @param int $deck the deck number
* @param int $userid the user the deck belongs to
* @uses $USER for setting default user
* @uses $CFG
*/
function flashcard_get_lastaccessed(&$flashcard, $deck, $userid = 0){
    global $USER, $CFG;
    
    if ($userid == 0) $userid = $USER->id;
    
    $sql = "
        SELECT 
            MIN(lastaccessed) as lastaccessed
        FROM
            {$CFG->prefix}flashcard_card
        WHERE
            flashcardid = {$flashcard->id} AND
            userid = {$userid} AND
            deck = {$deck}
    ";
    $rec = get_record_sql($sql);
    return $rec->lastaccessed;
}

/**
* prints a deck depending on deck status
* @param reference $cm the coursemodule
* @param int $deck the deck number
* @uses $CFG
*/
function flashcard_print_deck(&$cm, $deck){
    global $CFG;
    
    if ($deck == 0){
        echo "<img src=\"{$CFG->wwwroot}/mod/flashcard/pix/emptydeck.jpg\"/>";
    }

    if ($deck > 0){
        echo "<a href=\"view.php?view=play&amp;id={$cm->id}&amp;deck={$deck}&amp;what=initialize\" title=\"".get_string('playwithme', 'flashcard')."\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/enableddeck.jpg\"/></a>";
    }

    if ($deck < 0){
        $deck = -$deck;
        echo "<a href=\"view.php?view=play&amp;id={$cm->id}&amp;deck={$deck}&amp;what=initialize\" title=\"".get_string('reinforce', 'flashcard')."\"><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/disableddeck.jpg\"/></a>";
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
    global $CFG;

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
    
    $str .= "</td><td><img src=\"{$CFG->pixpath}/a/r_breadcrumb.gif\"></td><td width=\"30%\" align=\"center\">";

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
        $str .= "</td><td><img src=\"{$CFG->pixpath}/a/r_breadcrumb.gif\"></td><td width=\"30%\" align=\"center\">";

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
        $str .= "</td><td><img src=\"{$CFG->pixpath}/a/r_breadcrumb.gif\"></td><td width=\"30%\" align=\"center\">";

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
    $str .= print_single_button("view.php", $options, get_string('reset'), 'get', '_self', true);

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
*/
function flashcard_print_deckcounts($flashcard, $return, $userid = 0){
    global $USER, $CFG;
    
    if ($userid == 0) $userid = $USER->id;

    $sql = "
        SELECT 
            MIN(accesscount) AS minaccess,
            MAX(accesscount) AS maxaccess,
            AVG(accesscount) AS avgaccess,
            SUM(accesscount) AS sumaccess
        FROM
            {$CFG->prefix}flashcard_card
        WHERE
            flashcardid = $flashcard->id AND
            userid = $userid
    ";
    
    $rec = get_record_sql($sql);
    
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
function flashcard_print_image(&$flashcard, $imagename, $return = false){
    global $CFG, $COURSE;
    
    $strmissingimage = get_string('missingimage', 'flashcard');
    if (empty($imagename)) return $strmissingimage;

    $imagepath = ($CFG->slasharguments) ? "/{$COURSE->id}/{$imagename}" : "?file=/{$COURSE->id}/{$imagename}" ; 
    if (file_exists($CFG->dataroot."/{$COURSE->id}/{$imagename}")){
        $imagehtml = "<img src=\"{$CFG->wwwroot}/file.php{$imagepath}\" />";
    } else {
        $imagehtml = "<span class=\"error\">$strmissingimage</span>";
    }
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
function flashcard_play_sound(&$flashcard, $soundname, $autostart = 'false', $return = false, $htmlname = ''){
    global $CFG, $COURSE;
    static $id = 0;
    
    $as = ($autostart == 'false') ? 0 : 1 ;

    $strmissingsound = get_string('missingsound', 'flashcard');
    if (empty($soundname)) return $strmissingsound;
    
    $magic = rand(0,100000);
    if ($htmlname == '') $htmlname = "bell_{$magic}";

    $soundpath = ($CFG->slasharguments) ? "/{$COURSE->id}/{$soundname}" : "?file=/{$COURSE->id}/{$soundname}" ; 
    if (file_exists($CFG->dataroot."/{$COURSE->id}/{$soundname}")){
        if (!preg_match('/\.mp3$/i', $soundname)){
            $soundhtml = "<embed src=\"{$CFG->wwwroot}/file.php{$soundpath}\" autostart=\"$autostart\" hidden=\"false\" id=\"{$htmlname}\" height=\"20\" width=\"200\" />";
        } else {
			$soundhtml = flashcard_mp3_player($flashcard, $CFG->wwwroot.'/file.php'.$soundpath);
			/*
            $soundhtml = '<object type="application/x-shockwave-flash" id="'.$htmlname.'" data="dewplayers/dewplayer.swf?mp3='.$CFG->wwwroot.'/file.php'.$soundpath.'&amp;autostart='.$as.'" width="200" height="20">
                          <param name="wmode" value="transparent" />
                          <param name="movie" value="dewplayers/dewplayer.swf?mp3='.$CFG->wwwroot.'/file.php'.$soundpath.'&amp;autostart='.$as.'" />
                          </object>';
            */
        }
    } else {
        $soundhtml = "<span class=\"error\">$strmissingsound</span><br/>".$CFG->dataroot."/{$COURSE->id}/{$soundname}";
    }
    if (!$return) echo $soundhtml;
    return $soundhtml;
}

/**
* initialize decks for a given user. The initialization is soft as it will 
* be able to add new subquestions
* @param reference $flashcard
* @param int $userid
*/
function flashcard_initialize(&$flashcard, $userid){
    
    // get all cards (all decks)
    $cards = get_records_select('flashcard_card', "flashcardid = {$flashcard->id} AND userid = {$userid}");
    $registered = array();
    if (!empty($cards)){
        foreach($cards as $card){
            $registered[] = $card->entryid;
        }
    }

    // get all subquestions
    if ($subquestions = get_records('flashcard_deckdata', 'flashcardid', $flashcard->id, '', 'id,id')){
        foreach($subquestions as $subquestion){
            if (in_array($subquestion->id, $registered)) continue;
            $card->userid = $userid;
            $card->flashcardid = $flashcard->id;
            $card->lastaccessed = time() - ($flashcard->deck1_delay * HOURSECS);
            $card->deck = 1;
            $card->entryid = $subquestion->id;
            if (! insert_record('flashcard_card', $card)){
                error("Could not insert card");
            }
        }
    } else {
        return false;
    }

    return true;
}

/**
* imports data into the deck from a matching question. This allows making a quiz with questions
* then importing data to form a card deck.
* @param reference $flashcard
* @return void
*/
function flashcard_import(&$flashcard){
	$question = get_record('question', 'id', $flashcard->questionid);
	
	if ($question->qtype != 'match'){
	    notice("Not a match question. Internal error");
	    return;
	}

    $options = get_record('question_match', 'question', $question->id);
    $subquestionIds = str_replace(",", "','", $options->subquestions);
    if ($subquestions = get_records_select('question_match_sub', "id IN ('$subquestionIds') AND answertext != '' AND questiontext != ''")){
        
        // cleanup the flashcard
        delete_records('flashcard_card', 'flashcardid', $flashcard->id);
        delete_records('flashcard_deckdata', 'flashcardid', $flashcard->id);
        
        // transfer data
        foreach($subquestions as $subquestion){
            $deckdata->flashcardid = $flashcard->id;
            $deckdata->questiontext = $subquestion->questiontext;
            $deckdata->answertext = $subquestion->answertext;
            $deckdata->lastaccessed = 0;
            insert_record('flashcard_deckdata', $deckdata); 
        }
    }
    return true;
}

/**
* get count, last access time and reactivability for all decks
* @param reference $flashcard
* @param int $userid
* @uses $USER
*/
function flashcard_get_deck_status(&$flashcard, $userid = 0){
    global $USER;
    
    if ($userid == 0) $userid = $USER->id;
    
    unset($status);
    
    $dk3 = 0;
    $dk4 = 0;
    $dk1 = count_records('flashcard_card', 'flashcardid', $flashcard->id, 'userid', $userid, 'deck', 1);
    $status->decks[0]->count = $dk1;
    $dk2 = count_records('flashcard_card', 'flashcardid', $flashcard->id, 'userid', $userid, 'deck', 2);
    $status->decks[1]->count = $dk2;
    if ($flashcard->decks >= 3){
        $dk3 = count_records('flashcard_card', 'flashcardid', $flashcard->id, 'userid', $userid, 'deck', 3);
        $status->decks[2]->count = $dk3;
    }
    if ($flashcard->decks >= 4){
        $dk4 = count_records('flashcard_card', 'flashcardid', $flashcard->id, 'userid', $userid, 'deck', 4);
        $status->decks[3]->count = $dk4;
    }
    
    // not initialized for this user
    if ($dk1 + $dk2 + $dk3 + $dk4 == 0){
        return null;
    }

    if ($dk1 > 0){
        $status->decks[0]->lastaccess = flashcard_get_lastaccessed($flashcard, 1, $userid);
        $status->decks[0]->reactivate = (time() > ($status->decks[0]->lastaccess + $flashcard->deck1_delay * HOURSECS));
    }
    if ($dk2 > 0){
        $status->decks[1]->lastaccess = flashcard_get_lastaccessed($flashcard, 2, $userid);
        $status->decks[1]->reactivate = (time() > ($status->decks[1]->lastaccess + $flashcard->deck2_delay * HOURSECS));
    }
    if ($flashcard->decks >= 3 && $dk3 > 0){
        $status->decks[2]->lastaccess = flashcard_get_lastaccessed($flashcard, 3, $userid);
        $status->decks[2]->reactivate = (time() > ($status->decks[2]->lastaccess + $flashcard->deck3_delay * HOURSECS));
    }
    if ($flashcard->decks >= 4 && $dk4 > 0){
        $status->decks[3]->lastaccess = flashcard_get_lastaccessed($flashcard, 4, $userid);
        $status->decks[3]->reactivate = (time() > ($status->decks[3]->lastaccess + $flashcard->deck4_delay));
    }
       
    return $status;
}

/**
* get card status structure
* @param reference $flashcard
* @uses $CFG
*/
function flashcard_get_card_status(&$flashcard){
    global $CFG;
    
    // get decks by card
    $sql = "
        SELECT
           dd.questiontext,
           COUNT(c.id) as amount,
           c.deck AS deck
        FROM
            {$CFG->prefix}flashcard_deckdata dd
        LEFT JOIN
            {$CFG->prefix}flashcard_card c
        ON 
            c.entryid = dd.id
        WHERE
            c.flashcardid = {$flashcard->id}
        GROUP BY
            c.entryid,
            c.deck
    ";
    $recs = get_records_sql($sql);

    // get accessed by card
    $sql = "
        SELECT
           dd.questiontext,
           SUM(accesscount) AS accessed
        FROM
            {$CFG->prefix}flashcard_deckdata dd
        LEFT JOIN
            {$CFG->prefix}flashcard_card c
        ON 
            c.entryid = dd.id
        WHERE
            c.flashcardid = {$flashcard->id}
        GROUP BY
            c.entryid
    ";
    $accesses = get_records_sql($sql);
    
    $cards = array();
    foreach($recs as $question => $rec){
        if ($rec->deck == 1)
            $cards[$question]->deck[0] = $rec->amount;
        if ($rec->deck == 2)
            $cards[$question]->deck[1] = $rec->amount;
        if ($rec->deck == 3)
            $cards[$question]->deck[2] = $rec->amount;
        if ($rec->deck == 4)
            $cards[$question]->deck[3] = $rec->amount;
        $cards[$question]->accesscount = $accesses[$question]->accessed;
    }
    return $cards;
}

/**
* prints a graphical represnetation of decks, proportionnaly to card count
* @param reference $flashcard
* @param object $card
* @param boolean $return
* @uses $CFG
*/
function flashcard_print_cardcounts(&$flashcard, $card, $return=false){
    global $CFG;
    
    $str = '';
    
    $strs[] = "<td><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topenabled.png\" /> (1) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[0]).'px"></div></td>';
    $strs[] = "<td><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topenabled.png\" /> (2) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[1]).'px"></div></td>';
    if ($flashcard->decks >= 3){
        $strs[] = "<td><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topenabled.png\" /> (3) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[2]).'px"></div></td>';
    }
    if ($flashcard->decks >= 4){
        $strs[] = "<td><img src=\"{$CFG->wwwroot}/mod/flashcard/pix/topenabled.png\" /> (4) </td><td>".'<div class="bar" style="height: 10px; width: '.(1 + @$card->deck[3]).'px"></div></td>';
    }
    
    $str = "<table cellspacing=\"2\"><tr valign\"middle\">".implode("</tr><tr valign=\"middle\">", $strs)."</tr></table>";
    
    if ($return) return $str;
    echo $str;
}

function flashcard_mp3_player(&$flashcard, $url) {
    global $CFG, $THEME;

    if (!empty($THEME->filter_mediaplugin_colors)) {
        $c = $THEME->filter_mediaplugin_colors;   // You can set this up in your theme/xxx/config.php
        if($flashcard->audiostart){
        	$c = str_replace($c, 'waitForPlay=yes', 'waitForPlay=no&autoPlay=yes');
        }
    } else {
    	$audiostart = ($flashcard->audiostart) ? 'no' : 'yes&autoPlay=yes' ;
        $c = 'bgColour=000000&btnColour=ffffff&btnBorderColour=cccccc&iconColour=000000&'.
             'iconOverColour=00cc00&trackColour=cccccc&handleColour=ffffff&loaderColour=ffffff&'.
             'waitForPlay='.$audiostart;
    }

    static $count = 0;
    $count++;
    $id = 'flashcard_filter_mp3_'.time().$count; //we need something unique because it might be stored in text cache

    $url = addslashes_js($url);

    return '<span class="mediaplugin mediaplugin_mp3" id="'.$id.'">('.get_string('mp3audio', 'mediaplugin').')</span>
<script type="text/javascript">
//<![CDATA[
  var FO = { movie:"'.$CFG->wwwroot.'/filter/mediaplugin/mp3player.swf?src='.$url.'",
    width:"90", height:"15", majorversion:"6", build:"40", flashvars:"'.$c.'", quality: "high" };
  UFO.create(FO, "'.$id.'");
//]]>
</script>';
}

?>