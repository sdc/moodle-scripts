<?php

/**
 * "Moodle Site-Wide Administrative Report" script
 * (c) 2011-2012 Paul Vaughan, paulvaughan@southdevon.ac.uk
 *
 * Script downloaded from, and updates available from:
 * http://commoodle.southdevon.ac.uk/course/view.php?id=2
 *
 * Version:     February 27th, 2012
 * Released:    1.0.0
 *
 * This script was used in the run up to our Moodle 2 upgrade, and then beyond
 * also. It provides an overview of many aspects of all courses within a Moodle
 * 2 installation. It is based on the similar script 'last-modified.php'.
 *
 * It will need some initial configuration (see sections 1 and 2,
 * below), after which it should work. HOWEVER! This report was created and
 * continually modified over a long period of time with no concern to code
 * readability, reuse or configuration. Here be dragons.
 *
 * Note that where more information may exist about a course/user, it
 * has been added to the page as a 'title' attribute, which means that
 * it should appear after a small delay when you hover your pointer
 * over text on the screen.
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
$dbuser     = '';
// Password for your database:
$dbpass     = '';
// The name of your database:
$dbdb       =  '';
// Database IP address or hostname:
$dbaddress  =  '';

// Moodle hostname (*leave* the trailing slash):
$moodle_host    = 'http://moodle.yourdomain.ac.uk/';

/**
 * SECTION 2: POSSIBLE CHANGES
 * ~~~~~~~ ~  ~~~~~~~~ ~~~~~~~
 *
 * You should check, and possibly change, the following variables.
 */

// Database table prefix:
// Generally this is 'mdl_' but this may be different (e.g. 'm_') on your setup.
$dbprefix   = 'mdl_';

// Number of rows between headers:
$repeat_header = 20;

// Date format:
// (More details here: http://php.net/manual/en/function.date.php)
$date_format        = 'M d Y, H:i';
$date_format_short  = 'M d \'y';

// Report title:
$report_title = 'Moodle Admin Report';
// Report strapline:
$report_desc = 'Report generated: <strong>'.date($date_format, time()).'</strong>. Using the database <strong>'.$dbdb.'</strong>.';

/**
 * NOTE: You may also want to change the "print_category_links()"
 * function on (or about) line 179.
 */

/**
 * END OF EDITABLE SECTIONS.
 * Probably best you don't change anything below this point.
 **********************************************************************/

// Days per year (don't ask):
$days_year = 365;

// links to important places
$course_link            = $moodle_host . 'course/view.php?id=';
$user_link              = $moodle_host . 'user/view.php?id=';
$stats_link_1           = $moodle_host . 'course/report/stats/index.php?course=';
$stats_link_2               = '&amp;time=';
$category_link          = $moodle_host . 'course/category.php?id=';
$profile_image_link_1   = $moodle_host . 'user/pix.php/';
$profile_image_link_2       = '/f1.jpg';
$enrol_instance_link    = $moodle_host . 'enrol/instances.php?id=';

// get the script execution start time
$time_start = microtime(true);

// sort out debugging
if(isset($_GET['debug'])) {
    define('DEBUG', true);
} else {
    define('DEBUG', false);
}

// sort out loading pictures
if(isset($_GET['img'])) {
    define('IMAGES', true);
} else {
    define('IMAGES', false);
}

// get a course category, maybe
if(isset($_GET['category']) && !empty($_GET['category']) && is_numeric($_GET['category'])) {
    $show_category = $_GET['category'];
} else {
    $show_category = 0;
}

// connect to the database
$db = mysql_connect($dbaddress, $dbuser, $dbpass);
if (!$db) {
    echo '    <p class="error">Could not connect to datatbase: '.mysql_error().'</p>';
    exit;
} else {
    $db_con = mysql_select_db($dbdb, $db);
    if (!$db_con) {
        echo '    <p class="error">Could not select datatbase: '.mysql_error().'</p>';
        exit;
    } else {
        if(DEBUG) { echo '    <p class="debug">Database connection succeeded</p>'."\n"; }
    }
}

