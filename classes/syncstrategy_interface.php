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
 * (Role-based relationships) Sync strategy interface.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

interface enrol_dbuserrel_syncstrategy_interface {
    /**
     * Create new strategy object for syncing role-based relationships.
     *
     * @param array $config
     */
    public function __construct(
        \enrol_dbuserrel_dataport_interface $external_dataport,
        \enrol_dbuserrel_dataport_interface $internal_dataport
    );

    /**
     * Translates the value of the current field supplied as a parameter to the equivalent Moodle user.id value
     *
     * @param array $newrelationships Source/new role-based relationships
     * @param array $existingrelationships Sink/existing relationships (in Moodle)
     */
    public function get_external_relationships_in_scope($user);

    public function sync_relationships($userid, $verbose);
}
