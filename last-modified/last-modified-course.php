<?php

/**
 * "Moodle Last-Modified Course Report" script
 * (c) 2011 Paul Vaughan, paulvaughan@southdevon.ac.uk
 *
 * Script downloaded from, and updates available from:
 * http://commoodle.southdevon.ac.uk/course/view.php?id=2
 *
 * Last updated:    April 1st, 2011
 * Version:         1.0.1
 * Moodle Versions: 1.9.x, 2.0.x
 *
 * This script can be used to show when Moodle courses were last
 * accessed, and by whom. It displays the information as a table with
 * extra, useful information.
 *
 * It will need some initial configuration (see sections 1 and 2,
 * below), after which it should work.
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
$dbuser     = 'username';
// Password for your database:
$dbpass     = 'pA55w0rd!';
// The name of your database:
$dbdb       =  'moodle';
// Database IP address or hostname:
$dbaddress  =  '192.168.0.1';

// Moodle hostname (*leave* the trailing slash):
$moodle_host    = 'http://moodle.outstanding-college.ac.uk/';

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

// Maximum length, in characters, of a course's full name:
$max_line_length = 40;

// Date format:
// (More details here: http://php.net/manual/en/function.date.php)
$date_format = 'M d Y, H:i';

// Report title:
$report_title = 'Moodle Last-Modified Course Report';
// Report strapline:
$report_desc = 'Report generated: <strong>'.date($date_format, time()).'</strong>. Using the database <strong>'.$dbdb.'</strong>.';

// Days per year (don't ask):
$days_year = 365;

/**
 * NOTE: You may also want to change the "print_category_links()"
 * function on (or about) line 177.
 */

/**
 * END OF EDITABLE SECTIONS.
 * Probably best you don't change anything below this point.
 **********************************************************************/

// links to important places
$course_link            = $moodle_host . 'course/view.php?id=';
$user_link              = $moodle_host . 'user/view.php?id=';
$stats_link_1           = $moodle_host . 'course/report/stats/index.php?course=';
$stats_link_2               = '&amp;time=';
$category_link          = $moodle_host . 'course/category.php?id=';
$profile_image_link_1   = $moodle_host . 'user/pix.php/';
$profile_image_link_2       = '/f1.jpg';

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
    echo '        </tr>'."\n";
}

