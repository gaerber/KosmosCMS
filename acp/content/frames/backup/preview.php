<?php

 /*
 =====================================================
 Name ........: Frame: Backup Vorschau
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: preview.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 13.07.2011 | Programm erstellt.
 |1.1     | 11.09.2016 | Formaterung wie bei Vorschau.
 -----------------------------------------------------
 Beschreibung :
 Vorschau-Popup der gespeicherten Inhalten (Backups).

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = 'Backup Vorschau';
$ACP_ApplicationInfo['stylesheet'] = '../templates/stylesheets/content.css';
$ACP_ApplicationInfo['body_css_class'] = 'wym_dialog wym_dialog_preview';
$ACP_ApplicationInfo['body_arg'] = 'id="content"';
///////////////////////////////////////////////////////

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$result = mysql_query("SELECT html FROM ".DB_TABLE_ROOT."cms_content WHERE id=".StdSqlSafety($_GET['id']), DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		echo $line['html'];
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Backup nicht gefunden", "Das Backup wurde in der Datenbank nicht gefunden!");
	}
}
else {
	echo ActionReport(REPORT_EINGABE, "Eingabefehler", "Es wurde keine Backupidentifikationsnummer übergeben!");
}

?>