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
 * @package mod_flashcard
 * @category mod
 * @author Gustav Delius
 * @contributors Valery Fremaux
 * @version Moodle 2.0
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

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
        echo $OUTPUT->notification(get_string('nocards', 'flashcard'));
        echo $OUTPUT->continue_button($url);
    }
}

echo '<center>';
echo '<table width="90%" cellspacing="10">';
echo '<tr>';
echo '<th>';
print_string('difficultcards', 'flashcard');
echo '</th>';
if ($flashcard->decks >= 3) {
    echo '<th>';
    print_string('mediumeffortcards', 'flashcard');
    echo '</th>';
}

echo '<th>';
print_string('easycards', 'flashcard');
echo '</th>';

if ($flashcard->decks >= 4) {
    echo '<th>';
    print_string('trivialcards', 'flashcard');
    echo '</th>';
}

echo '</tr>';
echo '<tr valign="top">';
echo '<td>';
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
echo '</td>';
echo '<td>';
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
echo '</td>';

if ($flashcard->decks >= 3) {
    echo '<td>';
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
    echo '</td>';
}

if ($flashcard->decks >= 4) {
    echo '<td>';
    print_string('cardsindeck', 'flashcard', 0 + @$decks->decks[3]->count);
    echo "<br/>";
    if (@$decks->decks[3]->count == 0) {
         echo $renderer->print_deck($flashcard, $cm, 0);
    } else {
        if ($decks->decks[3]->reactivate) {
            echo $renderer->print_deck($flashcard, $cm, 4);
        } else {
            echo $renderer->print_deck($flashcard, $cm, -4);
        }
    }
    echo '</td>';
}

echo '</tr>';
echo '</table>';
echo '</center>';