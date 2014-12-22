<?php

 /*
 =====================================================
 Name ........: ACP Startseite
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: home.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 16.07.2011 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 ACP Startseite mit CMS Informationen.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
///////////////////////////////////////////////////////

/* Begruessung */
echo "<h1 class=\"first\">Hallo ".$_SESSION['admin_name']."</h1>";

/* Letzter Login */
$TageDE = array("So", "Mo", "Di", "Mi", "Do", "Fr", "Sa");


echo printBoxStart();

/* Admin Infos */
if ($_SESSION['admin_lastlogin'] > 0) {
	echo printBox("Letzte Anmeldung", printDate($_SESSION['admin_lastlogin'])
			." ".date(FORMAT_TIME, $_SESSION['admin_lastlogin']));
	echo printBox("IP Adresse letzte Anmeldung", $_SESSION['admin_ipadress']);
}

/* Daten Infos */
$quantity_cat = 0;
$quantity_page = 0;
$result = mysql_query("SELECT menu_is_categorie, count(*) as quantity
		FROM ".DB_TABLE_ROOT."cms_menu GROUP BY menu_is_categorie", DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($result)) {
	if ($row['menu_is_categorie'])
		$quantity_cat = $row['quantity'];
	else
		$quantity_page = $row['quantity'];
}
if (MENU_MAX_LEVEL_CATEGORIE) {
	echo printBox("Anzahl Kategorien &amp; Seiten", $quantity_cat." Kategorien, ".$quantity_page." Seiten");
}
else {
	echo printBox("Anzahl Seiten", $quantity_page." Seiten");
}

/* Statistik */
if (ACP_MODULE_STATISTIC) {
	$message = '';
	
	/* Besucher gesamt */
	$result = mysql_query('SELECT number FROM '.DB_TABLE_ROOT.'cms_register 
			WHERE name="Stats_CtrVisitors"', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_row($result);
	$message .= '<span class="boxnumber">'.number_format($line[0], 0, '.', '\'').'</span>';
	
	/* Seitenangichten gesamt */
	$result = mysql_query('SELECT SUM(views) FROM '.DB_TABLE_PLUGIN.'stats_views', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_row($result);
	//$message .= '/ <span class="boxnumber">'.number_format($line[0], 0, '.', '\'').'</span> Besucher<br />';
	$message .= ' Besucher<br />';
	
	/* Besucher heute */
	$result = mysql_query('SELECT visitors, views FROM '.DB_TABLE_PLUGIN.'stats_day
			WHERE day=CURRENT_DATE', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_assoc($result))
		$message .= '<span class="boxnumber">'.number_format($line['visitors'], 0, '.', '\'').'</span> <!--/ 
		<span class="boxnumber">'.number_format($line['views'], 0, '.', '\'').'</span>--> Heute<br />';
		
	/* Besucher gestern */
	$result = mysql_query('SELECT visitors, views FROM '.DB_TABLE_PLUGIN.'stats_day
			WHERE day=CURRENT_DATE-1', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_assoc($result))
		$message .= '<span class="boxnumber">'.number_format($line['visitors'], 0, '.', '\'').'</span> <!--/ 
		<span class="boxnumber">'.number_format($line['views'], 0, '.', '\'').'</span>--> Gestern<br />';
	
	/* Besucher Online */
	$result = mysql_query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'stats_ip
			WHERE timestamp>='.(TIME_STAMP - 300), DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_row($result);
	$message .= '<span class="boxnumber">'.number_format($line[0], 0, '.', '\'').'</span> Online';
	
	echo printBox('Statistik', $message);
}

/* CMS Versionsinfos */
echo printBox("CMS Informationen", "Swiss Webdesign<br />
		Kosmos CMS<br />
		Version 2.2 vom 22. Dezember 2014<br />
		&lt;cms.development@swiss-webdesign.ch&gt;",
		"<a href=\"#\" onclick=\"javascript:MyWindow=window.open('frame.php?page=cmssupport','cms_support','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=400'); return false;\" onmouseover=\"Tip('Support')\" onmouseout=\"UnTip()\"><img src=\"img/icons/support.png\" alt=\"\" /></a>
		<a href=\"http://www.swiss-webdesign.ch/\" target=\"_blank\" onmouseover=\"Tip('Website von Swiss Webdesign')\" onmouseout=\"UnTip()\"><img src=\"img/icons/information.png\" alt=\"\" /></a>",
		"© ".date('Y', TIME_STAMP)." Swiss Webdesign");

$file_time = filemtime("../_settings.php");
echo printBox("Stand der Konfiguration", printDate($file_time)." ".date(FORMAT_TIME, $file_time),
		null, NULL, null, $file_time > $_SESSION['admin_lastlogin']);


echo printBoxEnd();

?>