// prints the headers: it's a function so we can call it repeatedly.
function print_headers() {
    echo '        <tr>'."\n";
    echo '            <th>#</th>'."\n";
    echo '            <th>ID</th>'."\n";
    echo '            <th>Stats<br>(Yrs)</th>'."\n";
    echo '            <th>Full Name</th>'."\n";
    echo '            <th>Category</th>'."\n";
    echo '            <th>Time Created</th>'."\n";
    echo '            <th class="sort">Time Modified</th>'."\n";
    echo '            <th>Years / Days<br>Since Update</th>'."\n";
    echo '            <th>Last Accessed</th>'."\n";
    if(IMAGES) {
        echo '            <th>User Image</th>'."\n";
    }
    echo '            <th>Enrol Plugin</th>'."\n";
    echo '            <th>Last Backup</th>'."\n";
    echo '            <th>Abs. Act.</th>'."\n";
    echo '        </tr>'."\n";
}

// set a max line length
function short_line($line, $line_length = 40) {
    if(strlen($line) > $line_length) {
        return trim(substr($line, 0, $line_length)).'...';
    } else {
        return $line;
    }
}

function print_category_links() {
/**
 * For speed, this function simply prints a list of category info
 * generated by the Moodle page http://moodle.yourcollege.ac.uk/course/index.php
 * with a lot of gubbins removed and hacked slightly.
 *
 * If you want to use it with your institution, you'll need to rewrite
 * this function to either 1) get the categories automatically or 2)
 * copy/paste the HTML and clear it up yourself.
 *
 * For this to actually do *anything* you'll need to uncomment the
 * print_category_links() function call on (or about) line 375.
 */
?>
    <form name="categoryform">
        <select name="categories">
            <option value="?category=">Show All</option>
            <option disabled="disabled">----------</option>
            <option value="?category=50" disabled="disabled">RESTORING AREA</option>

            <option disabled="disabled">----------</option>
            <option value="?category=2">Student Resources</option>
            <option value="?category=3">Student Resources / Tutorial</option>
            <option value="?category=4">Student Resources / Study and Learning Support Resources</option>
            <option value="?category=6">Student Resources / University Level Learners</option>
            <option value="?category=5">Student Resources / Enrichment / Extra Curricular</option>

            <option disabled="disabled">----------</option>
            <option value="?category=1">Student Services</option>
            <option value="?category=47">Equality and Diversity</option>

            <option disabled="disabled">----------</option>
            <option value="?category=11">A Level, Sport &amp; Culture</option>
            <option value="?category=15">A Level, Sport &amp; Culture / A Levels</option>
            <option value="?category=16">A Level, Sport &amp; Culture / Pre A Level (GCSE)</option>
            <option value="?category=12">A Level, Sport &amp; Culture / Art, Media &amp; Design</option>
            <option value="?category=13">A Level, Sport &amp; Culture / Performing Arts &amp; Music</option>
            <option value="?category=14">A Level, Sport &amp; Culture / Sport &amp; Adventure</option>
            <option value="?category=22">A Level, Sport &amp; Culture / HE Courses (ASC)</option>

            <option disabled="disabled">----------</option>
            <option value="?category=17">Business Advantage, Construction &amp; Hospitality</option>
            <option value="?category=48">Business Advantage, Construction &amp; Hospitality / Adult training, Projects and Partnerships</option>
            <option value="?category=19">Business Advantage, Construction &amp; Hospitality / Apprenticeships, Construction &amp; Heritage</option>
            <option value="?category=18">Business Advantage, Construction &amp; Hospitality / Business Advantage &amp; Enterprise</option>
            <option value="?category=20">Business Advantage, Construction &amp; Hospitality / Catering, Hospitality &amp; Tourism</option>
            <option value="?category=21">Business Advantage, Construction &amp; Hospitality / HE Courses (BACH)</option>

            <option disabled="disabled">----------</option>
            <option value="?category=24">Health, Community &amp; Foundation Learning</option>
            <option value="?category=25">Health, Community &amp; Foundation Learning / Children, Health, Access &amp; Public Services</option>
            <option value="?category=27">Health, Community &amp; Foundation Learning / Children, Health, Access &amp; Public Services / Access to HE</option>
            <option value="?category=26">Health, Community &amp; Foundation Learning / Children, Health, Access &amp; Public Services / Children &amp; Health</option>
            <option value="?category=28">Health, Community &amp; Foundation Learning / Children, Health, Access &amp; Public Services / Public Services</option>
            <option value="?category=29">Health, Community &amp; Foundation Learning / Foundation Learning Excellence (FLex)</option>
            <option value="?category=30">Health, Community &amp; Foundation Learning / Learning Opportunities, ACL &amp; Skills for Life</option>
            <option value="?category=32">Health, Community &amp; Foundation Learning / Learning Opportunities, ACL &amp; Skills for Life / Adult &amp; Community Learning (ACL)</option>
            <option value="?category=31">Health, Community &amp; Foundation Learning / Learning Opportunities, ACL &amp; Skills for Life / Learning Opportunities</option>
            <option value="?category=33">Health, Community &amp; Foundation Learning / Learning Opportunities, ACL &amp; Skills for Life / Skills for Life</option>
            <option value="?category=34">Health, Community &amp; Foundation Learning / HE Courses (CHAPS)</option>

            <option disabled="disabled">----------</option>
            <option value="?category=35">Science &amp; Technology</option>
            <option value="?category=42">Science &amp; Technology / Building Services &amp; Renewables</option>
            <option value="?category=44">Science &amp; Technology / Hair &amp; Beauty</option>
            <option value="?category=46">Science &amp; Technology / Hair &amp; Beauty / Beauty</option>
            <option value="?category=45">Science &amp; Technology / Hair &amp; Beauty / Hairdressing</option>
            <option value="?category=36">Science &amp; Technology / Marine, Automotive, Computing &amp; Engineering</option>
            <option value="?category=38">Science &amp; Technology / Marine, Automotive, Computing &amp; Engineering / Automotive</option>
            <option value="?category=39">Science &amp; Technology / Marine, Automotive, Computing &amp; Engineering / Computing</option>
            <option value="?category=40">Science &amp; Technology / Marine, Automotive, Computing &amp; Engineering / Engineering</option>
            <option value="?category=37">Science &amp; Technology / Marine, Automotive, Computing &amp; Engineering / Marine</option>
            <option value="?category=43">Science &amp; Technology / Science &amp; Land Based</option>
            <option value="?category=41">Science &amp; Technology / HE Courses (MACE)</option>

            <option disabled="disabled">----------</option>
            <option value="?category=10">Teacher Training</option>

            <option disabled="disabled">----------</option>
            <option value="?category=53">Functional Skills</option>

            <option disabled="disabled">----------</option>
            <option value="?category=7">Staff</option>
            <option value="?category=49">Staff / Staff Resources</option>
            <option value="?category=8">Staff / CPD</option>
            <option value="?category=52">Staff / CPD / Moodle training (TEMP)</option>
            <option value="?category=9">Staff / Team Spaces</option>
            <option value="?category=23">Staff / LTRS Holding Area</option>
            <option value="?category=54">Staff / LTRS Holding Area / Courses to delete</option>
            <option value="?category=51">Staff / LTRS Holding Area / Restored but not req</option>
        </select>
        <button type="button" onClick="window.location=document.categoryform.categories.options[document.categoryform.categories.selectedIndex].value">Show this category</button>
    </form>
<?php
}

