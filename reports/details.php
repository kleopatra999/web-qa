<?php
#  +----------------------------------------------------------------------+
#  | PHP QA Website                                                       |
#  +----------------------------------------------------------------------+
#  | Copyright (c) 2005-2006 The PHP Group                                |
#  +----------------------------------------------------------------------+
#  | This source file is subject to version 3.01 of the PHP license,      |
#  | that is bundled with this package in the file LICENSE, and is        |
#  | available through the world-wide-web at the following url:           |
#  | http://www.php.net/license/3_01.txt                                  |
#  | If you did not receive a copy of the PHP license and are unable to   |
#  | obtain it through the world-wide-web, please send a note to          |
#  | license@php.net so we can mail you a copy immediately.               |
#  +----------------------------------------------------------------------+
#  | Author: Olivier Doucet <odoucet@php.net>                             |
#  +----------------------------------------------------------------------+
#   $Id$

$startTime = microtime(true);
include "../include/functions.php";

// sanitize
if (!preg_match('@^[a-z0-9]{32}$@', $_GET['signature'])) {
    exit('Invalid signature');
}
if (!preg_match('@^[0-9]{1}\.[0-9]{1}\.[0-9\.\-dev]{1,}$@', $_GET['version'])) {
    exit('invalid version');
}

$signature = $_GET['signature'];
$version  = $_GET['version'];

$DBFILE = dirname(__FILE__).'/db/'.$version.'.sqlite';

$database = new SQLite3($DBFILE, SQLITE3_OPEN_READONLY);

if (!$database) {
    $error = (file_exists($yourfile)) ? "Impossible to open, check permissions" : "Impossible to create, check permissions";
    die("Error: ".$error);
}

// GET infos from DB
$query = 'SELECT reports.* FROM failed JOIN reports ON reports.id=failed.id_report WHERE signature=X\''.$signature.'\'';

$q = $database->query($query);
$reportsArray = array();
while ($tab = $q->fetchArray(SQLITE3_ASSOC)) {
    $reportsArray[$tab['id']] = $tab;
}

$tab = $database->query('SELECT test_name FROM failed WHERE signature=X\''.$signature.'\' LIMIT 1');
list($testName) = $tab->fetchArray(SQLITE3_NUM);

// We stop everything
$database->close();

$TITLE = "Report details";
common_header();
?>
<script src="sorttable.js"></script>
<div style="margin:10px">
<h1><a href="/reports/"><img title="Go back home" src="home.png" border="0" style="vertical-align:middle;" /></a>List of reports associated</h1>
<b>Test name: </b><?php echo $testName; ?><br />
<b>Version: </b><?php echo $version; ?>
<br /><br />
<style>
#reportTable td {
    padding: 3px;
}
</style>
<table id="reportTable" class="sortable">
 <thead>
 <tr>
   <th>Date</th>
   <th>Failed tests</th>
   <th>Email</th>
   <th>&nbsp;</th>
  </tr>
 </thead>
<?php
    foreach ($reportsArray as $report) {
        echo '  <tr>'."\n";
        echo '    <td sorttable_customkey="'.strtotime($report['date']).'">'.$report['date'].'</td>'."\n";
        echo '    <td align="right" width="50">'.$report['nb_failed'].'</td>'."\n";
        echo '    <td>***'.strstr($report['user_email'], ' at ').'</td>'."\n";
        echo '    <td><a href="details.php?version='.$version.'&signature='.$signature.'&idreport='.$report['id'].'"><img src="report.png" title="View phpinfo and environment" border="0" /></a></td>'."\n";
        echo '  </tr>'."\n";
    }

?>

</table>
<?php
if (isset($_GET['idreport'])) {
?>
<hr size="1" />
<br />
<b>Goto: <a href="#phpinfo">PHPInfo</a> &nbsp; &nbsp; <a href="#buildenv">Build environment</a></b><br />
<br />
<?php

    $idreport = (int) $_GET['idreport'];
    echo '<a name="phpinfo" /><h2>PHPInfo</h2><pre>';
    echo $reportsArray[$idreport]['phpinfo'];
    echo '</pre><hr size=1 />';
    echo '<a name="buildenv" /><h2>Build environment</h2><pre>';
    echo str_replace($reportsArray[$idreport]['user_email'], '*** (truncated on purpose) ***', $reportsArray[$idreport]['build_env']);

}


echo '</div>';
$SITE_UPDATE = "<br /> Generated in ".round((microtime(true)-$startTime)*1000)." ms";
common_footer();