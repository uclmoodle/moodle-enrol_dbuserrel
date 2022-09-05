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
 * Internal (role-based) relationship data port implementation.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class enrol_dbuserrel_dataport_internal
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class enrol_dbuserrel_dataport_internal implements enrol_dbuserrel_dataport_interface {

    /**
     * @var enrol_dbuserrel_field_interface
     */
    private $localsubject;

    /**
     * @var enrol_dbuserrel_field_interface
     */
    private $localobject;

    /**
     * @var string
     */
    private $localrolefield;

    /**
     * Create new internal (i.e. Moodle) data port object instance.
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config = []) {

        // Todo: sanitise all input values.
        if (isset($config['localsubject'])) {
            $this->set_local_subject(enrol_dbuserrel_field_factory::create(trim(strtolower($config['localsubject']))));

            if (!$this->get_local_subject() instanceof \enrol_dbuserrel_field_interface) {
                throw new \Exception(get_string('failure_localsubtypesetup', 'enrol_dbuserrel'));
            }
        } else {
            throw new \Exception(get_string('failure_localsubnotport', 'enrol_dbuserrel'));
        }

        if (isset($config['localobject'])) {
            $this->set_local_object(enrol_dbuserrel_field_factory::create(trim(strtolower($config['localobject']))));

            if (!$this->get_local_object() instanceof \enrol_dbuserrel_field_interface) {
                throw new \Exception(get_string('failure_localobtypesetup', 'enrol_dbuserrel'));
            }
        } else {
            throw new \Exception(get_string('failure_localobnotport', 'enrol_dbuserrel'));
        }

        if (isset($config['localrole'])) {
            $this->localrolefield = trim(strtolower($config['localrole']));

            if (!is_string($this->localrolefield) || (strlen($this->localrolefield) < 1)) {
                throw new \Exception(get_string('failure_localrolesetup', 'enrol_dbuserrel'));
            }
        } else {
            throw new \Exception(get_string('failure_localrolenotset', 'enrol_dbuserrel'));
        }

        $this->localrolefield = clean_param($this->localrolefield, PARAM_STRINGID);
    }

    /**
     * @return array
     * @throws dml_exception
     */
    public function get_all_roles() {
        global $DB;
        return $DB->get_records('role', array(), '', "$this->localrolefield, id", 0, 0);
    }

    /**
     * @return string
     */
    public function get_role_fieldname() {
        return $this->localrolefield;
    }

    /**
     * @return string
     */
    public function get_subject_fieldname() {
        return $this->get_local_subject()->get_field_name();
    }

    /**
     * @return string
     */
    public function get_object_fieldname() {
        return $this->get_local_object()->get_field_name();
    }

    /**
     * @param string|null $subjectfilter
     * @param string|null $objectfilter
     * @return array|null
     * @throws Exception
     */
    public function get_relationships_in_scope(?string $subjectfilter, ?string $objectfilter) {
        global $DB;

        $existingrelationships = array();

        try {
            $uniquekey = $DB->sql_concat("r." . $this->localrolefield, "'|'", "localsubject.id", "'|'", "localobject.id");

            $columns[] = $uniquekey . " AS uniq";
            $columns[] = "ra.roleid";
            $columns[] = "ra.userid";
            $columns[] = "ra.component";
            $columns[] = "ra.contextid";
            $columns[] = "r." . $this->localrolefield;
            $columns[] = "localsubject.id AS subject_id";
            $columns[] = "localobject.id AS object_id";

            $tables[] = " {role_assignments} AS ra ";
            $tables[] = " {role} AS r ";
            $tables[] = " {context} AS c ";
            $tables[] = " {user} AS localsubject ";
            $tables[] = " {user} AS localobject ";

            $sql = " SELECT " .
                implode(",", array_unique($columns)) .
                " FROM " .
                implode(",", array_unique($tables)) .
                " WHERE
                ra.roleid = r.id AND
                ra.component = 'enrol_dbuserrel'
                AND c.contextlevel = :usercontext AND
                c.id = ra.contextid AND
                ra.userid = localsubject.id AND
                c.instanceid = localobject.id";

            $params = ['usercontext' => CONTEXT_USER];
            if ($subjectfilter) {
                $sql .= " AND localsubject.id = :subjectfilter";
                $params['subjectfilter'] = $subjectfilter;
            }
            if ($objectfilter) {
                $sql .= " AND localobject.id = :objectfilter";
                $params['objectfilter'] = $objectfilter;
            }

            $existing = $DB->get_records_sql($sql, $params);

            foreach ($existing as $record) {
                // Key the array using unique keys in terms of userid.
                $existingrelationships[$record->uniq] = (array)$record;
            }
        } catch (\Exception $e) {
            throw new \Exception(get_string('failure_getexistingrels', 'enrol_dbuserrel', $e->getMessage()));
        }

        return $existingrelationships;
    }

    // Todo: improve implementation of this function.
    /**
     * @param string $value
     * @param string $source
     * @return int|string|null
     */
    public function get_equivalent_moodle_id($value, $source) {
        if ($source == 'subject') {
            return clean_param($this->get_local_subject()->get_equivalent_moodle_id($value), PARAM_STRINGID);
        } else if ($source == 'object') {
            return clean_param($this->get_local_object()->get_equivalent_moodle_id($value), PARAM_STRINGID);
        } else {
            return null;
        }
    }


    /**
     * Close connection
     */
    public function shutdown() {
    }

    /**
     * @return enrol_dbuserrel_field_interface
     */
    private function get_local_object(): enrol_dbuserrel_field_interface {

        return $this->localobject;
    }

    /**
     * @param enrol_dbuserrel_field_interface $o
     */
    private function set_local_object(enrol_dbuserrel_field_interface $o) {
        $this->localobject = $o;
    }

    /**
     * @return enrol_dbuserrel_field_interface|null
     */
    private function get_local_subject(): ?enrol_dbuserrel_field_interface {
        return $this->localsubject;
    }

    /**
     * @param enrol_dbuserrel_field_interface|null $o
     */
    private function set_local_subject(?enrol_dbuserrel_field_interface $o) {
        $this->localsubject = $o;
    }
}
