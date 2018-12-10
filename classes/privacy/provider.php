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

namespace mod_flashcard\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection) : collection {

        $fields = [
            'userid' => 'privacy:metadata:flashcard_card:userid',
            'flashcardid' => 'privacy:metadata:flashcard_card:flashcardid',
            'entryid' => 'privacy:metadata:flashcard_card:entryid',
            'deck' => 'privacy:metadata:flashcard_card:deck',
            'lastaccessed' => 'privacy:metadata:flashcard_card:lastaccessed',
        ];

        $collection->add_database_table('flashcard_card', $fields, 'privacy:metadata:flashcard_card');

        $fields = [
            'userid' => 'privacy:metadata:flashcard_userdeck_state:userid',
            'flashcardid' => 'privacy:metadata:flashcard_userdeck_state:flashcardid',
            'deck' => 'privacy:metadata:flashcard_userdeck_state:deck',
            'state' => 'privacy:metadata:flashcard_userdeck_state:state',
        ];

        $collection->add_database_table('flashcard_userdeck_state', $fields, 'privacy:metadata:flashcard_userdeck_state');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
  public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Fetching flashcard_cards context should be sufficiant to get contexts where user is involved in.
        // It may have NO states if it has no deck cards.

        $sql = "
            SELECT
                c.id
            FROM
                {context} c
            INNER JOIN
                {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN
                {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN
                {flashcard} f ON f.id = cm.instance
            LEFT JOIN
                {flashcard_card} fc ON fc.flashcardid = f.id
            WHERE fc.userid = :userid
        ";

        $params = [
            'modname'           => 'flashcard',
            'contextlevel'      => CONTEXT_MODULE,
            'userid'  => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $ctx) {
            $instance = writer::withcontext($ctx);

            $data = new StdClass;

            $params = array('flashcardid' => $ctx->instanceid,
                            'userid' => $user->id);
            $cards = $DB->get_records('flashcard_cards', $params);

            foreach ($cards as $card) {
                $data->decks[$card->deck][] = $card;
            }

            $params = array('flashcardid' => $ctx->instanceid,
                            'userid' => $user->id);
            $cards = $DB->get_records('flashcard_userdeck_state', $params);

            foreach ($cards as $state) {
                $data->states[$state->deck][] = $state;
            }

            $instance->export_data(null, $data);
        }
    }

    public static function delete_data_for_all_users_in_context(deletion_criteria $criteria) {
        global $DB;

        $context = $criteria->get_context();
        if (empty($context)) {
            return;
        }

        $DB->delete_records('flashcard_card', ['flashcardid' => $context->instanceid]);
        $DB->delete_records('flashcard_userdeck_state', ['flashcardid' => $context->instanceid]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            $DB->delete_records('flashcard_card', ['flashcardid' => $ctx->instanceid, 'userid' => $userid]);
            $DB->delete_records('flashcard_userdeck_state', ['flashcardid' => $ctx->instanceid, 'userid' => $userid]);
        }
    }
}