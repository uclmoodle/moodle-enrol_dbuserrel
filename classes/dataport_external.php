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
 * External (role-based) relationship data port implementation.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class enrol_dbuserrel_dataport_external
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class enrol_dbuserrel_dataport_external implements enrol_dbuserrel_dataport_interface {

    /**
     * @var ADONewConnection|null
     */
    private $db;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $remoterolefield;

    /**
     * @var string
     */
    private $remotesubject;

    /**
     * @var string
     */
    private $remoteobject;

    /**
     * @var bool
     */
    private $debugging = false;

    /**
     * Create new external (i.e. non-Moodle) data port object instance.
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config) {
        try {
            $this->db = $this->init_external_db(
                $config['dbhost'], $config['dbname'], $config['dbtype'],
                $config['dbuser'],
                $config['dbpass'],
                $config['dbsetupsql'],
                $config['debugdb']
            );

            $this->debugging = $config['debugdb'];

            $this->table = $config['table'];

            if (isset($config['remotesubject'])) {
                $this->set_remote_subject(trim(strtolower($config['remotesubject'])));

                if (!is_string($this->remotesubject) || (strlen($this->remotesubject) < 1)) {
                    throw new \Exception(get_string('failure_remotesubtype', 'enrol_dbuserrel'));
                }
            }

            if (isset($config['remoteobject'])) {
                $this->set_remote_object(trim(strtolower($config['remoteobject'])));

                if (!is_string($this->remoteobject) || (strlen($this->remoteobject) < 1)) {
                    throw new \Exception(get_string('failure_remoteobtype', 'enrol_dbuserrel'));
                }
            }

            if (isset($config['remoterole'])) {
                $this->remoterolefield = trim(strtolower($config['remoterole']));

                if (!is_string($this->remoterolefield) || (strlen($this->remoterolefield) < 1)) {
                    throw new \Exception(get_string('failure_remoteroletype', 'enrol_dbuserrel'));
                }
            }

        } catch (\Exception $e) {
            throw new \Exception(get_string('failure_dbinit', 'enrol_dbuserrel', $e->getMessage()));
        }

        if ($this->db == null) {
            throw new \Exception(get_string('failure_dbconn', 'enrol_dbuserrel'));
        }
    }

    /**
     * @param string|null $subjectfilter
     * @param string|null $objectfilter
     * @return array|null
     */
    public function get_relationships_in_scope(?string $subjectfilter, ?string $objectfilter) {
        $filter = "";
        $externaldata = array();

        if ($subjectfilter && $objectfilter) {
            $filter = $this->remoteobject . "=" . $this->sanitise_literal_for_comparison($objectfilter) .
                " OR " . $this->remotesubject . "=" . $this->sanitise_literal_for_comparison($subjectfilter);
        }

        $sql = "SELECT " .
            $this->db->concat($this->remoterolefield, "'|'", $this->remotesubject, "'|'", $this->remoteobject) . " AS uniq," .
            "t.* FROM " . $this->table . " t WHERE 1=1 " . ($filter ? " AND (" . $filter . ")" : "");
        $data = $this->db->GetAll($sql);

        if (is_array($data)) {
            foreach ($data as $record) {
                $externaldata[$record['uniq']] = $record;
            }
        }

        // Todo: Cleanup external DB connection?
        return $externaldata;
    }

    /**
     * @param string $value
     * @param string $source
     * @return int|null
     */
    public function get_equivalent_moodle_id($value, $source) {
        return $this->remote{$source}::get_equivalent_moodle_id($value);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_all_roles() {
        try {
            return $this->db->GetAll("SELECT DISTINCT " . $this->remoterolefield . " AS id," . $this->remoterolefield .
                " FROM " . $this->table);
        } catch (\Exception $e) {
            throw new \Exception(get_string('failure_remoterolefetch', 'enrol_dbuserrel', $e->getMessage()));
        }
    }

    /**
     * @return string
     */
    public function get_role_fieldname() {
        return $this->remoterolefield;
    }

    /**
     * @return string
     */
    public function get_subject_fieldname() {
        return $this->remotesubject;
    }

    /**
     * @return string
     */
    public function get_object_fieldname() {
        return $this->remoteobject;
    }

    /**
     * @param string $value
     * @return mixed
     */
    private function sanitise_literal_for_comparison(string $value) {
        return $this->db->quote($value);
    }

    /**
     * Tries to make connection to the external database.
     *
     * @return null|ADONewConnection
     */
    private function init_external_db($host, $dbname, $dbtype, $username, $password, $setupsql, $debug) {

        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection).
        $extdb = ADONewConnection($dbtype);
        if ($debug) {
            $extdb->debug = true;
            ob_start(); // Start output buffer to allow later use of the page headers.
        }

        // The dbtype my contain the new connection URL, so make sure we are not connected yet.
        if (!$extdb->IsConnected()) {
            $result = $extdb->Connect($host, $username, $password, $dbname, true);
            if (!$result) {
                return null;
            }
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($setupsql) {
            $extdb->Execute($setupsql);
        }

        return $extdb;
    }

    /**
     * Close connection
     */
    public function shutdown() {
        // NOTE: if $this->db_init() succeeds you MUST remember to call.
        // $this->enrol_disconnect() as it is doing some nasty vodoo with $CFG->prefix.
        try {
            if ($this->db->IsConnected()) {
                $this->db->Disconnect();
            }
        } catch (\Exception $e) {
            mtrace('Failed to shutdown external data port because ' . $e->getMessage());
        }

        if ($this->debugging) {
            mtrace(ob_get_contents());
        }

        // CATALYST - Commented out as it was causing issue with the mtrace output being written to task logs table.
        // ob_end_clean();
    }

    // Todo: check if these functions are needed.

    /**
     * @param $text
     * @return array
     */
    private function db_encode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return textlib::convert($text, 'utf-8', $dbenc);
        }
    }

    /**
     * @param $text
     * @return array
     */
    private function db_decode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return textlib::convert($text, $dbenc, 'utf-8');
        }
    }

    /**
     * @param $text
     * @return mixed
     */
    private function db_addslashes($text) {
        // Using custom made function for now.
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    /**
     * @return string
     */
    private function get_remote_subject(): string {
        return $this->remotesubject;
    }

    /**
     * @param string $o
     */
    private function set_remote_subject(string $o) {
        $this->remotesubject = $o;
    }

    /**
     * @return string
     */
    private function get_remote_object(): string {
        return $this->remoteobject;
    }

    /**
     * @param string $o
     */
    private function set_remote_object(string $o) {
        $this->remoteobject = $o;
    }
}