// set a max line length
function short_line($line) {
    global $max_line_length;
    if(strlen($line) > $max_line_length) {
        return trim(substr($line, 0, $max_line_length)).'...';
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
 * function call on (or about) line 286.
 */
?>
    <form name="categoryform">
        <select name="categories">
            <option value="?category=">Show All</option>
            <option disabled="disabled">----------</option>
            <option value="?category=64">Student Resources</option>
            <option value="?category=77">- Study and Learning Support Resources</option>
            <option value="?category=52">- Enrichment / Extra Curricular</option>
            <option value="?category=70">- University Level Learners</option>
            <option disabled="disabled">----------</option>
            <option value="?category=81">Student Services</option>
            <option disabled="disabled">----------</option>
            <option value="?category=60">Arts and Leisure Industries</option>
            <option value="?category=25">- Art, Media and Design</option>
            <option value="?category=21">- Catering, Hospitality and Tourism</option>
            <option value="?category=8">- Sport and Adventure</option>
            <option value="?category=54">- Music and Performing Arts</option>
            <option value="?category=66">- HE courses (ALI)</option>
            <option disabled="disabled">----------</option>
            <option value="?category=29">Business Advantage</option>
            <option value="?category=10">- Teacher Training</option>
            <option value="?category=47">- Work Based Learning</option>
            <option value="?category=71">- Management and Short Courses</option>
            <option disabled="disabled">----------</option>
            <option value="?category=63">A Levels, Business and Land Based Studies</option>
            <option value="?category=11">- Access to HE</option>
            <option value="?category=13">- A Levels</option>
            <option value="?category=6">- Business and Computing</option>
            <option value="?category=41">- Science and Land-based studies</option>
            <option value="?category=12">- GCSE</option>
            <option value="?category=67">- HE courses (BGED)</option>
            <option value="?category=80">- Functional Skills</option>
            <option disabled="disabled">----------</option>
            <option value="?category=61">Health, Community &amp; Foundation Learning</option>
            <option value="?category=27">- ACL</option>
            <option value="?category=23">- Children, Health and Public Services</option>
            <option value="?category=14">- Learning Opportunities</option>
            <option value="?category=36">- Skills for Life</option>
            <option value="?category=56">- FLex</option>
            <option value="?category=68">- HE Courses (CLOPPs)</option>
            <option value="?category=83">- Torbay Link</option>
            <option disabled="disabled">----------</option>
            <option value="?category=62">Technology</option>
            <option value="?category=18">- Automotive, Engineering and Marine Engineering</option>
            <option value="?category=49">- Beauty and Complementary Therapies</option>
            <option value="?category=16">- Construction</option>
            <option value="?category=28">- Hairdressing</option>
            <option value="?category=46">- Plumbing and Electrical Installation</option>
            <option value="?category=69">- HE Courses (Tech)</option>
            <option disabled="disabled">----------</option>
            <option value="?category=65">Staff Resources</option>
            <option value="?category=73">- CPD</option>
            <option value="?category=17">- - Moodle Development </option>
            <option value="?category=85">- - - Worcester Tech shared example PAL packs (for LTRS use only)</option>
            <option value="?category=79">- - Sub-Courses (linked to from other areas only)</option>
            <option value="?category=74">- Team Spaces</option>
            <option value="?category=75">- Sharing Resources</option>
            <option disabled="disabled">----------</option>
            <option value="?category=45">External User Spaces</option>
        </select>
        <button type="button" onClick="window.location=document.categoryform.categories.options[document.categoryform.categories.selectedIndex].value">Show this category</button>
    </form>
<?php
}

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
        th, td { border: 1px solid #bbb; padding: 1px 5px; }
        th { background-color: #556; color: #fff; }
        p, th, td { font-size: 0.8em; }
        .center { text-align: center; }
        .sort { color: #070; }
        th.sort { color: #5b5; }
        a img { border: none; }
        .nowrap { white-space: nowrap; }
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
$sql1 .= " ORDER BY timemodified ASC;";

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
//    echo '<a href="'.$stats_link_1.$row['cid'].$stats_link_2.'44" target="_blank">2</a> ';
    echo '<a href="'.$stats_link_1.$row['cid'].$stats_link_2.'56" target="_blank">3</a> ';
//    echo '<a href="'.$stats_link_1.$row['cid'].$stats_link_2.'68" target="_blank">4</a> ';
    echo '<a href="'.$stats_link_1.$row['cid'].$stats_link_2.'80" target="_blank">5</a> </td>'."\n";
    echo '            <td title="'.$row['fullname'].' ['.$row['shortname'].']" >'.short_line($row['fullname']).'</td>'."\n";

    // course categories
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
        echo '<a href="'.$category_link.$row4['id'].'" target="_blank">'.$row4['name'].'</a>';
        $l++;
        if($l != $rows) {
            echo ' &rarr; ';
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
    $sql2 = "SELECT time, userid, firstname, lastname FROM ".$dbprefix."log, ".$dbprefix."user WHERE ".$dbprefix."log.userid = ".$dbprefix."user.id AND course = '".$row['cid']."' AND action = 'view' AND userid NOT IN (3, 1181, 13385, 13730) ORDER BY time DESC LIMIT 1;";
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

    // do images
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

    // finish off the row
    echo '        </tr>'."\n";

    // flush to screen whatever we have so far
    flush();
}

// end of table
echo '    </table>'."\n";

// get the script execution end time (more or less)
$time_end = microtime(true);

echo '    <p>Execution took '.number_format(($time_end-$time_start), 2).' seconds.</p>'."\n";

// last bit of code needed
mysql_close($db);
if(DEBUG) { echo '    <p class="debug">Database connection closed.</p>'."\n"; }

/**
 * Release History
 * ~~~~~~~ ~~~~~~~
 *
 * Version:     1.0.1
 * Released:    April 1st, 2011
 * Details:     Removed the '1.9' from the title as the script works well in
 *                  Moodle 2.0.x (except for pictures, which use a different
 *                  path and handle a lack of profile image differently).
 *
 * Version:     1.0.0
 * Released:    March 16th, 2011
 * Details:     Initial public release of this script.
 *              Produces nice output on browser and valid code.
 *              Takes between 2-5 minutes to check through 1,100
 *                  courses, depending on db server and network load.
 */
?>
    <p style="text-align: right;">
        <a href="http://validator.w3.org/check?uri=referer" target="_blank">
            <img src="http://www.w3.org/Icons/valid-html401" style="border:0;width:88px;height:31px" alt="Valid HTML 4.01 Transitional">
        </a>
        <a href="http://jigsaw.w3.org/css-validator/check/referer" target="_blank">
            <img src="http://jigsaw.w3.org/css-validator/images/vcss-blue" style="border:0;width:88px;height:31px" alt="Valid CSS">
        </a>
    </p>
</body>
</html>