/***********************************************************************
 * Code to get the courses with absence activities
 */
$badwords = array (
    'absence',
    'absense',
    'abcence',
    'abcense',
);
// array of places to look for 'bad' words
$location = array (
    'table' => array (
        'mdl_course_sections',
        'mdl_course_sections',
        'mdl_resource',
        'mdl_page',
        'mdl_url',
        'mdl_label',
    ),
    'column' => array (
        'summary',
        'name',
        'name',
        'name',
        'name',
        'name',
    ),
);
// somewhere to store the results
$absences = array();
// quick check to ensure the above array is balanced
if (count($location['table']) != count($location['column'])) {
    echo 'Something went wrong with the $location array, get an adult, quick!';
} else {
    for ($j = 0; $j < count($location['table']); $j++) {
        for ($k = 0; $k < count($badwords); $k++) {
            $qry = 'SELECT * FROM '.$location['table'][$j].' WHERE '.$location['column'][$j].' LIKE "%'.$badwords[$k].'%";';
            $res = mysql_query($qry);

//echo 'J: '.$j.'; K: '.$k.';'."<br />\n";

            if (mysql_num_rows($res) > 0) {
                while ($row = mysql_fetch_assoc($res)) {
                    $absences[$row['course']] = $row['course'];

//echo $row['course']."<br />\n";

                }
            }
        }
    }
}
asort($absences);

