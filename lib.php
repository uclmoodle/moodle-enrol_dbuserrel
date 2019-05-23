<?php  // $Id$
/**
 * User role assignment plugin.
 *
 * This plugin synchronises user roles with external database table.
 *
 * @package    enrol
 * @subpackage dbuserrel
 * @copyright  Penny Leach <penny@catalyst.net.nz>
 * @copyright  Maxime Pelletier <maxime.pelletier@educsa.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_dbuserrel_plugin extends enrol_plugin {

    private $externaldataport;
    private $internaldataport;

    private $syncstrategy;

    var $log;

    private function setup() {
        try {
            // Set data ports (internal, i.e. Moodle) and external
            $this->set_external_dataport(enrol_dbuserrel_dataport_factory::create('EXTERNAL', [
                'dbhost' => $this->get_config('dbhost'), 'dbname' => $this->get_config('dbname'),
                'dbtype' => $this->get_config('dbtype'), 'dbuser' => $this->get_config('dbuser'),
                'dbpass' => $this->get_config('dbpass'), 'dbsetupsql' => $this->get_config('dbsetupsql'),
                'table' => $this->get_config('remoteenroltable'),
                'remotesubject' => $this->get_config('remotesubjectuserfield'),
                'remoteobject' => $this->get_config('remoteobjectuserfield'),
                'remoterole' => $this->get_config('remoterolefield'),
                'debugdb' => $this->get_config('debugdb')
            ]));

            $this->set_internal_dataport(enrol_dbuserrel_dataport_factory::create('INTERNAL', [
                'localsubject' => $this->get_config('localsubjectuserfield'),
                'localobject' => $this->get_config('localobjectuserfield'),
                'localrole' => $this->get_config('localrolefield')
            ]));

            // Configure strategy (only default exists for now).
            $this->syncstrategy = new enrol_dbuserrel\syncstrategy\defaultstrategy(
                $this->get_external_dataport(),
                $this->get_internal_dataport()
            );

        } catch (\Exception $e) {
            error_log('Error: [ENROL_DBUSERREL] Initialisation failed : ' . $e->getMessage());
        }
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function instance_deleteable($instance) {
        if (!enrol_is_enabled('dbuserrel')) {
            return true;
        }
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('remoteenroltable') or !$this->get_config('remotecoursefield') or !$this->get_config('remoteuserfield')) {
            return true;
        }

        //TODO: connect to external system and make sure no users are to be enrolled in this course
        return false;
    }

    /**
     * Does this plugin allow manual unenrolment of a specific user?
     * Yes, but only if user suspended...
     *
     * @param stdClass $instance course enrol instance
     * @param stdClass $ue record from user_enrolments table
     *
     * @return bool - true means user with 'enrol/xxx:unenrol' may unenrol this user, false means nobody may touch this user enrolment
     */
    public function allow_unenrol_user(stdClass $instance, stdClass $ue) {
        if ($ue->status == ENROL_USER_SUSPENDED) {
            return true;
        }

        return false;
    }


    /*
     * MAIN FUNCTION
     * For the given user, let's go out and look in an external database
     * for an authoritative list of relationships, and then adjust the
     * local Moodle assignments to match.
     * @param bool $verbose
     * @return int 0 means success, 1 db connect failure, 2 db read failure
     */

    function setup_enrolments($verbose = false, &$user=null) {
        try {
            $this->setup();

            mtrace('Starting user enrolment synchronisation...');

            // NOTE: if $this->db_init() succeeds you MUST remember to call
            // $this->enrol_disconnect() as it is doing some nasty vodoo with $CFG->prefix
            if ($verbose) {
                mtrace("Starting db_init()");
            }

            // we may need a lot of memory here
            @set_time_limit(0);
            raise_memory_limit(MEMORY_HUGE);

            // Assume no user initially.
            $userid = null;

            if ($user && isset($user->id) && intval($user->id, 10) > 0) {
                $userid = $user->id;
            }

            $this->syncstrategy->sync_relationships($userid, $verbose);
        } catch(\Exception $e) {
            mtrace('Failed to sync user relationships because ' . $e->getMessage());
        }
    }

    // Data ports.
    private function get_internal_dataport(): enrol_dbuserrel_dataport_interface {
        return $this->internaldataport;
    }

    private function set_internal_dataport(enrol_dbuserrel_dataport_interface $dp) {
        $this->internaldataport = $dp;
    }

    private function get_external_dataport(): enrol_dbuserrel_dataport_interface {
        return $this->externaldataport;
    }

    private function set_external_dataport(enrol_dbuserrel_dataport_interface $dp) {
        $this->externaldataport = $dp;
    }

} // end of class


