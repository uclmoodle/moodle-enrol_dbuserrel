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
 * (Role-based relationships) Mapping Field factory.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_dbuserrel_field_factory {

    private static $instancedirectory = 'enrol_dbuserrel\\field\\';

    /**
     * Creates a (local) mapping field object.
     *
     * @param string $name name. Convention is to prefix this name with entity class name followed by "_"
     * @return enrol_dbuserrel\interfaces\field_interface
     *
     * @throws \Exception
     */
    public static function create(string $name) {
        // Convention is to name profile fields <class name>_<id | name>
        if (strlen($name) && (strpos($name, "_") !== false)) {

            // Try to instantiate the required class
            $last_ = strrpos($name, "_");

            $targetclass = strtolower(substr($name, 0, $last_)) ;
            $fieldid = strtolower(substr($name, $last_ + 1));

                $fieldclassname = self::$instancedirectory . $targetclass;

                if (class_exists($fieldclassname)) {
                    return new $fieldclassname($fieldid);
                } else {
                    throw new \Exception('Mapping field class ' . $fieldclassname . ' not found');
                }
        }

        return null;
    }

    public static function get_all_mappable_fields() {

        $allfields = array();

        foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . "field" . DIRECTORY_SEPARATOR . "*php") as $f) {
            try {
                $classfile = strtolower($f);
                $classfileparts = explode(DIRECTORY_SEPARATOR, $classfile);
                $classfilename = $classfileparts[count($classfileparts) - 1];
                $classname = self::$instancedirectory . str_replace(".php", "",$classfilename);

                if (class_exists($classname)) {
                    $class = new $classname(null);

                    if ($class instanceof enrol_dbuserrel_field_interface) {
                        $allfields[$classname] = $class::get_mappable_profile_fields();
                    } else {
                        throw new \Exception('Attempt to create mapping field using non-compliant class');
                    }
                }
            } catch(\Exception $e) {
                throw new \Exception('Failed to get list of all mappable fields because {a}');
            }
        }

        return $allfields;

    }

    public static function get_mappable_fields_for_config_settings() {
        $settings = array();

        foreach(self::get_all_mappable_fields() as $fieldtype => $fields) {
            foreach ($fields as $fielddefinition) {
                $settings[str_replace('enrol_dbuserrel\\field\\','',strtolower($fieldtype)) . "_" . $fielddefinition['id']] = '['
                    . str_replace('ENROL_DBUSERREL\\FIELD\\', '', trim(strtoupper($fieldtype)))
                    . '] ' . $fielddefinition['name'];
            }
        }

        return $settings;
    }
}
