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
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_dbuserrel\field;

defined('MOODLE_INTERNAL') || die();

/**
 * Class profile
 * @package enrol_dbuserrel\field
 */
class profile implements \enrol_dbuserrel_field_interface {

    /**
     * @var array|mixed
     */
    private $field = array();

    /**
     * @var array
     */
    private static $mappableprofilefields = array();

    /**
     * Create new field object to represent mappable user profile fields.
     *
     * @param string|null $profilefielddbid
     * @throws \dml_exception
     */
    public function __construct(?string $profilefielddbid) {

        if (strlen($profilefielddbid)) {
            $fieldid = trim($profilefielddbid);
            $this->set_mappable_profile_fields();
            if (array_key_exists($fieldid, self::$mappableprofilefields)) {
                $this->field = self::$mappableprofilefields[$fieldid];
            }
        }

    }

    /**
     * @return mixed|string|null
     */
    public function get_field_name() {
        if (isset($this->field['id'])) {
            return $this->field['id'];
        }
        return null;
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
            return $DB->get_field('user_info_data', 'data', array('userid' => $userid ));
        } catch (\Exception $e) {
            mtrace(get_string('failure_uidtranslateprofile', 'enrol_dbuserrel',
                ['u' => $userid, 'id' =>  $this->field['id']]));
        }

        return "";
    }

    // Todo: Improve implementation. Shouldn't have to loop to get value.
    /**
     * @param string $value
     * @return int|string
     * @throws \coding_exception
     */
    public function get_equivalent_moodle_id($value) {
        global $DB;

        try {
            $sql = "SELECT userid FROM {user_info_data} WHERE data ='" . $value . "' AND fieldid = " .
                str_replace("f", "", $this->field['id']);

            $userid = $DB->get_records_sql($sql);

            if (count($userid)) {
                foreach ($userid as $u) {
                    return $u->userid;
                }
            }

        } catch (\Exception $e) {
            mtrace(get_string('failure_profilevaluetranslate', 'enrol_dbuserrel',
                ['v' => $value, 'err' => $e->getMessage()]));
        }

        return "";
    }

    /**
     * Returns an array of the possible Moodle profile fields that can be presented to admin users as config
     * options for mapping external relationship data.
     *
     * @return array
     * @throws \dml_exception
     */
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

            // Workaround to avoid issues with purely numeric index.
            $tempfield['id'] = 'f' . $tempfield['id'];
            $keyedfields['f' . $f->id] = $tempfield;
        }

        return $keyedfields;
    }

    /**
     * @throws \dml_exception
     */
    private function set_mappable_profile_fields() {
        self::$mappableprofilefields = self::get_mappable_profile_fields();
    }
}
