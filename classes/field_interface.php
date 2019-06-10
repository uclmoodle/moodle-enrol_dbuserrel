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

defined('MOODLE_INTERNAL') || die();

/**
 * (Role-based relationships mapping) Field interface.
 * Objects that implement this interface are expected to be identity fields (so unique and not null),
 * and will be used to map role-based relationships between sources of relationship data,
 * (for instance between an external data sources such as a student record system, and Moodle).
 *
 * Each field that complies with this interface must somehow be able to map itself to Moodle's user.id field
 * since this is the foreign key needed to create role assignments that underpin role-based relationships.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface enrol_dbuserrel_field_interface {
    /**
     * Create new (mapping) field instance.
     *
     * @param array $config
     */
    public function __construct(string $id);

    /**
     * Translates the supplied parameter value to an equivalent Moodle user.id value
     *
     * @param string $value
     * @return string|int Equivalent value from Moodle user table
     */
    public function get_equivalent_moodle_id($value);

    /**
     * Name used to identify field object. This name can be used to reference data collections that include the field.
     *
     * @return string
     */
    public function get_field_name();

    /**
     * Returns the equivalent Moodle Id value for the given value for the "field" object.
     *
     * @param string|int $userid Moodel user Id.
     * @return mixed Equivalent field value for the Moodle Id supplied as a parameter.
     */
    public function translate_moodle_userid_to_mapped_value($userid);

    /**
     * Returns an array of the possible child fields for the class that can be presented to users as config
     * options for mapping external relationship data. Needed to allow to allow rendering of config drop-down to users.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_mappable_profile_fields();
}
