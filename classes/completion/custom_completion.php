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

declare(strict_types=1);

namespace mod_flashcard\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the survey activity.
 *
 * Class for defining mod_survey's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given survey instance and a user.
 *
 * @package mod_flashcard
 * @copyright Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        // Get flashcard details.
        if (!($flashcard = $DB->get_record('flashcard', ['id' => $this->cm->instance]))) {
            throw new Exception("Can't find flashcard {$this->cm->instance}");
        }

        // Completion condition 1 is have no cards in deck.

        // Count all cards.
        $allcards = $DB->count_records('flashcard_deckdata', ['flashcardid' => $flashcard->id]);

        if ($rule == 'completionallgood') {

            // Match any card that are NOT in last deck.
            $sql = "
                SELECT
                    COUNT(DISTINCT c.id)
                FROM
                    {flashcard_card} c
                WHERE
                    c.userid = ? AND
                    c.flashcardid = ? AND
                    c.deck = ?
            ";
            $result = $DB->count_records_sql($sql, array($userid, $flashcard->id, $flashcard->decks));
        } else if ($rule == 'completionallviewed') {
            // Allgood superseedes allviewed.

            // Match distinct viewed cards.
            $sql = "
                SELECT
                    COUNT(DISTINCT c.entryid)
                FROM
                    {flashcard_card} c
                WHERE
                    c.userid = ? AND
                    c.flashcardid = ?
            ";
            $result = $DB->count_records_sql($sql, [$userid, $flashcard->id]);
        }

        return $result;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionallviewed',
            'completionallgood',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionallviewed' => get_string('completiondetail:allviewed', 'flashcard'),
            'completionallgood' => get_string('completiondetail:allgood', 'flashcard'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionallviewed',
            'completionallgood',
        ];
    }
}
