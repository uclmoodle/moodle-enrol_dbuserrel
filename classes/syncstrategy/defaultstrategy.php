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
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_dbuserrel\syncstrategy;

defined('MOODLE_INTERNAL') || die();

Class defaultstrategy implements \enrol_dbuserrel_syncstrategy_interface {

    private $externaldataport;
    private $internaldataport;

    public function __construct(
        \enrol_dbuserrel_dataport_interface $external_dataport,
        \enrol_dbuserrel_dataport_interface $internal_dataport) {

        $this->externaldataport = $external_dataport;
        $this->internaldataport = $internal_dataport;
    }

    public function sync_relationships($userid, $verbose) {

        // Todo: error_log and mtrace where necessary throughout plugin
        // Todo: Move all static strings to language file

        // Get relatinoships we want to work on from external source.
        $externaldata = $this->get_external_relationships_in_scope($userid);

        if ($verbose) {
            mtrace(count($externaldata) ." entries in the external table");
        }

        // Only continue syn process if there are externally defined relationships. This behaviour can vary from
        // sync strategy to sync strategy
        if (count($externaldata)) {

            // Get relationships that already exist in Moodle
            $existing = $this->internaldataport->get_relationships_in_scope($userid, $userid);

            if ($verbose) {
                mtrace(count($existing)." role assignment entries from dbuserrel found in Moodle DB");
            }

            // Get all existing Moodle roles
            $roles = $this->internaldataport->get_all_roles();

            if ($verbose) {
                mtrace(sizeof($roles)." role entries found in Moodle DB");
            }

            $subjectusers = array(); // cache of mapping of localsubjectuserfield to mdl_user.id (for get_context_instance)
            $objectusers = array(); // cache of mapping of localsubjectuserfield to mdl_user.id (for get_context_instance)

            // Since there is data to process, get details of local and remote fields.
            $remoterolefield = $this->externaldataport->get_role_fieldname();
            $remotesubjectfield = $this->externaldataport->get_subject_fieldname();
            $remoteobjectfield = $this->externaldataport->get_object_fieldname();

            foreach ($externaldata as $key => $row) {

                if ($verbose) {
                    print_r($row);
                }

                // TODO: Handle coma seperated values in remoteobject field

                // First translate remote subject and object values into Moodle user IDs (and cache them).
                $remotesubjectvalue = $row[$remotesubjectfield];
                $remoteobjectvalue = $row[$remoteobjectfield];
                $remotesubjectuserid = $this->internaldataport->get_equivalent_moodle_id($remotesubjectvalue, 'subject');
                $remoteobjectuserid = $this->internaldataport->get_equivalent_moodle_id($remoteobjectvalue, 'object');

                $localkeyvalue = $row[$remoterolefield] . '|' . $remotesubjectuserid . '|' . $remoteobjectuserid;

                // Check if the role is already assigned
                if (array_key_exists($localkeyvalue, $existing)) {
                    // exists in moodle db already, unset it (so we can delete everything left)
                    unset($existing[$localkeyvalue]);
                    error_log("Warning: Relationship [$localkeyvalue] exists in moodle already");
                    continue;
                }

                // Ensure the remote role exists in Moodle
                if (!array_key_exists($row[$remoterolefield], $roles)) {
                    // role doesn't exist in moodle. skip.
                    error_log("Warning: role " . $row[$remoterolefield] . " wasn't found in moodle.  skipping $key");
                    continue;
                }

                // Ensure remote subject exists as a user in Moodle
                if (!array_key_exists($row[$remotesubjectfield], $subjectusers)) {
                    if (empty($remotesubjectuserid) || !$remotesubjectuserid) {
                        error_log("Warning: [" . $row[$remotesubjectfield] . "] couldn't find subject user -- skipping $key");
                        // couldn't find Moodle user record for remote subject, skip
                        continue;
                    } else {
                        $subjectusers[$row[$remotesubjectfield]] = $remotesubjectuserid;
                    }
                }

                // Ensure remote object exists as a user in Moodle
                if (!array_key_exists($row[$remoteobjectfield], $objectusers)) {
                    if (empty($remoteobjectuserid) || !$remoteobjectuserid) {
                        error_log("Warning: [" . $row[$remoteobjectfield] . "] couldn't find object user -- skipping $key");
                        // couldn't find Moodle user record for remote object, skip
                        continue;
                    } else {
                        $objectusers[$row[$remoteobjectfield]] = $remoteobjectuserid;
                    }
                }

                // Get the context of the object
                $context = \context_user::instance($objectusers[$row[$remoteobjectfield]]);

                mtrace("Information: assigning " . $row[$remoterolefield] . " role " .
                    " to remote subject " . $row[$remotesubjectfield] . " on remote object " . $row[$remoteobjectfield]);

                role_assign(
                    $roles[$row[$remoterolefield]]->id,
                    $subjectusers[$row[$remotesubjectfield]],
                    $context->id,
                    'enrol_dbuserrel',
                    0,
                    ''
                );
            } // end foreach external record.

            mtrace("Deleting old role assignations");

            // Delete existing roles that are no longer present in remote data source
            foreach ($existing as $key => $assignment) {
                if ($assignment['component'] == 'enrol_dbuserrel') {
                    mtrace("Information: [$key] unassigning $key");

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
    }

    public function get_external_relationships_in_scope($userid) {
        $localobjectvalue = "";
        $localsubjectvalue = "";

        if ($userid) {
            // Get only those externally defined relationships involving this user.
            // First need to translate the user ID value into equivalent values using definition of local fields.
            $localobjectvalue = $this->internaldataport->get_equivalent_moodle_id($userid, "object");
            $localsubjectvalue = $this->internaldataport->get_equivalent_moodle_id($userid, "subject");
        }

        return $this->externaldataport->get_relationships_in_scope($localsubjectvalue, $localobjectvalue);
    }
}
