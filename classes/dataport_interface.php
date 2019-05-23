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
 * (Role-based relationships) Dataport interface.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

interface enrol_dbuserrel_dataport_interface {
    /**
     * Create new dataport instance.
     *
     * @param array $config
     */
    public function __construct(array $config);
    public function get_relationships_in_scope(?string $subjectfilter, ?string $objectfilter);

    public function get_equivalent_moodle_id($value, $source);
    public function sanitise_literal_for_comparison(string $value);

    public function construct_unique_relationship_key();
    public function get_all_roles();

    public function get_role_fieldname();
    public function get_subject_fieldname();
    public function get_object_fieldname();
}
