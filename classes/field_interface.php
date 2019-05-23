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
 * (Role-based relationships mapping) Field interface.
 * Objects that implement this interface are expected to be identify fields (either internal to Moodle, or external),
 * and are used to map role-based relationships between sources of relationship data,
 * (for instance between an external data sources such as a student record system, and Moodle).
 *
 * Each field that complies with this interface ust somehow be able to map itself to Moodle's user.id field
 * since this is the foreign key needed to create role assignments that underpin role-based relationships.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

interface enrol_dbuserrel_field_interface {
    /**
     * Create new (mapping) field instance.
     *
     * @param array $config
     */
    public function __construct(string $id);

    /**
     * Translates the value of the current field supplied as a parameter to the equivalent Moodle user.id value
     *
     * @param string $value
     */
    public function get_equivalent_moodle_id($value);

    public function get_single_table_definition();

    public function get_userid_join_column();

    public function get_field_name();

    public function translate_moodle_userid_to_mapped_value($userid);
}
