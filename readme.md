# Moodle Scripts

(c) 2011-2012 Paul Vaughan, paulvaughan@southdevon.ac.uk

Scripts available from: [https://github.com/sdc/Moodle-Scripts](https://github.com/sdc/Moodle-Scripts)

Homepage: [http://commoodle.southdevon.ac.uk/course/view.php?id=2](http://commoodle.southdevon.ac.uk/course/view.php?id=2)

## Description

A collection of scripts which somehow assist with the administration and/or housekeeping (and possibly development) of Moodle at South Devon College.

## Licence

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.

## Scripts

The description higher up was right: these scripts have and continue to assist with housekeeping or administration of Moodle 2.x at South Devon College. These scripts are quite esoteric: they were written in a hurry to meet a specific need and may not be the best examples of what they do, but they do work, and if they save you from having to reinvent the wheel, brilliant.

### last-modified-course.php

For Moodle 1.9.x, 2.0.x.

This script can be used to show when Moodle courses were last accessed, and by whom. It displays the information as a table with extra, useful information. It will need some initial configuration (see sections 1 and 2), after which it should work.

Note that where more information may exist about a course/user, it has been added to the page as a 'title' attribute, which means that it should appear after a small delay when you hover your pointer over text on the screen.

There is a history of changes at the bottom of the script.

### innodb-ify.php

For MySQL 5.1.x. Will work on other versions of MySQL.

> **Note:** This is a script which makes changes to the underlying structure of your database. It is not 'destructive' in the sense that it will destroy your data, but it DOES change the way the data is stored and therefore *could, potentially* result in data corruption. Precautions should be taken before using this script.

This script can be used to change the collation (character set: e.g. latin, UTF8) of the database and tables, as well as the database storage engine (e.g. MyISAM, InnoDB) for all tables in a named database.  It is primarily intended to be used on the Moodle database, but can be used on any named database and modified to change only some tables, instead of all.

Originally this script was the work of another person (unknown) and has been adapted specifically to modify a Moodle database. It is crude but does the job much more quickly than you could using another tool such as PHPMyAdmin.

If you don't know why you should use this script, *DO NOT USE IT.*

### sitewide-admin-report.php

For Moodle 2.x

This script was used in the run up to our Moodle 2 upgrade, and then beyond also. It provides an overview of many aspects of all courses within a Moodle 2 installation. It is based on the similar script 'last-modified.php' but goes considerably further.

It will need some initial configuration (see sections 1 and 2 within the code), after which it should work. HOWEVER! This report was created and continually modified over a long period of time with no concern to code readability, reuse or configuration. Sone configuration options (such as ignoring specific user IDs because they are site administrators, or detecting which backup methods are in use) has been written directly into if() statements. *Here be dragons.*

Note that where more information may exist about a course/user, it has been added to the page as a 'title' attribute, which means that it should appear after a small delay when you hover your pointer over text on the screen.

There is a history of changes at the bottom of the script.

## How to Use

These are PHP scripts and require the following to run correctly:

* A web server, such as Apache or IIS
* PHP needs to be installed and working
* The scripts need to be configured:
  * The username for database access
  * Password for database access
  * The name of your database
  * The IP address or hostname of your database (this may be along the lines of _192.168.0.100_, _localhost_ or _database.domain.ac.uk_.
  * Additionally, a little more info may be required, depending on the script, such as your hostname _http://moodle.yourdomain.ac.uk/_ or database table prefix.

Note that while these scripts use the Moodle database, they don't actually need Moodle installed to function. This is on purpose, but to make these scripts more user-friendly and comaptible, they may be turned into Moodle admin reports in the future.

## Problems / Issues

There are bound to be support queries for these scripts. For help using or configuring the script, or to notify me of an error, please raise an issue on GitHub: [https://github.com/sdc/Moodle-Scripts/issues](https://github.com/sdc/Moodle-Scripts/issues])