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

    var $log;

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
    global $CFG, $DB;

    mtrace('Starting user enrolment synchronisation...');

    // NOTE: if $this->db_init() succeeds you MUST remember to call
    // $this->enrol_disconnect() as it is doing some nasty vodoo with $CFG->prefix
    if ($verbose) {
	mtrace("Starting db_init()");
    }
    $extdb = $this->db_init();
    if (!$extdb) {
        error_log('Error: [ENROL_DBUSERREL] Could not make a connection');
        return;
    }

    // we may need a lot of memory here
    @set_time_limit(0);
    raise_memory_limit(MEMORY_HUGE);

    // Store the field values in some shorter variable names to ease reading of the code.
    $flocalsubject  = strtolower($this->get_config('localsubjectuserfield'));
    $flocalobject   = strtolower($this->get_config('localobjectuserfield'));
    $flocalrole     = strtolower($this->get_config('localrolefield'));
    $fremotesubject = strtolower($this->get_config('remotesubjectuserfield'));
    $fremoteobject  = strtolower($this->get_config('remoteobjectuserfield'));
    $fremoterole    = strtolower($this->get_config('remoterolefield'));
    $dbtable        = $this->get_config('remoteenroltable');
	
	

    // TODO: Ensure that specifying a user works correctly
    if ($user) {
        $subjectfield = $extdb->quote($user->{$flocalsubject});
        $objectfield = $extdb->quote($user->{$flocalobject});

        $sql = "SELECT * FROM {$dbtable}
            WHERE {$fremotesubject} = $subjectfield
            OR {$fremoteobject} = $objectfield";
    } else {
		// Get all entries from source(external) table
        $sql = "SELECT * FROM {$dbtable}";
    }

	// Execute query to get entries from external DB
    if ($rs = $extdb->Execute($sql)) {

        if ($verbose) {
	    mtrace($rs->RecordCount()." entries in the external table");
        }

		// Unique identifier of the role assignment
        $uniqfield = $DB->sql_concat("r.$flocalrole", "'|'", "u1.$flocalsubject", "'|'", "u2.$flocalobject");
		
		// Query to retreive all user role assignment from Moodle
        $sql = "SELECT $uniqfield AS uniq,
            ra.*, r.{$flocalrole} ,
            u1.{$flocalsubject} AS subjectid,
            u2.{$flocalobject} AS objectid
            FROM {role_assignments} ra
            JOIN {role} r ON ra.roleid = r.id
            JOIN {context} c ON c.id = ra.contextid
            JOIN {user} u1 ON ra.userid = u1.id
            JOIN {user} u2 ON c.instanceid = u2.id
            WHERE ra.component = 'enrol_dbuserrel' 
			AND c.contextlevel = " . CONTEXT_USER;
            //(!empty($user) ?  " AND c.instanceid = {$user->id} OR ra.userid = {$user->id}" : '');

		// Is there any role in Moodle?
		// The first column is used as the key
		if (!$existing = $DB->get_records_sql($sql)) {
			$existing = array();
        }

        if ($verbose) {
	    mtrace(sizeof($existing)." role assignement entries from dbuserrel found in Moodle DB");
        }

	// Is there something in the remote table?
        if (!$rs->EOF) {

            // MOODLE 1.X => $roles = $DB->get_records('role', array(), '', '', "$flocalrole, id");
	    $roles = $DB->get_records('role', array(), '', "$flocalrole, id", 0, 0);
	
            if ($verbose) {
	        mtrace(sizeof($roles)." role entries found in Moodle DB");
            }

            $subjectusers = array(); // cache of mapping of localsubjectuserfield to mdl_user.id (for get_context_instance)
            $objectusers = array(); // cache of mapping of localsubjectuserfield to mdl_user.id (for get_context_instance)
            $contexts = array(); // cache

            $rels = array();
			
            // We loop through all the records of the remote table
            while ($row = $rs->FetchRow() ) {
		// Convert encoding if necessary
		//		$row = reset($row);
		$row = $this->db_decode($row);

                if ($verbose) {
                    print_r($row);
                    mtrace("Role:".$row[$fremoterole]);
                }

		// TODO: Handle coma seperated values in remoteobject field
                // either we're assigning ON the current user, or TO the current user
                $key = $row[$fremoterole] . '|' . $row[$fremotesubject] . '|' . $row[$fremoteobject];
				
				// Check if the role is already assigned
                if (array_key_exists($key, $existing)) {
                    // exists in moodle db already, unset it (so we can delete everything left)
                    unset($existing[$key]);
                    error_log("Warning: [$key] exists in moodle already");
                    continue;
                }

				// Check if the role from the remote table exist in Moodle
                if (!array_key_exists($row[$fremoterole], $roles)) {
                    // role doesn't exist in moodle. skip.
                    error_log("Warning: role " . $row[$fremoterole] . " wasn't found in moodle.  skipping $key");
                    continue;
                }
				
				// Fill the subject array
                if (!array_key_exists($row[$fremotesubject], $subjectusers)) {
                    $subjectusers[$row[$fremotesubject]] = $DB->get_field('user', 'id', array($flocalsubject => $row[$fremotesubject]) );
                }
				
				// Check if subject exist in Moodle
                if ($subjectusers[$row[$fremotesubject]] == false) {
                    error_log("Warning: [" . $row[$fremotesubject] . "] couldn't find subject user -- skipping $key");
                    // couldn't find user, skip
                    continue;
                }

				// Fill the object array
                if (!array_key_exists($row[$fremoteobject], $objectusers)) {
                    $objectusers[$row[$fremoteobject]] = $DB->get_field('user', 'id', array($flocalobject => $row[$fremoteobject]) );
                }
				
				// Check if object exist in Moodle
                if ($objectusers[$row[$fremoteobject]] == false) {
                    // couldn't find user, skip
                    error_log("Warning: [" . $row[$fremoteobject] . "] couldn't find object user --  skipping $key");
                    continue;
                }
				
				// Get the context of the object
                $context = get_context_instance(CONTEXT_USER, $objectusers[$row[$fremoteobject]]);
                mtrace("Information: [" . $row[$fremotesubject] . "] assigning " . $row[$fremoterole] . " to " . $row[$fremotesubject]
                   . " on " . $row[$fremoteobject]);
                // MOODLE 1.X => role_assign($roles[$row->{$fremoterole}]->id, $subjectusers[$row->{$fremotesubject}], 0, $context->id, 0, 0, 0, 'dbuserrel');
		// MOODLE 2.X => role_assign($roleid, $userid, $contextid, $component = '', $itemid = 0, $timemodified = '') 
		role_assign($roles[$row[$fremoterole]]->id, $subjectusers[$row[$fremotesubject]], $context->id, 'enrol_dbuserrel', 0, '');

            }

	    mtrace("Deleting old role assignations");
            // delete everything left in existing
            foreach ($existing as $key => $assignment) {
                if ($assignment->component == 'enrol_dbuserrel') {
                    mtrace("Information: [$key] unassigning $key");
                    // MOODLE 1.X => role_unassign($assignment->roleid, $assignment->userid, 0, $assignment->contextid);
	  	    role_unassign($assignment->roleid, $assignment->userid, $assignment->contextid, 'enrol_dbuserrel', 0);
                }
            }
        } else {
            error_log('Warning: [ENROL_DBUSERREL] Couldn\'t get rows from external db: '.$extdb->ErrorMsg(). ' -- no relationships to assign');
        }
    }
    $this->enrol_disconnect($extdb);
}

    /**
     * Tries to make connection to the external database.
     *
     * @return null|ADONewConnection
     */
    protected function db_init() {

        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection)
        $extdb = ADONewConnection($this->get_config('dbtype'));
        if ($this->get_config('debugdb')) {
            $extdb->debug = true;
            ob_start(); //start output buffer to allow later use of the page headers
        }

        // the dbtype my contain the new connection URL, so make sure we are not connected yet
        if (!$extdb->IsConnected()) {
            $result = $extdb->Connect($this->get_config('dbhost'), $this->get_config('dbuser'), $this->get_config('dbpass'), $this->get_config('dbname'), true);
            if (!$result) {
                return null;
            }
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->get_config('dbsetupsql')) {
            $extdb->Execute($this->get_config('dbsetupsql'));
        }

        return $extdb;
    }


/// DB Disconnect
function enrol_disconnect($extdb) {
    global $CFG;

    $extdb->Close();
}

    protected function db_addslashes($text) {
        // using custom made function for now
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    protected function db_encode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach($text as $k=>$value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return textlib::convert($text, 'utf-8', $dbenc);
        }
    }

    protected function db_decode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach($text as $k=>$value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return textlib::convert($text, $dbenc, 'utf-8');
        }
    }

} // end of class