// debugging
//echo '<pre>';
//print_r($absences);
//echo '</pre>';

// end of absence stuff

/**
 * keep some statistics about the results we gather
 */
$stats_backup   = array ('ok' => 0, 'notyetrun' => 0, 'skipped' => 0, 'unfinished' => 0, 'error'=> 0);
$stats_absence  = array ('yes' => 0, 'no' => 0);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
    <title><?php echo $report_title; ?></title>
    <style type="text/css">
        body { padding: 0px; }
        * { font-family: "Droid Sans", "Trebuchet MS", Tahoma, Verdana, Helvetica, Arial, sans-serif; }
        .debug { border: 1px solid #0f0; background-color: #bfb; -moz-border-radius: 1em; padding: 0.2em; padding-left: 1em; font-style: italic; font-size: 0.8em; color: #373; }
        .error { border: 1px solid #f00; background-color: #fbb; -moz-border-radius: 1em; padding: 0.2em; color: #f00; text-align: center; }
        table { border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 1px; }
        th { background-color: #778; color: #fff; }
        p, th, td { font-size: 0.7em; }
        .center { text-align: center; }
        .sort { color: #070; }
        th.sort { color: #5b5; }
        a img { border: none; }
        .nowrap { white-space: nowrap; }
        tr:nth-child(even) { background: #eef; }
        tr:hover { background-color: #bbf; }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
    <h1><?php echo $report_title; ?></h1>
    <p><?php echo $report_desc; ?></p>
<?php


/**
 * Uncomment the following to print the list of categories as a drop-
 * down menu. The function to do this is on (or about) line 173.
 */
//print_category_links();


// print the course category, if we're in one
if($show_category > 0) {
    $sql6 = "SELECT name FROM ".$dbprefix."course_categories WHERE id = ".$show_category." LIMIT 1;";
    $res6 = mysql_query($sql6);
    if(!$res6) {
        echo '<p class="error">Could not run database query SQL6: '.mysql_error().'</p>';
    } else {
        if(DEBUG) { echo '    <p class="debug">Category Name query (SQL6) successful. ['.$sql.'] ['.mysql_num_rows($res).' rows]</p>'."\n"; }
    }
    $row6 = mysql_fetch_assoc($res);

    echo '<p>Now showing the following category: <strong>'.$row6['name'].'</strong>. (<a href="?category=">Show all</a>?';
    if(IMAGES) {
        echo ' <a href="?category='.$show_category.'">Hide images</a>?)</p>'."\n";
    } else {
        echo ' <a href="?category='.$show_category.'&amp;img">Show images</a>?)</p>'."\n";
    }
} else {
    if(IMAGES) {
        echo '<p>Now showing: <strong>all categories</strong>. <a href="?category=">Hide images</a>?</p>'."\n";
    } else {
        echo '<p>Now showing: <strong>all categories</strong>. <a href="?category=&amp;img">Show images</a>?</p>'."\n";
    }
}

// get the course details
$sql1 = "SELECT ".$dbprefix."course.id AS cid, fullname, shortname, name, ".$dbprefix."course_categories.id AS ccid, ".$dbprefix."course.timemodified, timecreated FROM ".$dbprefix."course, ".$dbprefix."course_categories WHERE ".$dbprefix."course.category = ".$dbprefix."course_categories.id";
// if there's a category id in the URL
if($show_category) {
    $sql1 .= " AND ".$dbprefix."course_categories.id = ".$show_category;
}
// DEVELOPMENT: limit the output severely.
//$sql1 .= " ORDER BY timemodified ASC LIMIT 10;";
// PRODUCTION: Get the job lot (nearly 1,100).
//$sql1 .= " ORDER BY timemodified ASC;";
$sql1 .= " ORDER BY ".$dbprefix."course.id ASC;";

$res1 = mysql_query($sql1);
if(!$res1) {
    echo '<p class="error">Could not run database query SQL1: '.mysql_error().'</p>';
    exit;
} else {
    if(DEBUG) { echo '    <p class="debug">SQL1 query successful. ['.$sql1.'] ['.mysql_num_rows($res1).' rows]</p>'."\n"; }
}

// start the table
echo '    <table>'."\n";

// call the print headers function for the first time
print_headers();

// just a few throw-away variables for looping and counting and such
// there's an $l in the while loop too
$j=0;
$k=0;
while($row = mysql_fetch_assoc($res1)) {
    if($k == $repeat_header) {
        print_headers();
        $k = 1;
    } else {
        $k++;
    }
    echo '        <tr>'."\n";
    echo '            <td class="center">'.++$j.'</td>'."\n";
    echo '            <td class="center"><a title="Opens in a new window/tab" href="'.$course_link.$row['cid'].'" target="_blank">'.$row['cid'].'</a></td>'."\n";
    echo '            <td class="center"><a href="'.$stats_link_1.$row['cid'].$stats_link_2.'32" target="_blank">1</a> ';
//    echo '            <a href="'.$stats_link_1.$row['cid'].$stats_link_2.'44" target="_blank">2</a> ';
//    echo '            <a href="'.$stats_link_1.$row['cid'].$stats_link_2.'56" target="_blank">3</a> ';

    echo '</td>'."\n";
    echo '            <td title="'.$row['fullname'].' ['.$row['shortname'].']" >'.short_line($row['fullname']).'</td>'."\n";

    /**
     * course categories
     */
    $sql3 = "SELECT path FROM ".$dbprefix."course_categories WHERE id = ".$row['ccid']." LIMIT 1;";
    $res3 = mysql_query($sql3);
    if(!$res3) {
        echo '<p class="error">Could not run database query SQL3: '.mysql_error().'</p>';
    } else {
        if(DEBUG) { echo '    <p class="debug">SQL3 query successful. ['.$sql3.'] ['.mysql_num_rows($res3).' rows]</p>'."\n"; }
    }
    $row3 = mysql_fetch_assoc($res3);

    // strip off the first slash
    $paths = substr($row3['path'], 1, strlen($row3['path'])-1);
    // separate the string
    $paths = explode('/', $paths);
    $qry_ccids = '';
    foreach($paths as $path) {
        $qry_ccids .= $path . ', ';
    }
    // strip off the last comma and space
    $qry_ccids = substr($qry_ccids, 0, strlen($qry_ccids)-2);

    $sql4 = "SELECT id, name FROM ".$dbprefix."course_categories WHERE id IN (".$qry_ccids.");";
    $res4 = mysql_query($sql4);
    if(!$res4) {
        echo '<p class="error">Could not run database query SQL4: '.mysql_error().'</p>';
    } else {
        if(DEBUG) { echo '    <p class="debug">SQL4 query successful. ['.$sql4.'] ['.mysql_num_rows($res4).' rows]</p>'."\n"; }
    }
    echo '            <td>';
    $l=0;
    $rows = mysql_num_rows($res4);
    while($row4 = mysql_fetch_assoc($res4)) {
        echo '<a href="'.$category_link.$row4['id'].'" target="_blank">'.short_line($row4['name'], 10).'</a>';
        $l++;
        if($l != $rows) {
            echo ' &rarr; ';
            //echo '<br />';
        }
    }
    echo '</td>'."\n";
    // end course categories

    echo '            <td class="center nowrap">'.date($date_format, $row['timecreated']).'</td>'."\n";
    echo '            <td class="center nowrap sort">'.date($date_format, $row['timemodified']).'</td>'."\n";

    // code for years-and-days-since... (probably sub-optimal)
    $days = number_format(((((time()-$row['timemodified'])/60)/60)/24), 0, '', '');
    $years = substr(($days/$days_year), 0, 1);
    $remainder = number_format($days - ($days_year * $years), 0);
    echo '            <td title="'.number_format(((((time()-$row['timemodified'])/60)/60)/24), 0).' days" class="center nowrap">'.$years.'y '.$remainder.'d</td>'."\n";

    // get the most recent non-admin view entry from log table
    $sql2 = "SELECT time, userid, firstname, lastname FROM ".$dbprefix."log, ".$dbprefix."user WHERE ".$dbprefix."log.userid = ".$dbprefix."user.id AND course = '".$row['cid']."' AND action = 'view' AND userid NOT IN (3, 4, 5, 7, 8) ORDER BY time DESC LIMIT 1;";
    $res2 = mysql_query($sql2);
    if(!$res2) {
        echo '<p class="error">Could not run database query SQL2: '.mysql_error().'</p>';
    } else {
        if(DEBUG) { echo '    <p class="debug">SQL2 query successful. ['.$sql2.'] ['.mysql_num_rows($res2).' rows]</p>'."\n"; }
    }
    $row2 = mysql_fetch_assoc($res2);
    if($row2['userid'] <> '') {
        $last_accessed = '<a title="Opens in a new window/tab" href="'.$user_link.$row2['userid'].'" target="_blank">'.$row2['firstname'].' '.$row2['lastname'].'</a><br>'.date($date_format, $row2['time']);
    } else {
        $last_accessed = 'Never accessed';
    }
    echo '            <td class="center nowrap">'.$last_accessed.'</td>'."\n";

    /**
     * do images
     */
    if(IMAGES) {
        if($row2['userid'] <> '') {
            $sql5 = "SELECT description, lastlogin FROM ".$dbprefix."user WHERE id = ".$row2['userid']." LIMIT 1;";
            $res5 = mysql_query($sql5);
            if(!$res5) {
                echo '<p class="error">Could not run database query SQL5: '.mysql_error().'</p>';
            } else {
                if(DEBUG) { echo '    <p class="debug">SQL5 query successful. ['.$sql5.'] ['.mysql_num_rows($res5).' rows]</p>'."\n"; }
            }
            $row5 = mysql_fetch_assoc($res5);
            echo '            <td><a href="'.$user_link.$row2['userid'].'" target="_blank"><img src="'.$profile_image_link_1.$row2['userid'].$profile_image_link_2.'" title="Last login: '.date($date_format, $row5['lastlogin']).'. Description: &quot;'.trim(strip_tags($row5['description'])).'&quot;" alt="Last login: '.date($date_format, $row5['lastlogin']).'. Description: &quot;'.trim(strip_tags($row5['description'])).'&quot;"></a></td>'."\n";
        } else {
            echo '            <td></td>'."\n";
        }
    }

    /**
     * enrolment plugins
     */
    $sql7 = "SELECT enrol, status FROM ".$dbprefix."enrol WHERE ".$dbprefix."enrol.courseid = '".$row['cid']."' ORDER BY enrol ASC;";
    $res7 = mysql_query($sql7);
    if(!$res7) {
        echo '<p class="error">Could not run database query SQL7: '.mysql_error().'</p>';
    } else {
        if(DEBUG) { echo '    <p class="debug">SQL7 query successful. ['.$sql7.'] ['.mysql_num_rows($res7).' rows]</p>'."\n"; }
    }
    $build = '';
    while ($row7 = mysql_fetch_assoc($res7)) {
        $build .= $row7['enrol'].' ';
        if ($row7['status'] == '1') {
            $build .= '<span style="color: #777;">[h]</span> ';
        }
    }
    $build = trim($build);
    echo '            <td class="center">'.$build;
    if ($build !== 'manual self' && $build !== 'category manual self') {
        echo ' <a style="color: #f00;" href="'.$enrol_instance_link.$row['cid'].'" target="_blank">Edit?</a>';
    }
    echo '</td>'."\n";


    /**
     * backup status
     */
    $sql8 = "SELECT laststarttime, laststatus FROM mdl_backup_courses WHERE id = '".$row['cid']."';";
    $res8 = mysql_query($sql8);
    if(!$res8) {
        echo '<p class="error">Could not run database query SQL8: '.mysql_error().'</p>';
    } else {
        if(DEBUG) { echo '    <p class="debug">SQL8 query successful. ['.$sql8.'] ['.mysql_num_rows($res8).' rows]</p>'."\n"; }
    }

    $row8 = mysql_fetch_assoc($res8);

    if (mysql_num_rows($res8) != 0) {
        $build = '';
        switch ($row8['laststatus']) {
            case 1:
                $build = '<span style="color: #070;">OK</span>';
                $stats_backup['ok']++;
                break;
            case 2:
                $build = '<span style="color: #f00;">Unfinished</span>';
                $stats_backup['unfinished']++;
                break;
            case 3:
                $build = '<span style="color: #f70;">Skipped</span>';
                $stats_backup['skipped']++;
                break;
            case 0:
                $build = '<span style="color: #f00;">Error</span>';
                $stats_backup['error']++;
                break;
        }
        echo '<td class="center">'.date($date_format_short, $row8['laststarttime']).':<br />'.$build.'</td>'."\n";
    } else {
        // if no results, the backup has never run (this may be fine)
        echo '<td class="center" style="color: #f70;">Not yet run</td>'."\n";
        $stats_backup['notyetrun']++;
    }

    /**
     * absence activity
     */
    if(isset($absences[$row['cid']]) &&  $row['cid'] == $absences[$row['cid']]) {
        echo '<td class="center" style="color: #070;">Yes</td>';
        $stats_absence['yes']++;
    } else {
        echo '<td class="center" style="color: #f00;">No!</td>';
        $stats_absence['no']++;
    }




    // finish off the row
    echo '        </tr>'."\n";

    // flush to screen whatever we have so far
    flush();
}

// end of table
echo '    </table>'."\n";

// totals and such rows
$total  = $stats_backup['ok']
        + $stats_backup['notyetrun']
        + $stats_backup['skipped']
        + $stats_backup['unfinished']
        + $stats_backup['error'];
echo '<p> Backup Stats: ';
echo '<span style="color: #070;">OK: '.$stats_backup['ok'].' ('.number_format(($stats_backup['ok']/$total)*100, 1)."%)</span>, \n";
echo '<span style="color: #D1BF00;">Not Yet Run: '.$stats_backup['notyetrun'].' ('.number_format(($stats_backup['notyetrun']/$total)*100, 1)."%)</span>, \n";
echo '<span style="color: #f70;">Skipped: '.$stats_backup['skipped'].' ('.number_format(($stats_backup['skipped']/$total)*100, 1)."%)</span>, \n";
echo '<span style="color: #FF4600;">Unfinished: '.$stats_backup['unfinished'].' ('.number_format(($stats_backup['unfinished']/$total)*100, 1)."%)</span>, \n";
echo '<span style="color: #f00;">Error: '.$stats_backup['error'].' ('.number_format(($stats_backup['error']/$total)*100, 1)."%)</span>.\n";
echo '</p>'."\n";

$total = $stats_absence['yes'] + $stats_absence['no'];
echo '<p>Absence Activities: ';
echo '<span style="color: #070;">Yes: '.$stats_absence['yes'].' ('.number_format(($stats_absence['yes']/$total)*100, 1)."%)</span>, \n";
echo '<span style="color: #f00;">No: '.$stats_absence['no'].' ('.number_format(($stats_absence['no']/$total)*100, 1)."%)</span>.\n";

// get the script execution end time (more or less)
$time_end = microtime(true);

echo '<p>Execution took '.number_format(($time_end-$time_start), 2).' seconds.</p>'."\n";

// last bit of code needed
mysql_close($db);
if(DEBUG) { echo '    <p class="debug">Database connection closed.</p>'."\n"; }

/**
 * Release History
 * ~~~~~~~ ~~~~~~~
 *
 * Version:     1.0.0
 * Released:    February 27th, 2012
 * Details:     Initial public release of this script.
 */
?>
</body>
</html>
