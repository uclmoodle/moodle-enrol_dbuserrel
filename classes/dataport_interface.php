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
 * (Role-based relationships) Data port interface.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Represents external and internal sources (or ports for) of relationship data.
 *
 * @package   enrol_dbuserrel
 * @copyright 2019 Segun Babalola <segun@babalola.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface enrol_dbuserrel_dataport_interface {

    /**
     * Constructor.
     *
     * @param array $config Configuration info.
     */
    public function __construct(array $config);

    /**
     * Gets a list of all the relationships from the data source that should be considered (given the input filter).
     *
     * @param string|null $subjectfilter Typically Moodle user Id.
     * @param string|null $objectfilter Typically Moodle user Id.
     * @return array|null Array of relationships present in the data source this data port is connected to.
     */
    public function get_relationships_in_scope(?string $subjectfilter, ?string $objectfilter);

    /**
     * Translates the Id of a object or subject to a Moodle DB user Id.
     *
     * @param string $value The input Id value to be translated
     * @param string $source Indicates type of the input Id value, i.e. "subject" or "object".
     * @return int|null The equivalent Moodle DB user Id.
     */
    public function get_equivalent_moodle_id($value, $source);

    /**
     * Gets all known roles from the data source that may be used to define/process relationships.
     *
     * @return array Roles, expressed as associative array with Id and name pairs for each role.
     */
    public function get_all_roles();

    /**
     * Returns the name of the field/element used by the data source to hold role name.
     * This field name is useful for accessing individual data items in the results returned by get_relationships_in_scope().
     *
     * @return string
     */
    public function get_role_fieldname();

    /**
     * Returns the name of the field/element used by the data source to hold subject.
     * This field name is useful for accessing individual data items in the results returned by get_relationships_in_scope().
     *
     * @return string
     */
    public function get_subject_fieldname();

    /**
     * Returns the name of the field/element used by the data source to hold object.
     * This field name is useful for accessing individual data items in the results returned by get_relationships_in_scope().
     *
     * @return string
     */
    public function get_object_fieldname();

    /**
     * Opportunity to do any cleanup needed after using data ports.
     */
    public function shutdown();
}
