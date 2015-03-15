<?php

 /*
 =====================================================
 Name ........: Plugin: Benutzer Liste
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: members.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 11.09.2011 | Programm erstellt
 |1.0.1   | 12.09.2011 | Dynamische Sortierung
 |1.1     | 24.11.2012 | Dynamische URL des Kontakt-Moduls
 -----------------------------------------------------
 Beschreibung :
 Plugin: Erstellt eine Liste aller Benutzer.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

/* Default Group */
$plugin_access_groups = 0x01;

/* Bestimmte Gruppe anzeigen */
if (isset($_GET['group']) && $_GET['group'] != "") {
	$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_groups
			WHERE id_str='".StdSqlSafety($_GET['group'])."'", DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		$plugin_access_groups = 1 << $line['id'];
	}
}

if (isset($moduleParameter['show_groups_only'])) {
	$plugin_access_groups = (int) $moduleParameter['show_groups_only'];
}

/* Sortierung */
if (isset($_GET['sort'])) {
	/* Sortierung */
	$sort = explode("-", $_GET['sort']);
	if (sizeof($sort) == 2 
			&& ($sort[0] == "user_name" || $sort[0] == "user_lastlogin"
				|| $sort[0] == "user_regist" || $sort[0] == "user_points") 
			&& ($sort[1] == "asc" || $sort[1] == "desc")) {
		$sql_sort = $sort[0]." ".strtoupper($sort[1]);
	}
}
if (!isset($sql_sort)) {
	/* Default Sort */
	$sql_sort = "user_regist ASC";
	if (isset($moduleParameter['sql_sort_field'], $moduleParameter['sql_sort_order']))
		$sql_sort = $moduleParameter['sql_sort_field'].' '.$moduleParameter['sql_sort_order'];
}

/* URL zur Seite mit dem Kontakt Modul ermitteln */
$result = mysql_query('SELECT menu.id 
		FROM '.DB_TABLE_ROOT.'cms_plugin AS plugin 
		INNER JOIN '.DB_TABLE_ROOT.'cms_menu AS menu ON plugin.id=menu.plugin
		WHERE plugin.label="Kontaktformular"', DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
if ($line = mysql_fetch_row($result)) {
	$o_modlue_path = new activePage(DB_CMS);
	$url = $o_modlue_path->getUrlById($line[0]);
}

$result = mysql_query("SELECT * FROM ".DB_TABLE_ROOT."cms_access_user
		WHERE (user_access & ".$plugin_access_groups.") && (user_locked=0) ORDER BY ".$sql_sort, DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);

while ($row = mysql_fetch_array($result)) {
	$tpl = new tpl("plugins/members/user");
	/* Email Form */
	if ($row['user_email_show'] && isset($url)) {
		$tpl_email = new tpl('plugins/members/user_email_show');
		$tpl_email->assign('url_module_contact', $url);
	}
	else {
		$tpl_email = new tpl('plugins/members/user_email_noshow');
	}
	$email_form = $tpl_email->get();
	$tpl->assign("user_email_form", $email_form);
	$tpl->assign($row);
	$tpl->out();
}

		
/* Neuster Benutzer fuer Stand der Seite */
$result = mysql_query('SELECT user_regist FROM '.DB_TABLE_ROOT.'cms_access_user
		ORDER BY user_regist DESC LIMIT 1', DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
if ($line = mysql_fetch_assoc($result)) {
	$PluginContent['date'] = printDate($line['user_regist']);
}

?>