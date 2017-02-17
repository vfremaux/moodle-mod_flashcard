<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     mod_flashcard
 * @category    mod
 * @author      Gustav Delius
 * @author      Tomas Muraz
 * @author      Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * Master renderer
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/repository/lib.php');

class mod_flashcard_renderer extends plugin_renderer_base {

    public function filepicker($elname, $value, $contextid, $filearea, $cardid, $maxbytes, $acceptedtypes = '*') {
        global $COURSE, $PAGE, $USER;

        $str = '';

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();

        // No existing area info provided - let's use fresh new draft area.
        if ($value) {
            $draftitemid = file_get_submitted_draft_itemid($filearea);
            $maxbytes = 100000;
            $options = array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1);
            file_prepare_draft_area($draftitemid, $contextid, 'mod_flashcard', $filearea, $cardid, $options);
        } else {
            $draftitemid = file_get_unused_draft_itemid();
        }

        if ($COURSE->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($COURSE->id);
        }

        $args = new stdClass();
        // Need these three to filter repositories list.
        $args->accepted_types = $acceptedtypes;
        $args->return_types = FILE_INTERNAL;
        $args->itemid = $draftitemid;
        $args->maxbytes = $maxbytes;
        $args->buttonname = $elname.'choose';
        $args->elementname = $elname;
        $id = $elname;

        $fp = new file_picker($args);
        $options = $fp->options;
        $options->context = $PAGE->context;
        $str .= $this->output->render($fp);
        $str .= '<input type="hidden" name="'.$elname.'" id="'.$id.'" value="'.$draftitemid.'" class="filepickerhidden"/>';

        $module = array(
            'name' => 'form_filepicker',
            'fullpath' => '/lib/form/filepicker.js',
            'requires' => array('core_filepicker', 'node', 'node-event-simulate')
        );

        $PAGE->requires->js_init_call('M.form_filepicker.init', array($fp->options), true, $module);

        $params = array(
            'env' => 'filepicker',
            'action' => 'browse',
            'itemid' => $draftitemid,
            'subdirs' => 0,
            'maxbytes' => $options->maxbytes,
            'maxfiles' => 1,
            'ctx_id' => $PAGE->context->id,
            'course' => $PAGE->course->id,
            'sesskey' => sesskey(),
        );
        $nonjsfilepicker = new moodle_url('/repository/draftfiles_manager.php', $params);

        // Non js file picker.
        $str .= '<noscript>';
        $str .= '<div>';
        $str .= '<object type="text/html" data="'.$nonjsfilepicker.'" height="160" width="600" style="border:1px solid #000">';
        $str .= '</object>';
        $str .= '</div>';
        $str .= '</noscript>';

