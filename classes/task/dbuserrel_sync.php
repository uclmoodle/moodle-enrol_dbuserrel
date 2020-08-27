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
 * Syncing DB User role assignment task.
 *
 * @package   enrol_dbuserrel
 * @copyright 2020 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jackson D'souza (jackson.dsouza@catalyst-eu.net)
 */

namespace enrol_dbuserrel\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Syncing DB User role assignment task.
 *
 * @package   enrol_dbuserrel
 * @copyright 2020 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jackson D'souza (jackson.dsouza@catalyst-eu.net)
 */
class dbuserrel_sync extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() : string {
        return get_string('dbuserrelsynctask', 'enrol_dbuserrel');
    }

    /**
     * Run task for syncing dbuserrel enrolments.
     *
     * @return void
     */
    public function execute() : void {

        if (!enrol_is_enabled('dbuserrel')) {
            return;
        }

        $enrol = enrol_get_plugin('dbuserrel');
        $enrol->setup_enrolments(true);

    }
}
