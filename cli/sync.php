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
 * CLI sync for full external database synchronisation.
 *
 * Sample cron entry:
 * # 5 minutes past 4am
 * 5 4 * * * $sudo -u www-data /usr/bin/php /var/www/moodle/enrol/database/cli/sync.php
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    enrol
 * @subpackage dbuserrel
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
	define('CLI_SCRIPT', true);
	
	require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');// global moodle config file.
	require_once($CFG->libdir.'/clilib.php');
	// now get cli options
	list($options, $unrecognized) = cli_get_params(array('verbose'=>false, 'help'=>false), array('v'=>'verbose', 'h'=>'help'));
	if ($unrecognized) {
		$unrecognized = implode("\n  ", $unrecognized);
		cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
	}

	if ($options['help']) {
    $help =
"Execute dbuserrel enrol sync with external database.
The enrol_dbuserrel plugin must be enabled and properly configured.
Options:
-v, --verbose         Print verbose progess information
-h, --help            Print out this help
Example:
\$sudo -u www-data /usr/bin/php enrol/database/cli/sync.php
Sample cron entry:
# 5 minutes past 4am
5 4 * * * \$sudo -u www-data /usr/bin/php /var/www/moodle/enrol/dbuserrel/cli/sync.php
";
    echo $help;
    die;
}

	if (!enrol_is_enabled('dbuserrel')) {
    		echo('enrol_dbuserrel plugin is disabled, sync is disabled'."\n");
    		exit(1);
	}
	if(!empty($_SERVER['GATEWAY_INTERFACE'])){
        error_log("should not be called from apache!");
        exit(1);
    }
	
	
    ///////error_reporting(E_ALL);
    
	
    /////////require_once($CFG->dirroot . "/enrol/dbuserrel/enrol.php");
    // ensure errors are well explained
    /////////$CFG->debug=E_ALL;
	    /////////////$enrol = new enrolment_plugin_dbuserrel();
    ////////$enrol->setup_enrolments(); 
	
	$verbose = !empty($options['verbose']);
	$enrol = enrol_get_plugin('dbuserrel');
	$result = 0;
	
	$result = $result | $enrol->setup_enrolments($verbose);
	exit($result);
