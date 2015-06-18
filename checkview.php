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
 * This view allows checking deck states
 * 
 * @package mod-flashcard
 * @category mod
 * @author Gustav Delius
 * @contributors Valery Fremaux
 * @version Moodle 2.0
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

// Security.

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

// Print deferred header.

echo $out;

// Get available decks for user and calculate deck state.

if (!$decks = flashcard_get_deck_status($flashcard)) {
    // If deck status have bever been initialized initialized them.
    if (flashcard_initialize($flashcard, $USER->id)) {
        $decks = flashcard_get_deck_status($flashcard);
    } else {
        if (has_capability('mod/flashcard:manage', $context)) {
            $url = new moodle_url('/mod/flashcard/view.php', array('id' => $cm->id, 'view' => 'edit'));
        } else {
            $url = new moodle_url('/course/view.php', array('id' => $course->id));
        }
        echo $OUTPUT->notification(get_string('nocards', 'flashcard'), $url);
    }
}
?>

<center>
<table width="90%" cellspacing="10">
    <tr>
        <th>
            <?php print_string('difficultcards', 'flashcard') ?>
        </th>
<?php
if ($flashcard->decks >= 3) {
?>
        <th>
            <?php print_string('mediumeffortcards', 'flashcard') ?>
        </th>
<?php
}
?>
        <th>
            <?php print_string('easycards', 'flashcard') ?>
        </th>
<?php
if ($flashcard->decks >= 4) {
?>
        <th>
            <?php print_string('trivialcards', 'flashcard') ?>
        </th>
<?php
}
?>
    </tr>
    <tr valign="top">
        <td>
            <?php
                print_string('cardsindeck', 'flashcard', 0 + @$decks->decks[0]->count);
                echo "<br/>";
                if (@$decks->decks[0]->count == 0) {
                     echo $renderer->print_deck($flashcard, $cm, 0);
                } else {
                    if ($decks->decks[0]->reactivate) {
                        echo $renderer->print_deck($flashcard, $cm, 1);
                    } else {
                        echo $renderer->print_deck($flashcard, $cm, -1);
                    }
                }
            ?>
        </td>
        <td>
            <?php
                print_string('cardsindeck', 'flashcard', 0 + @$decks->decks[1]->count);
                echo "<br/>";
                if (@$decks->decks[1]->count == 0) {
                     echo $renderer->print_deck($flashcard, $cm, 0);
                } else {
                    if ($decks->decks[1]->reactivate) {
                        echo $renderer->print_deck($flashcard, $cm, 2);
                    } else {
                        echo $renderer->print_deck($flashcard, $cm, -2);
                    }
                }
            ?>
        </td>
<?php
if ($flashcard->decks >= 3) {
?>
        <td>
            <?php
                print_string('cardsindeck', 'flashcard', 0 + @$decks->decks[2]->count);
                echo "<br/>";
                if (@$decks->decks[2]->count == 0) {
                     echo $renderer->print_deck($flashcard, $cm, 0);
                } else {
                    if ($decks->decks[2]->reactivate) {
                        echo $renderer->print_deck($flashcard, $cm, 3);
                    } else {
                        echo $renderer->print_deck($flashcard, $cm, -3);
                    }
                }
            ?>
        </td>
<?php
}
if ($flashcard->decks >= 4) {
?>
        <td>
            <?php
                print_string('cardsindeck', 'flashcard', 0 + @$decks->decks[3]->count);
                echo "<br/>";
                if (@$decks->decks[3]->count == 0){
                     echo $renderer->print_deck($flashcard, $cm, 0);
                } else {
                    if ($decks->decks[3]->reactivate){
                        echo $renderer->print_deck($flashcard, $cm, 4);
                    } else {
                        echo $renderer->print_deck($flashcard, $cm, -4);
                    }
                }
            ?>
        </td>
<?php
}
?>
    </tr>
</table>
</center>