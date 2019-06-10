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
 * Profile record field.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_dbuserrel\field;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user
 * @package enrol_dbuserrel\field
 */
class user implements \enrol_dbuserrel_field_interface {

    private $field = array();
    private static $mappableprofilefields = array();

    public function __construct(?string $fieldname) {
        if ((strlen($fieldname) > 0) && array_key_exists($fieldname, $this->get_mappable_profile_fields())) {
            $this->field = $this->get_mappable_profile_fields()[$fieldname];
        }

    }

    /**
     * @return mixed|string
     */
    public function get_field_name() {
        return $this->field['id'];
    }

    /**
     * @param int|string $userid
     * @return mixed|string
     * @throws \coding_exception
     */
    public function translate_moodle_userid_to_mapped_value($userid) {
        global $DB;

        // This translation attempt could fail because profile fields may be changed after setup, or may have been
        // setup before certain users were assigned values.
        try {
            return $DB->get_field('user', $this->field['id'], array('id' => $userid ));
        } catch (\Exception $e) {
            mtrace(get_string('failure_uidtranslate', 'enrol_dbuserrel',
                ['u' => $userid, 'id' => $this->field['id']]));
        }

        return "";
    }

    /**
     * @param string $value
     * @return int|mixed|string
     * @throws \coding_exception
     */
    public function get_equivalent_moodle_id($value) {
        global $DB;

        try {
            $configuredcolumn = self::get_mappable_profile_fields()[$this->field['id']]['id'];
            return $DB->get_field('user', 'id', array($configuredcolumn => $value));

        } catch (\Exception $e) {
            mtrace(get_string('failure_moodleuidtrl', 'enrol_dbuserrel',
                ['v' => $value, 'err' => $e->getMessage()]));
        }

        return "";
    }

    /**
     * @return array
     */
    public static function get_mappable_profile_fields() {
        return array(
            'id' => ['id' => 'id', 'shortname' => 'id', 'name' => 'id',
                'description' => 'ID column of Moodle user table'],
            'idnumber' => ['id' => 'idnumber', 'shortname' => 'idnumber', 'name' => 'idnumber',
                'description' => 'IDNumber column of Moodle user table'],
            'email' => ['id' => 'email', 'shortname' => 'email', 'name' => 'email',
                'description' => 'Email column of Moodle user table'],
            'username' => ['id' => 'username', 'shortname' => 'username', 'name' => 'username',
                'description' => 'Username column of Moodle user table']
        );
    }

    /**
     * Property setter
     */
    private function set_mappable_profile_fields() {
        self::$mappableprofilefields = self::get_mappable_profile_fields();
    }
}
