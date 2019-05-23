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

Class profile implements \enrol_dbuserrel_field_interface {

    private $field = array();
    private static $mappable_profile_fields = array();

    /**
     * Create new external (i.e. non-Moodle) dataport object instance.
     *
     */
    public function __construct(?string $profilefielddbid) {

        if (strlen($profilefielddbid)) {
            $fieldid = trim($profilefielddbid);
            $this->set_mappable_profile_fields();
            if (array_key_exists($fieldid, self::$mappable_profile_fields)) {
                $this->field = self::$mappable_profile_fields[$fieldid];
            }
        }

    }

    public function get_single_table_definition(){
        $sql = "SELECT enrol_dbuserrel_profile_user.id AS uid ";
        $columns = array();

        $profile_fields = self::get_mappable_profile_fields();
        foreach ($profile_fields as $f) {
            $columns[] = "(SELECT `data` FROM mdl_user_info_data WHERE userid=enrol_dbuserrel_profile_user.id AND fieldid="
                . str_replace("f","", $f['id']) . ") AS "
                . $f['id'];
        }

        $sql .= (count($columns) ? "," : "") . implode(", ", $columns) . " FROM {user} AS enrol_dbuserrel_profile_user";

        return array(
            "table_alias" => "enrol_dbuserrel_profile_fields",
            "table_def" => "(" . $sql . ")");
    }

    public function get_userid_join_column() {
        return "uid";
    }

    public function get_field_name() {
        if (isset($this->field['id'])) {
            return $this->field['id'];
        }
        return null;
    }

    public function translate_moodle_userid_to_mapped_value($userid) {
        global $DB;

        // This translation attempt could fail because profile fields may be changed after setup, or may have been
        // setup before certain users were assigned values.
        try {
            return $DB->get_field('user_info_data','data',array('userid' => $userid ));
        } catch(\Exception $e) {
            mtrace("Failed to translate Moodle user Id " . $userid . " into a " . self::$field['id']
                . " profile field value");
        }

        return "";
    }

    // Todo: Improve implementation. Shouldn't have to loop to get value.
    public function get_equivalent_moodle_id($value) {
        global $DB;

        try {
            $sql = "SELECT userid FROM {user_info_data} WHERE data ='" . $value . "' AND fieldid = " .
                str_replace("f", "", $this->field['id']);

            $userid = $DB->get_records_sql($sql);

            if (count($userid)) {
                foreach ($userid as $u)
                    return $u->userid;
            }

        } catch(\Exception $e) {
            mtrace('Unable to translate profile value ' . $value . ' to a Moodle user ID because ' . $e->getMessage());
        }

        return "";
    }

    public static function get_mappable_profile_fields() {
        global $DB;

        $profilefields = $DB->get_records(
            'user_info_field',
            array(
                'datatype' => 'text',
                'forceunique' => 1,
                'required' => 1
            ),
            'sortorder', "id, shortname, name, description", 0, 0
        );

        // Key the fields by ID so it's easier to use later.
        $keyedfields = array();
        foreach ($profilefields as $f) {
            $tempfield = (array)$f;

            // workaround to avoid issues with purely numeric index
            $tempfield['id'] = 'f' . $tempfield['id'];
            $keyedfields['f' . $f->id] = $tempfield;
        }

        return $keyedfields;
    }

    private function set_mappable_profile_fields() {
        self::$mappable_profile_fields = self::get_mappable_profile_fields();
    }
}
