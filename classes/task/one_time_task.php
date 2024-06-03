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
 * A one time task class to delete accounts which are inactive and older than 6 months.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 03/06/2024 Mfreak.nl | LdesignMedia.nl - Luuk Verhoeven
 * @author    Nihaal Shaikh
 */
namespace local_greetings\task;

use dml_exception;

/**
 * A one time task class to delete accounts which are inactive and older than 6 months.
 *
 * @copyright 03/06/2024 Mfreak.nl | LdesignMedia.nl - Luuk Verhoeven
 * @author    Nihaal Shaikh
 */
class one_time_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name(): string {
        return 'One time task';
    }

    /**
     * Run one time task.
     */
    public function execute(): string {
        global $DB, $CFG;

        try {
            // Define the date six months ago in Unix timestamp format.
            $sixmonthsago = time() - (6 * 30 * 24 * 60 * 60); // Approximation of six months.

            // Find and delete accounts that have never been active and were created more than 6 months ago.
            $sql = "SELECT id FROM {user} WHERE lastlogin = 0 AND timecreated < :sixmonthsago AND id != 1 AND deleted != 1";
            $accountstodelete = $DB->get_records_sql($sql, ['sixmonthsago' => $sixmonthsago]);

            if (!empty($accountstodelete)) {
                require_once($CFG->dirroot . '/user/lib.php');

                foreach ($accountstodelete as $account) {
                    $user = $DB->get_record('user', ['id' => $account->id]);
                    if ($user) {
                        // Delete the user using Moodle's built-in function, so that it is seen in the report.
                        delete_user($user);
                    }
                }

                return "Deleted " . count($accountstodelete) . " accounts.\n";
            } else {
                return "No accounts to delete.\n";
            }

        } catch (dml_exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

}
