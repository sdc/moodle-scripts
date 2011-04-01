<?php

/**
 * Script to change MySQL database collation and/or engine
 * (c) 2011 Paul Vaughan, paulvaughan@southdevon.ac.uk
 *
 * This script is a heavily modified version of someone else's code,
 * however the original author's details have been mislaid. Apologies
 * for this. Please contact me and I will credit you. :)
 *
 * Script downloaded from, and updates available from:
 * http://commoodle.southdevon.ac.uk/course/view.php?id=2
 *
 * Last updated:    April 1st, 2011
 * Version:         1.0.0
 *
 * This script is used to change both the collation (the character set)
 * of a named database's (e.g. Moodle) tables (e.g. mdl_user) and/or
 * change the engine of the same tables from MyISAM to InnoDB.
 *
 * It will need some initial configuration (see sections 1 and 2,
 * below), after which it should work.
 *
 * WARNING!! WARNING!! WARNING!! WARNING!! WARNING!! WARNING!! WARNING!!
 *
 * 1. This script can take a long time to run, during which time
 * tables may be locked and therefore unwritable. This may lead to
 * applications 'locking up' or freezing.
 *
 * 2. This script will run your hardware quite hard, constantly. Use in
 * a low-use period for the least disruption.
 *
 * 3. If, because of 1 or 2, you stop this script executing, you may
 * leave your database in a sub-optimal state (broken). :o
 *
 * 4. BACK-UP YOUR DATA BEFORE YOU START! Changing the collation or
 * engine is just that: a change to your data. Things can and do go
 * wrong so use this script entirely at your own risk.
 *
 * 5. If you're not sure you need to use this script, you don't.
 *
 * END WARNINGS.
 *
 * There is a history of changes at the bottom of the script.
 *
 * This script is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with This script.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * SECTION 1: REQUIRED CHANGES
 * ~~~~~~~ ~  ~~~~~~~~ ~~~~~~~
 *
 * You *MUST* change the following variables. They should match the
 * details found in your Moodle's /config.php file.
 */

// Username for your database:
$dbuser     = 'username';
// Password for your database:
$dbpass     = 'pA55w0rd!';
// The name of your database:
$dbdb       =  'moodle';
// Database IP address or hostname:
$dbaddress  =  '192.168.0.1';

/**
 * SECTION 2: POSSIBLE CHANGES
 * ~~~~~~~ ~  ~~~~~~~~ ~~~~~~~
 *
 * You should check, and possibly change, the following variables.
 */

// Database table prefix:
// Generally this is 'mdl_' for Moodle but this may be different
// (e.g. 'm_') on your setup. You can leave it blank, too.
$dbprefix   = 'mdl_';

// ONLY UN-COMMENT THE NEXT LINE WHEN YOU KNOW WHAT YOU ARE DOING AND HAVE
// CHANGED THE SETTINGS ABOVE APPROPRIATELY. **TEST THIS SCRIPT FIRST**
die("<p>About to modify the database '".$dbdb."'. Please edit the code and change the name of the database you which to modify (SECTION 1), and comment out line 91 ('die(...);'), before progressing.</p>");

/**
 * END OF EDITABLE SECTION.
 * Probably best you don't change anything below this point.
 **********************************************************************/

// get the script execution start time
$time_start = microtime(true);

$db = mysql_connect($dbaddress, $dbuser, $dbpass);

if(!$db) {
    die("Cannot connect to the database: ".mysql_error());
} else {
    echo "Connected to database server.<br />\n";
    if(!mysql_select_db($dbdb)) {
        die("Cannot select the database: ".mysql_error());
    } else {
        echo "Database '$dbdb' selected.<br />\n";
    }
}

//die("<p>ENSURE you have modified the code appropriately before running this script, or IRREPERABLE DAMAGE MAY OCCUR.</p><p>You have been SO warned.</p>\n\n");

echo "<br /><strong>Changing database table collation and/or engine</strong><br />\n";

// This changes the collation of the database, not the tables within it.
echo "<br />\nDatabase: '$dbdb':"; flush();
mysql_query("ALTER DATABASE $dbdb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");
echo " done.<br />\n<br />\n"; flush();

$result = mysql_query("SHOW TABLES;");

$j=0;
while($tables = mysql_fetch_assoc($result)) {
    foreach ($tables as $value) {

        // At this point we remove two (huge (Moodle)) tables from the process as we can do them manually at a later point
        if($value != $dbprefix.'log' && $value != $dbprefix.'backup_log') {
            echo 'Table '.++$j.", '$value':"; flush();

            /**
             * Uncomment only ONE of the following three options to
             * change collation, db engine, or both.
             */

            // Uncomment to change collation of each table to utf8
            //mysql_query("ALTER TABLE $value DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");

            // Uncomment to change db engine for each table to InnoDB
            //mysql_query("ALTER TABLE $value ENGINE = InnoDB;");

            // Uncomment to change both collation of each table to utf8 AND db engine for each table to InnoDB
            mysql_query("ALTER TABLE $value ENGINE = InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");

            echo " done.<br />\n"; flush();
        }
    }
}


// get the script execution end time (more or less)
$time_end = microtime(true);
echo "<br /><strong>All done.</strong> The collation of your database/tables and/or database engine has been successfully changed. Execution took " .
    number_format(($time_end-$time_start), 2).' seconds.';

/**
 * Release History
 * ~~~~~~~ ~~~~~~~
 *
 * Version:     1.0.0
 * Released:    April 1st, 2011
 * Details:     Initial release of the script. Not pretty, but works.
 */
?>