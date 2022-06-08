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

$string['pluginname'] = 'DB User role assignment';
$string['dbtype'] = 'Database type';
$string['dbtype_desc'] = 'ADOdb database driver name, type of the external database engine.';
$string['dbhost'] = 'Server IP name or number';
$string['dbhost_desc'] = 'Type database server IP address or host name';
$string['dbuser'] = 'Server user';
$string['dbpass'] = 'Server password';
$string['dbname'] = 'Database name';
$string['dbtable'] = 'Database table';
$string['dbencoding'] = 'Database encoding';
$string['description'] = 'You can use a external database (of nearly any kind) to control your relationships between users. It is assumed your external database contains a field containing two user IDs, and a Role ID.  These are compared against fields that you choose in the local user and role tables';
$string['enrolname'] = 'External Database (User relationships)';
$string['localrolefield'] = 'Local role field';
$string['localsubjectuserfield'] = 'Local subject field';
$string['localobjectuserfield'] = 'Local object field';
$string['localrolefield_desc'] = 'The name of the field in the roles table that we are using to match entries in the remote database (eg shortname).';
$string['localsubjectuserfield_desc'] = 'The name of the field in the user table that we are using to match entries in the remote database (eg idnumber). for the <i>subject</i> role assignment';
$string['localobjectuserfield_desc'] = 'The name of the field in the user table that we are using to match entries in the remote database (eg idnumber). for the <i>object</i> role assignment';
$string['remote_fields_mapping'] = 'Database field mapping';
$string['remoterolefield'] = 'Remote role field';
$string['remotesubjectuserfield'] = 'Remote subject field';
$string['remoteobjectuserfield'] = 'Remote object field';
$string['remoterolefield_desc'] = 'The name of the field in the remote table that we are using to match entries in the roles table.';
$string['remotesubjectuserfield_desc'] = 'The name of the field in the remote table that we are using to match entries in the user table for the <i>subject</i> role assignment';
$string['remoteobjectuserfield_desc'] = 'The name of the field in the remote table that we are using to match entries in the user table for the <i>object</i> role assignment';
$string['server_settings'] = 'External Database Server Settings';
$string['pluginname_desc'] = 'You can use an external database (of nearly any kind) to control your mentor role. It is assumed your external database contains at least a field containing a student username, a mentor role, and a field containing a mentor username. These are compared against fields that you choose in the local role and user tables.';
$string['settingsheaderdb'] = 'External database connection';
$string['remoteenroltable'] = 'Remote user enrolment table';
$string['remoteenroltable_desc'] = 'Specify the name of the table that contains list of user enrolments. Empty means no user enrolment sync.';

$string['dbuserrelsynctask'] = 'DB User role assignment sync task'; // CATALYST CUSTOM.
$string['failure_initialisation'] = 'Error: [ENROL_DBUSERREL] Initialisation/setup failed : {$a}';
$string['failure_sync_operation'] = 'Failed to sync user relationships because {$a}';
$string['plugindisabled'] = 'enrol_dbuserrel plugin is disabled, sync is disabled';
$string['apachedisallowed'] = 'should not be called from apache!';
$string['failure_mapfields'] = 'Failed to get list of all mappable fields because {$a}';
$string['failure_mapfieldclass'] = 'Attempt to create mapping field using non-compliant class';
$string['failure_mapfieldcnf'] = 'Mapping field class {$a} not found';
$string['failure_localsubtypesetup'] = 'Attempt to set invalid type for local subject';
$string['failure_localsubnotport'] = 'Local subject is not set in internal data port';
$string['failure_localobtypesetup'] = 'Attempt to set invalid type for local object';
$string['failure_localobnotport'] = 'Local object is not set in internal data port';
$string['failure_localrolesetup'] = 'Attempt to set invalid local role';
$string['failure_localrolenotset'] = 'Local role is not set in internal data port';
$string['failure_getexistingrels'] = 'Unable to query for existing Moodle relationships: {$a}';
$string['failure_unknownporttype'] = 'Attempt to create unknown type of data port (must be EXTERNAL or INTERNAL';
$string['failure_remotesubtype'] = 'Attempt to set invalid type for remote subject';
$string['failure_remoteobtype'] = 'Attempt to set invalid type for remote object';
$string['failure_remoteroletype'] = 'Attempt to set invalid remote role';
$string['failure_dbinit'] = 'Failed to initialise external database. because {$a}';
$string['failure_dbconn'] = 'Failed to initialise external database. Error: [ENROL_DBUSERREL] Could not make a connection. DB resource is null after init.';
$string['failure_remoterolefetch'] = 'Failed to get all roles from remote data source: {$a}';
$string['info_existingrelcount'] = '{$a} role assignment entries from dbuserrel found in Moodle DB';
$string['info_existingrolescount'] = '{$a} role entries found in Moodle DB';
$string['warn_duplicaterel'] = 'Warning: Relationship [{$a}] exists in Moodle already';
$string['warn_unknownrole'] = 'Warning: role {$a->f} was not found in Moodle.  skipping {$a->k}';
$string['warn_unknownsub'] = 'Warning: [{$a->f}] could not find subject user -- skipping {$a->k}';
$string['warn_unknownobj'] = 'Warning: [{$a->f}] could not find object user -- skipping {$a->k}';
$string['info_relcreated'] = 'Information: assigning {$a->r} role to remote subject {$a->s} on remote object {$a->o}';
$string['info_deletingrels'] = 'Deleting old role assignations';
$string['failure_uidtranslate'] = 'Failed to translate Moodle user Id {$a->u} into a {$a->id} user table field value';
$string['failure_uidtranslateprofile'] = 'Failed to translate Moodle user Id {$a->u} into a {$a->id} profile field value';
$string['failure_moodleuidtrl'] = 'Unable to translate user table value {$a->v} to a Moodle user ID because {$a->err}';
$string['failure_profilevaluetranslate'] = 'Unable to translate profile value {$a->v} to a Moodle user ID because {$a->err}';
$string['privacy:metadata'] = 'plugin does not store any personal data.';
