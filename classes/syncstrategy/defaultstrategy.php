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
 * Role-based relationship sync (default) strategy.
 *
 * @package    enrol_dbuserrel
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_dbuserrel\syncstrategy;

defined('MOODLE_INTERNAL') || die();

/**
 * (Default) Sync strategy class.
 * Hopefully alternative strategies will be implemented in future and present to users as drop-down config options.
 *
 * @package enrol_dbuserrel
 * @copyright 2019 Segun Babalola <segun@babalola.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class defaultstrategy implements \enrol_dbuserrel_syncstrategy_interface {

    private $externaldataport;
    private $internaldataport;

    public function __construct(
        \enrol_dbuserrel_dataport_interface $externaldataport,
        \enrol_dbuserrel_dataport_interface $internaldataport) {

        $this->externaldataport = $externaldataport;
        $this->internaldataport = $internaldataport;
    }

    /**
     * Main/driving function of the strategy. Used to define the overall approach of the sync strategy.
     *
     * @param  int $userid Moodle db Id of the user for whom relationships should be synced.
     * @param boolean $verbose
     * @throws \Exception
     */
    public function sync_relationships($userid, $verbose) {

        // Get relationships we want to work on from external source.
        $externaldata = $this->get_external_relationships_in_scope($userid);

        if ($verbose) {
            mtrace(count($externaldata) ." entries in the external table");
        }

        // Only continue syn process if there are externally defined relationships.
        // This behaviour can vary from sync strategy to sync strategy.
        if (count($externaldata)) {

            // Get relationships that already exist in Moodle.
            $existing = $this->internaldataport->get_relationships_in_scope($userid, $userid);

            if ($verbose) {
                mtrace(get_string('info_existingrelcount', 'enrol_dbuserrel', count($existing)));
            }

            // Get all existing Moodle roles (doing this once here for efficiency).
            $roles = $this->internaldataport->get_all_roles();

            if ($verbose) {
                mtrace(get_string('info_existingrolescount', 'enrol_dbuserrel', count($roles)));
            }

            $subjectusers = array(); // Cache mapping of localsubjectuserfield to mdl_user.id.
            $objectusers = array(); // Cache mapping of localsubjectuserfield to mdl_user.id.

            // Since there is data to process, get details of remote fields in preparation.
            $remoterolefield = $this->externaldataport->get_role_fieldname();
            $remotesubjectfield = $this->externaldataport->get_subject_fieldname();
            $remoteobjectfield = $this->externaldataport->get_object_fieldname();

            foreach ($externaldata as $key => $row) {

                // TODO: Handle coma seperated values in remoteobject field.

                // First translate remote subject and object values into equivalent Moodle user IDs (and cache them).
                $remotesubjectvalue = $row[$remotesubjectfield];
                $remoteobjectvalue = $row[$remoteobjectfield];

                $remotesubjectuserid = clean_param($this->internaldataport->get_equivalent_moodle_id($remotesubjectvalue, 'subject'),PARAM_STRINGID);
                $remoteobjectuserid = clean_param($this->internaldataport->get_equivalent_moodle_id($remoteobjectvalue, 'object'),PARAM_STRINGID);

                $localkeyvalue = $row[$remoterolefield] . '|' . $remotesubjectuserid . '|' . $remoteobjectuserid;

                // Check if the role is already assigned.
                if (array_key_exists($localkeyvalue, $existing)) {
                    // Relationship already exists in moodle db, so unset it (so we can delete everything left).
                    unset($existing[$localkeyvalue]);

                    if ($verbose) {
                    	mtrace(get_string('warn_duplicaterel', 'enrol_dbuserrel', $localkeyvalue));
                    }

                    continue;
                }

                // Ensure the remote role exists in Moodle.
                if (!array_key_exists($row[$remoterolefield], $roles)) {
                    // Role doesn't exist in moodle. skip.
                    if ($verbose) {
	                    mtrace(get_string('warn_unknownrole', 'enrol_dbuserrel',
	                        ['k' =>  $key, 'f' => $row[$remoterolefield]]));
	                }

                    continue;
                }

                // Ensure remote subject exists as a user in Moodle.
                if (!array_key_exists($row[$remotesubjectfield], $subjectusers)) {
                    if (empty($remotesubjectuserid) || !$remotesubjectuserid) {
                    	if ($verbose) {
                    	    mtrace(get_string('warn_unknownsub', 'enrol_dbuserrel',
                    	        ['k' =>  $key, 'f' => $row[$remotesubjectfield]]));
                    	}

                        // Couldn't find Moodle user record for remote subject, skip.
                        continue;
                    } else {
                        $subjectusers[$row[$remotesubjectfield]] = $remotesubjectuserid;
                    }
                }

                // Ensure remote object exists as a user in Moodle.
                if (!array_key_exists($row[$remoteobjectfield], $objectusers)) {
                    if (empty($remoteobjectuserid) || !$remoteobjectuserid) {

                    	if ($verbose) {
                    		    mtrace(get_string('warn_unknownobj', 'enrol_dbuserrel',
                    		        ['k' =>  $key, 'f' => $row[$remoteobjectfield]]));
                   		}

                        // Couldn't find Moodle user record for remote object, skip.
                        continue;
                    } else {
                        $objectusers[$row[$remoteobjectfield]] = $remoteobjectuserid;
                    }
                }

                // Get the relevant context object.
                $context = \context_user::instance($objectusers[$row[$remoteobjectfield]]);

				if ($verbose) {
	                mtrace(get_string('info_relcreated', 'enrol_dbuserrel',
	                    ['o' => $row[$remoteobjectfield], 's' =>  $row[$remotesubjectfield], 'r' => $row[$remoteobjectfield]]));
				}

                // Assign the role!
                role_assign(
                    $roles[$row[$remoterolefield]]->id,
                    $subjectusers[$row[$remotesubjectfield]],
                    $context->id,
                    'enrol_dbuserrel',
                    0,
                    ''
                );

            } // End foreach external record.

			if ($verbose) {
	            mtrace(get_string('info_deletingrels', 'enrol_dbuserrel'));
	        }

            // Delete existing roles that are no longer present in remote data source.
            foreach ($existing as $key => $assignment) {
                if ($assignment['component'] == 'enrol_dbuserrel') {

                	if ($verbose) {
                    	mtrace("Information: [$key] unassigning $key");
                    }

                    role_unassign(
                        $assignment['roleid'],
                        $assignment['userid'],
                        $assignment['contextid'],
                        'enrol_dbuserrel',
                        0
                    );
                }
            }

        } // End check on existence of external data.

        $this->externaldataport->shutdown();
    }


    /**
     * Main/driving function of the strategy. Used to define the overall approach of the sync strategy.
     *
     * @param   int $userid Moodle db Id of the user for whom relationships should be synced.
     * @return  array An array of (external) relationships to be synced.
     */
    private function get_external_relationships_in_scope($userid) {
        $localobjectvalue = "";
        $localsubjectvalue = "";

        if ($userid) {
            // Get only those relationships involving this user.
            // First need to translate the user ID value into equivalent values using definition of local fields.
            $localobjectvalue = $this->internaldataport->get_equivalent_moodle_id($userid, "object");
            $localsubjectvalue = $this->internaldataport->get_equivalent_moodle_id($userid, "subject");
        }

        return $this->externaldataport->get_relationships_in_scope($localsubjectvalue, $localobjectvalue);
    }
}
