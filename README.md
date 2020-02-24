# Database User Relationship Role assignment

## Plugin Maintenance
The latest release of the plugin has been tested on Moodle 3.7.

This plugin is currently in use at UCL as a key data integration piece for [MyFeedback](https://moodle.org/plugins/report_myfeedback). We will therefore be ensuring this works for the foreseeable future.

## Contributions welcome
We welcome merge request to improve this plugin further. Due to staff commitments, we won't be able to review these until after MoodleMoot UK/IE In April but we will definetely review any merge requests submitted here by August 2020.

## Updating from older versions
The mapping of Moodle user profile fields and additional profile fields (new feature) to match users on is now a dropdown based on the available fields, you will need to ensure you re-select the required mapping for your data integration approach.

## Installation instruction 
`git clone --branch master https://github.com/uclmoodle/moodle-enrol_dbuserrel.git moodlesite/enrol/dbuserrel/`

Setup a cron job to execute /path_to_moodle/enrol/dbuserrel/cli/sync.php 

## Original docs

This plugin allows you to configure automatic relationships between users from an external database.  

THIS PLUGIN IS IN BETA STATUS! BE CAREFULL WITH PRODUCTION ENVIRONMENT!

Users familiar with enrol/db should have no problems configuring this.

The latest release of the plugin has been tested on Moodle 3.7.

This plugin was first developed by Penny Leach <penny@catalyst.net.nz> for Moodle 1.9
then modified by Maxime Pelletier <maxime.pelletier@educsa.org> to work with Moodle 2.3, then later modified
by Segun Babalola <segun@babalola.com> for 3.7 to allow profile files to be used for mapping relationships.

This is my first experience with Moodle plugin development, so your comments are more than
welcome. Useless to say that you use this piece of code at your own risk :)

In the configuration, "Subject" represent the parent, and "Object" represent the student.

HOW TO INSTALL
==============
Prerequisites
a. SQL table containing mentee-mentor-role relationship information
b. PHP library to connect to SQL table
c. mentee and mentor already in Moodle
d. role already in Moodle

1. Download all the files in the directory {MOODLE_DIR}/enrol/dbuserrel (using git, GitHub website, or anything else)
2. Go to https://{MOODLE_URL}/admin to complete the installation
3. Fill all parameters using Moodle plugin administration interface
   (http://{MOODLE_URL}/admin/settings.php?section=enrolsettingsdbuserrel
4. Setup a cron job to execute {MOODLE_DIR}/enrol/dbuserrel/cli/sync.php
   (add -v for more output, and redirect output to log file)

Feel free to send me any comments/suggestions

Maxime Pelletier <maxime.pelletier@educsa.org>