        return $str;
    }

    /**
     * prints a deck depending on deck status
     * @param reference $cm the coursemodule
     * @param int $deck the deck number
     */
    public function print_deck(&$flashcard, &$cm, $deck) {

        $str = '';

        $emptydeckurl = $this->output->pix_url('emptydeck', 'flashcard');
        if (!empty($flashcard->customreviewemptyfileid)) {
            $emptydeckurl = flashcard_get_file_url($flashcard->customreviewemptyfileid);
        }

        $decktoreviewurl = $this->output->pix_url('enableddeck', 'flashcard');
        if (!empty($flashcard->customreviewfileid)) {
            $decktoreviewurl = flashcard_get_file_url($flashcard->customreviewfileid);
        }

        $deckreviewedurl = $this->output->pix_url('disableddeck', 'flashcard');
        if (!empty($flashcard->customreviewedfileid)) {
            $deckreviewedurl = flashcard_get_file_url($flashcard->customreviewedfileid);
        }

        if ($deck == 0) {
            $str .= '<img src="'.$emptydeckurl.'"/>';
        }

        if ($deck > 0) {
            $params = array('view' => 'play', 'id' => $cm->id, 'deck' => $deck, 'what' => 'initialize');
            $linkurl = new moodle_url('/mod/flashcard/view.php', $params);
            $img = '<img src="'.$decktoreviewurl.'"/>';
            $str .= '<a href="'.$linkurl.'" title="'.get_string('playwithme', 'flashcard').'">'.$img.'</a>';
        }

        if ($deck < 0) {
            $deck = -$deck;
            $params = array('view' => 'play', 'id' => $cm->id, 'deck' => $deck, 'what' => 'initialize');
            $linkurl = new moodle_url('/mod/flashcard/view.php', $params);
            $img = '<img src="'.$deckreviewedurl.'"/>';
            $str .= '<a href="'.$linkurl.'" title="'.get_string('reinforce', 'flashcard').'">'.$img.'</a>';
        }

        return $str;
    }

    /**
     * prints the deck status for use in teacher's overview
     * @param reference $flashcard the flashcard object
     * @param int $userid the user for which printing status
     * @param object $status a status object to be filled by the function
     */
    public function print_deck_status(&$flashcard, $userid, &$status) {

        $str = '';

        $str = '<table width="100%">';
        $str .= '<tr valign="bottom">';
        $str .= '<td width="30%" align="center">';

        // Print for deck 1.
        if ($status->decks[0]->count) {
            $image = ($status->decks[0]->reactivate) ? 'topenabled' : 'topdisabled';
            $height = $status->decks[0]->count * 3;
            $str .= '<table>';
            $str .= '<tr><td>';
            $str .= '<div style="padding-bottom: '.$height.'px" class="graphdeck" align="top">';
            $pixurl = $this->output->pix_url($image, 'flashcard');
            $title = get_string('cardsindeck', 'flashcard', $status->decks[0]->count);
            $str .= '<img src="'.$pixurl.'" title="'.$title.'"/>';
            $str .= '</div>';
            $str .= '</td>';

            $str .= '<td>';
            $dayslateness = floor((time() - $status->decks[0]->lastaccess) / DAYSECS);

            $timetoreview = round(max(0, ($status->decks[0]->lastaccess + ($flashcard->deck1_delay * HOURSECS) - time()) / DAYSECS));
            $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
            for ($i = 0; $i < min($dayslateness, floor($flashcard->deck1_delay / 24)); $i++) {
                $pixurl = $this->output->pix_url('clock', 'flashcard');
                $str .= '<img src="'.$pixurl.'" valign="bottom" title="'.$strtimetoreview.'" />';
            }
            if ($dayslateness < $flashcard->deck1_delay / 24) {
                for (; $i < $flashcard->deck1_delay / 24; $i++) {
                    $pixurl = $this->output->pix_url('shadowclock', 'flashcard');
                    $str .= '<img src="'.$pixurl.'" valign="bottom" title="'.$strtimetoreview.'" />';
                }
            } else if ($dayslateness > $flashcard->deck1_delay / 24) {
                // Deck 1 has no release limit as cards can stay here as long as not viewed.
                for ($i = 0; $i < min($dayslateness - floor($flashcard->deck1_delay / 24), 4); $i++) {
                    $pixurl = $this->output->pix_url('overtime', 'flashcard');
                    $str .= '<img src="'.$pixurl.'" valign="bottom" title="'.$strtimetoreview.'" />';
                }
            }
            $str .= '</td>';
            $str .= '</tr>';
            $str .= '</table>';
        } else {
            $str .= '<div height="12px" align="top">';
            $pixurl = $this->output->pix_url('topempty', 'flashcard');
            $str .= '<img src="'.$pixurl.'" />';
            $str .= '</div>';
        }

        $str .= '</td>';
        $str .= '<td>'. $this->output->pix_icon('a/r_breadcrumb', 'right breadcrumb icon').'</td>';
        $str .= '<td width="30%" align="center">';

        // Print for deck 2.
        if ($status->decks[1]->count) {
            $image = ($status->decks[1]->reactivate) ? 'topenabled' : 'topdisabled';
            $height = $status->decks[1]->count * 3;
            $str .= '<table>';
            $str .= '<tr>';
            $str .= '<td>';
            $str .= '<div style="padding-bottom: '.$height.'px" class="graphdeck" align="top">';
            $pixurl = $this->output->pix_url($image, 'flashcard');
            $title = get_string('cardsindeck', 'flashcard', $status->decks[1]->count);
            $str .= '<img src="'.$pixurl.'" title="'.$title.'"/>';
            $str .= '</div>';
            $str .= '</td>';
            $str .= '<td>';
            $dayslateness = floor((time() - $status->decks[1]->lastaccess) / DAYSECS);
            $timetoreview = round(max(0, ($status->decks[1]->lastaccess + ($flashcard->deck2_delay * HOURSECS) - time()) / DAYSECS));
            $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
            for ($i = 0; $i < min($dayslateness, floor($flashcard->deck2_delay / 24)); $i++) {
                $pixurl = $this->output->pix_url('clock', 'flashcard');
                $str .= '<img src="'.$pixurl.'" valign="bottom" title="'.$strtimetoreview.'" />';
            }
            if ($dayslateness < $flashcard->deck2_delay / 24) {
                for (; $i < $flashcard->deck2_delay / 24; $i++) {
                    $pixurl = $this->output->pixurl('shadowclock', 'flashcard');
                    $str .= '<img src="'.$pixurl.'" valign="bottom" title="'.$strtimetoreview.'" />';
                }
            } else if ($dayslateness > $flashcard->deck2_delay / 24) {
                for ($i = 0; $i < min($dayslateness - floor($flashcard->deck2_delay / 24), $flashcard->deck2_release / 24); $i++) {
                    $pixurl = $this->output->pixurl('overtime', 'flashcard');
                    $str .= '<img src="'.$pixurl.'" valign="bottom" />';
                }
            }
            $str .= '</td>';
            $str .= '</tr>';
            $str .= '</table>';
        } else {
            $str .= '<div height="12px" align="top">';
            $pixurl = $this->output->pix_url('topempty', 'flashcard');
            $str .= '<img src="'.$pixurl.'" />';
            $str .= '</div>';
        }

        if ($flashcard->decks >= 3) {
            $str .= '</td>';
            $str .= '<td>'.$this->output->pix_icon('a/r_breadcrumb', 'right breadcrumb icon').'</td>';
            $str .= '<td width="30%" align="center">';

            // Print for deck 3.
            if ($status->decks[2]->count) {
                $image = ($status->decks[2]->reactivate) ? 'topenabled' : 'topdisabled';
                $height = $status->decks[2]->count * 3;
                $str .= '<table>';
                $str .= '<tr>';

                $str .= '<td>';
                $str .= '<div style="padding-bottom: '.$height.'px" class="graphdeck" align="top">';
                $pixurl = $this->output->pix_url($image, 'flashcard');
                $title = get_string('cardsindeck', 'flashcard', $status->decks[2]->count);
                $str .= '<img src="'.$pixurl.'" title="'.$title.'"/>';
                $str .= '</div>';
                $str .= '</td>';

                $str .= '<td>';
                $dayslateness = floor((time() - $status->decks[2]->lastaccess) / DAYSECS);
                $timetoreview = round(max(0, ($status->decks[2]->lastaccess + ($flashcard->deck3_delay * HOURSECS) - time()) / DAYSECS));
                $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
                $pixurl = $this->output->pix_url('clock', 'flashcard');
                for ($i = 0; $i < min($dayslateness, floor($flashcard->deck3_delay / 24)); $i++) {
                    $str .= '<img src="'.$pixurl.'" valign="bottom" />';
                }
                if ($dayslateness < $flashcard->deck3_delay / 24) {
                    for (; $i < $flashcard->deck3_delay / 24; $i++) {
                        $pixurl = $this->output->pix_url('shadowclock', 'flashcard');
                        $str .= '<img src="'.$pixurl.'" valign="bottom" title="'.$strtimetoreview.'" />';
                    }
                } else if ($dayslateness > $flashcard->deck3_delay / 24) {
                    $pixurl = $this->output->pix_url('overtime', 'flashcard');
                    for ($i = 0; $i < min($dayslateness - floor($flashcard->deck3_delay / 24), $flashcard->deck3_release / 24); $i++) {
                        $str .= '<img src="'.$pixurl.'" valign="bottom" />';
                    }
                }
                $str .= '</td>';
                $str .= '</tr>';
                $str .= '</table>';
            } else {
                $str .= '<div height="12px" align="top">';
                $pixurl = $this->output->pix_url('topempty', 'flashcard');
                $str .= '<img src="'.$pixurl.'"  title="'.$strtimetoreview.'" />';
                $str .= '</div>';
            }
        }
        if ($flashcard->decks >= 4) {
            $str .= '</td>';
            $str .= '<td>'.$this->output->pix_icon('a/r_breadcrumb', 'right breadcrumb icon').'</td>';
            $str .= '<td width="30%" align="center">';

            // Print for deck 4.
            if ($status->decks[3]->count) {
                $image = ($status->decks[3]->reactivate) ? 'topenabled' : 'topdisabled';
                $height = $status->decks[3]->count * 3;
                $str .= '<table>';
                $str .= '<tr>';
                $str .= '<td>';
                $str .= '<div style="padding-bottom: '.$height.'px" class="graphdeck" align="top">';
                $pixurl = $this->output->pixurl($image, 'flashcard');
                $title = get_string('cardsindeck', 'flashcard', $status->decks[3]->count);
                $str .= '<img src="'.$pixurl.'" title="'.$title.'"/>';
                $str .= '</div>';
                $str .= '</td>';
                $str .= '<td>';

                $dayslateness = floor((time() - $status->decks[3]->lastaccess) / DAYSECS);
                $timetoreview = round(max(0, ($status->decks[3]->lastaccess + ($flashcard->deck4_delay * HOURSECS) - time()) / DAYSECS));
                $strtimetoreview = get_string('timetoreview', 'flashcard', $timetoreview);
                $pixurl = $this->output->pix_url('clock', 'flashcard');
                for ($i = 0; $i < min($dayslateness, floor($flashcard->deck4_delay / 24)); $i++) {
                    $str .= '<img src="'.$pixurl.'" valign="bottom" />';
                }
                if ($dayslateness < $flashcard->deck4_delay / 24) {
                    $pixurl = $this->output->pix_url('shadowclock', 'flashcard');
                    for (; $i < $flashcard->deck4_delay / 24; $i++) {
                        $str .= '<img src="'.$pixurl.'" valign="bottom" />';
                    }
                } else if ($dayslateness > $flashcard->deck4_delay / 24) {
                    $pixurl = $this->output->pix_url('overtime', 'flashcard');
                    for ($i = 0; $i < min($dayslateness - floor($flashcard->deck4_delay / 24), $flashcard->deck4_release / 24); $i++) {
                        $str .= '<img src="'.$pixurl.'" valign="bottom" />';
                    }
                }
                $str .= '</td>';
                $str .= '</tr>';
                $str .= '</table>';
            } else {
                $str .= '<div height="12px" align="top">';
                $str .= '<img src="'.$this->output->pix_url('topempty', 'flashcard').'" />';
                $str .= '</div>';
            }
        }
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '<br/>';

        $options['id'] = $flashcard->cm->id;
        $options['view'] = 'summary';
        $options['what'] = 'reset';
        $options['userid'] = $userid;
        $str .= $this->output->single_button(new moodle_url('/mod/flashcard/view.php', $options), get_string('reset'), 'get');

        return $str;
    }

    /**
     * prints some statistic counters about decks
     * @param reference $flashcard
     * @param int $userid
     * @uses $USER
     * @uses $DB
     */
    public function print_deckcounts($flashcard, $userid = 0) {
        global $USER, $DB;

        if ($userid == 0) {
            $userid = $USER->id;
        }

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

        $str = '<table><tr valign="top"><td class="smalltext"><b>'.$strminaccess.'</b>:</td>';
        $str .= '<td class="smalltext">'.$rec->minaccess.'</td></tr>';
        $str .= '<tr valign="top"><td class="smalltext"><b>'.$strmaxaccess.'</b>:</td>';
        $str .= '<td class="smalltext">'.$rec->maxaccess.'</td></tr>';
        $str .= '<tr valign="top"><td class="smalltext"><b>'.$stravgaccess.'</b>:</td>';
        $str .= '<td class="smalltext">'.$rec->avgaccess.'</td></tr>';
        $str .= '<tr valign="top"><td class="smalltext"><b>'.$strsumaccess.'</b>:</td>';
        $str .= '<td class="smalltext">'.$rec->sumaccess.'</td></tr></table>';

        return $str;
    }

    /**
     * prints an image on card side.
     * @param reference $flashcard the flashcard object
     * @param string $imagename
     * @param boolean $return
     * @uses $CFG
     * @uses $COURSE
     */
    public function print_image(&$flashcard, $imagefileid) {
        global $CFG;

        $strmissingimage = get_string('missingimage', 'flashcard');

        $fs = get_file_storage();

        // New way : probably no effective fielids storage needed anymore.
        $cm = get_coursemodule_from_instance('flashcard', $flashcard->id);
        $context = context_module::instance($cm->id);
        $contextid = $context->id;
        list($filearea, $itemid) = explode('/', $imagefileid);
        $imagefiles = $fs->get_area_files($context->id, 'mod_flashcard', $filearea, $itemid);

        if (empty($imagefiles)) {
            $imagefileurl = $this->output->pix_url('notfound', 'flashcard');
            $imagehtml = '<img src="'.$imagefileurl.'" width="100%" height="100%" />';
            return $imagehtml;
        }

        $imagefile = array_pop($imagefiles);
        $filename = $imagefile->get_filename();

        $magic = rand(0, 100000);
        if (empty($htmlname)) {
            $htmlname = "bell_{$magic}";
        }

        $imagefileurl = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";

        $imagehtml = '<img src="'.$imagefileurl.'" width="100%" height="100%" />';

        return $imagehtml;
    }

    /**
     * plays a soundcard
     * @param reference $flashcard
     * @param string $soundname the local name of the sound file. Should be wav or any playable sound format.
     * @param string $autostart if 'true' the sound starts playing immediately
     */
    public function play_sound(&$flashcard, $soundfileid, $autostart = 'false', $htmlname = '') {
        global $CFG;

        $strmissingsound = get_string('missingsound', 'flashcard');

        $fs = get_file_storage();

        // New way : probably no effective fieldids storage needed anymore.
        $cm = get_coursemodule_from_instance('flashcard', $flashcard->id);
        $context = context_module::instance($cm->id);
        $contextid = $context->id;
        list($filearea, $itemid) = explode('/', $soundfileid);
        $soundfiles = $fs->get_area_files($context->id, 'mod_flashcard', $filearea, $itemid);

        if (empty($soundfiles)) {
            $soundfileurl = $this->output->pix_url('notfound', 'flashcard');
            $soundhtml = '<img src="'.$soundfileurl.'" />';
            return $soundhtml;
        }

        $soundfile = array_pop($soundfiles);
        $filename = $soundfile->get_filename();

        $magic = rand(0, 100000);
        if ($htmlname == '') {
            $htmlname = "bell_{$magic}";
        }

        $soundfileurl = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";

        if (!preg_match('/\.mp3$/i', $filename)) {
            $soundhtml = "<embed src=\"{$soundfileurl}\"
                                 autostart=\"{$autostart}\"
                                 hidden=\"false\"
                                 id=\"{$htmlname}_player\"
                                 height=\"20\"
                                 width=\"200\" />";
            $soundhtml .= "<a href=\"{$soundfileurl}\"
                              autostart=\"{$autostart}\"
                              hidden=\"false\"
                              id=\"{$htmlname}\"
                              height=\"20\"
                              width=\"200\" />";
        } else {
            $soundhtml = flashcard_mp3_dewplayer($flashcard, $soundfileurl, $htmlname);
        }

        return $soundhtml;
    }

    public function play_video(&$flashcard, $videofileid, $autostart = 'false', $htmlname = '', $thumb = false) {
        global $CFG;

        $strmissingvid = get_string('missingvid', 'flashcard');

        $fs = get_file_storage();

        // New way : probably no effective fieldids storage needed anymore.
        $cm = get_coursemodule_from_instance('flashcard', $flashcard->id);
        $context = context_module::instance($cm->id);
        $contextid = $context->id;
        list($filearea, $itemid) = explode('/', $videofileid);
        $videofiles = $fs->get_area_files($context->id, 'mod_flashcard', $filearea, $itemid);

        if (empty($videofiles)) {
            $videofileurl = $this->output->pix_url('notfound', 'flashcard');
            $videohtml = "<img src=\"{$videofileurl}\" />";
            return $videohtml;
        }

        $videofile = array_pop($videofiles);
        $filename = $videofile->get_filename();
        $parts = pathinfo($filename);
        $videotype = $parts['extension'];

        $magic = rand(0, 100000);

        if ($htmlname == '') {
            $htmlname = "bell_{$magic}";
        }

        $videofileurl = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";

        $videohtml = flashcard_flowplayer($flashcard, $videofileurl, $videotype, $htmlname, $thumb);

        return $videohtml;
    }

    public function print_custom_url(&$flashcard, $filearea, $itemid) {
        global $CFG;

        // New way : probably no effective fieldids storage needed anymore.
        $cm = get_coursemodule_from_instance('flashcard', $flashcard->id);
        $context = context_module::instance($cm->id);
        $contextid = $context->id;

        $fs = get_file_storage();

        $customfiles = $fs->get_area_files($context->id, 'mod_flashcard', $filearea, 0);
        if (empty($customfiles)) {
            return;
        }
        $customfile = array_pop($customfiles);
        $filename = $customfile->get_filename();

        $url = $CFG->wwwroot."/pluginfile.php/{$contextid}/mod_flashcard/{$filearea}/{$itemid}/{$filename}";
        return $url;
    }

    /**
     * prints a graphical represnetation of decks, proportionnaly to card count
     * @param reference $flashcard
     * @param object $card
     */
    public function print_cardcounts(&$flashcard, $card) {
        $str = '';

        $topenabledpixurl = $this->output->pix_url('topenabled', 'flashcard');

        $row = '<td>';
        $row .= '<img src="'.$topenabledpixurl.'" /> (1) </td>';
        $row .= '<td><div class="bar" style="height: 10px; width: '.(1 + @$card->deck[0]).'px"></div></td>';
        $strs[] = $row;

        $row = '<td>';
        $row .= '<img src="'.$topenabledpixurl.'" /> (2) </td>';
        $row .= '<td><div class="bar" style="height: 10px; width: '.(1 + @$card->deck[1]).'px"></div></td>';
        $strs[] = $row;

        if ($flashcard->decks >= 3) {
            $row = '<td><img src="'.$topenabledpixurl.'" /> (3) </td>';
            $row .= '<td><div class="bar" style="height: 10px; width: '.(1 + @$card->deck[2]).'px"></div></td>';
            $strs[] = $row;
        }
        if ($flashcard->decks >= 4) {
            $row = '<td><img src="'.$topenabledpixurl.'" /> (4) </td>';
            $row .= '<td><div class="bar" style="height: 10px; width: '.(1 + @$card->deck[3]).'px"></div></td>';
            $strs[] = $row;
        }

        $str = '<table>';
        $str .= '<tr valign="middle">'.implode('</tr><tr valign="middle">', $strs).'</tr>';
        $str .= '</table>';

        return $str;
    }

    public function playview($flashcard, $cm, $cards) {

        $str = '<script type="text/javascript">
            var qtype = "'.$flashcard->questionsmediatype.'";
            var atype = "'.$flashcard->answersmediatype.'";
            var maxitems = '.count($cards).';
        </script>';

        $str .= '<style>'.$flashcard->extracss.'</style>';

        $str .= '<div id="flashcard-board">';
        $str .= '<div id="flashcard-header">';
        $str .= $this->output->heading($flashcard->name);
        $str .= '<p> '.get_string('instructions', 'flashcard').'</p>';
        $str .= '</div>';

        $str .= '<center>';

        $style = ';background-repeat:no-repeat;background-image:url('.$this->print_custom_url($flashcard, 'customback', 0).');';
        $str .= '<div id="questiondiv"
                      style="'.$style.'"
                      class="flashcard-question'.$qcardvideoclass.'"
                      onclick="javascript:togglecard()">';
        if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE) {
            $str .= $this->print_image($flashcard, "{$back}imagefile/{$subquestion->id}");
        } else if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_SOUND) {
            $str .= $this->play_sound($flashcard, "{$back}soundfile/{$subquestion->id}", $autoplay, 'bell_q');
        } else if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_VIDEO) {
            $str .= $this->play_video($flashcard, "{$back}videofile/{$subquestion->id}", $autoplay, 'bell_q');
        } else if ($flashcard->questionsmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
            $str .= $this->print_image($flashcard, "{$back}imagefile/{$subquestion->id}");
            $str .= '<br/>';
            $str .= $this->play_sound($flashcard, "{$back}soundfile/{$subquestion->id}", $autoplay, 'bell_q');
        } else {
            $str .= format_text($subquestion->questiontext, FORMAT_HTML);
        }
        $str .= '</div>';

        $customurl = $this->print_custom_url($flashcard, 'customfront', 0);
        $str .= '<div id="answerdiv"
                      style="display:none;background-repeat:no-repeat;background-image:url('.$customurl.')"
                      class="flashcard-answer'.$acardvideoclass.'"
                      onclick="javascript:togglecard()">';
        if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE) {
            $str .= $this->print_image($flashcard, "{$front}imagefile/{$subquestion->id}");
        } else if ($flashcard->answersmediatype == FLASHCARD_MEDIA_SOUND) {
            $str .= $this->play_sound($flashcard, "{$front}soundfile/{$subquestion->id}", $autoplay, 'bell_a');
        } else if ($flashcard->answersmediatype == FLASHCARD_MEDIA_VIDEO) {
            $str .= $this->play_video($flashcard, "{$front}videofile/{$subquestion->id}", $autoplay, 'bell_a');
        } else if ($flashcard->answersmediatype == FLASHCARD_MEDIA_IMAGE_AND_SOUND) {
            $str .= $this->print_image($flashcard, "{$front}imagefile/{$subquestion->id}");
            $str .= '<br/>';
            $str .= $this->play_sound($flashcard, "{$front}soundfile/{$subquestion->id}", $autoplay, 'bell_a');
        } else {
            $str .= format_text($subquestion->answertext, FORMAT_HTML);
        }
        $str .= '</div>';

        $str .= '<div id="flashcard-controls">';
        $str .= '<p>'.get_string('cardsremaining', 'flashcard').': <span id="remain">'.count($subquestions).'</span></p>';

        $params = array('id' => $cm->id,
                        'what' => 'igotit',
                        'view' => 'play',
                        'deck' => $deck,
                        'cardid' => $subquestions[$random]->cardid);
        $label = get_string('igotit', 'flashcard');
        $attrs = array('class' => 'flashcard_playbutton');
        $str .= $this->output->single_button(new moodle_url('view.php', $params), $label, 'post', $attrs);

        $params = array('id' => $cm->id,
                        'what' => 'ifailed',
                        'view' => 'play',
                        'deck' => $deck,
                        'cardid' => $subquestions[$random]->cardid);
        $label = get_string('ifailed', 'flashcard');
        $attrs = array('class' => 'flashcard-playbutton');
        $str .= $this->output->single_button(new moodle_url('view.php', $params), $label, 'post', $attrs);

        $str .= '<div class="flashcard-screenlinks">';
        $str .= '<a href="'.$thisurl.'?id='.$cm->id.'&amp;view=checkdecks">'.get_string('backtodecks', 'flashcard').'</a>';
        $courseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        $str .= '- <a href="'.$courseurl.'">'.get_string('backtocourse', 'flashcard').'</a>';
        $str .= '</div>';
        $str .= '</center>';
        $str .= '</div>';

        return $str;
    }

    public function finishtable() {

        $str = '<div id="finished" style="display: none;" class="finished">';
        $str .= '<table width="100%" height="100%">';
        $str .= '<tr>';
        $str .= '<td align="center" valign="middle" class="emptyset">';
        $str .= get_string('emptyset', 'flashcard');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</div>';

        return $str;
    }

    public function freebuttons(&$subquestions) {
        global $COURSE;

        $str = '';

        $str .= '<tr>';
        $str .= '<td width="200px">';
        $str .= '<p>'.get_string('cardsremaining', 'flashcard').': <span id="remain">'.count($subquestions).'</span></p>';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td width="200px">';
        $str .= '<input id="next"
                        type="button"
                        value="'.get_string('next', 'flashcard').'"
                        onclick="javascript:next_card()" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td width="200px">';
        $str .= '<input id="previous"
                        type="button"
                        value="'.get_string('previous', 'flashcard').'"
                        onclick="javascript:previous_card()" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td width="200px">';
        $str .= '<input id="remove"
                        type="button"
                        value="'.get_string('removecard', 'flashcard').'"
                        onclick="javascript:remove_card()" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td width="200px">';
        $str .= '<input type="button"
                        value="'.get_string('reset', 'flashcard').'"
                        onclick="javascript:location.reload()" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td width="200px" align="center" colspan="2">';
        $courseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        $str .= '<br/><a href="'.$courseurl.'">'.get_string('backtocourse', 'flashcard').'</a>';
        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }
}