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
 * (Role-based relationships) Dataport factory.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_dbuserrel_dataport_factory {
    /**
     * Creates a dataport object from supplied config
     *
     * @param string $name Type of dataport required.
     * @param array $config Dataport config.
     * @return enrol_dbuserrel\interfaces\dataport_interface
     *
     * @throws \Exception
     */
    public static function create(string $name, array $config) {
        switch($name) {
            case "EXTERNAL": {
                return new enrol_dbuserrel_dataport_external($config);
                break;
            }
            case "INTERNAL":
            case "MOODLE" : {
                return new enrol_dbuserrel_dataport_internal($config);
                break;
            }
            default:
                throw new \Exception('Attempt to create unknown type of data port (must be EXTERNAL or INTERNAL');
        }
    }
}
