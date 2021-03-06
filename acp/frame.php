<?php

 /*
 =====================================================
 Name ........: Frame fuer Erweiterungen
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: frame.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 07.07.2011 | Programm erstellt.
 |1.0.1   | 11.07.2011 | UTF-8 Kodierung
 |1.0.2   | 14.03.2015 | Debugausgabe Projektabhaengig
 |1.1     | 13.10.2018 | SQL API Umstellung
 -----------------------------------------------------
 Beschreibung :
 Sicherheitsstufen von Frames. Frames werden b.B. beim
 WYMeditor fuer Bilderupload und Links verwendet.

 (c) by Kevin Gerber
 =====================================================
 */

/* Programmkonstante */
$f = @file('../.version', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) OR die('Missing Version');
define('SWISS_WEBDESIGN', $f[0]);

/* Check ob alle wichtigen dateien vorhanden sind */
if (!(file_exists('../_settings.php')
		&& file_exists('../_classes.php')
		&& file_exists('../_functions.php')
		&& file_exists('_functions_acp.php')
		)) {
	die('Loading Error');
}

/* Einstellungen holen */
include('../_settings.php');

/* Ausgabe aller Fehler fuer Debug */
if (EN_DEBUG) {
	error_reporting(E_ALL);
}
else {
	error_reporting(0);
}

/* Sessionen Starten */
session_start();

/* UTF-8 Ausgabe */
mb_internal_encoding('UTF-8');

/* Initialisieren der Klassen & Funktionen */
include('../_classes.php');
include('../_functions.php');
include('_functions_acp.php');

/* Informationen aus den Applikationen */
$ACP_ApplicationInfo = array();


///////////////////////////////////////
// Login-System                      //
///////////////////////////////////////
if (!LoginSystem(Database::instance())) {
	echo ActionReport(REPORT_ERROR, 'Keine berechtigung', 'Sie müssen sich anmelden um diese Seite zu sehen!');
	die();
}
else {
	define('ACP_CHECK_SUM', 707);
}


///////////////////////////////////////
// Inaktive Sessionen loeschen       //
///////////////////////////////////////
if ($_SESSION['admin_time_lastaction'] < TIME_STAMP - MAX_ACP_LOGIN_TIME) {
	// TIME OUT -> Session loeschen
	SessionDelete();
	echo ActionReport(REPORT_ERROR, 'Keine berechtigung',
			'Aufgrund längerer inaktivität wurden Sie automatisch abgemeldet.');
	die();
}
$_SESSION['admin_time_lastaction'] = TIME_STAMP;


///////////////////////////////////////
// Seite auswaehlen                  //
///////////////////////////////////////
if(isset($_GET['page'])) {
	$datei = mb_strtolower($_GET['page']);		// Alles in Kleinbuchstaben
	$datei = str_replace('-', '/', $datei); 	// Datei in einem Unterordner
}
else {
	$datei = 'none';
}

// Existiert diese Datei ueberhaubt?
if(!file_exists('content/frames/'.$datei.'.php')) {
	// Datei existiert nicht!
	$datei = 'none';
}


///////////////////////////////////////
// Der Buffer muss den Inhalt holen  //
///////////////////////////////////////
ob_start();										// Buffer starten
include('content/frames/'.$datei.'.php');		// datei wird in Buffer geladen
$content = ob_get_contents();					// Buffer wird in $content geschrieben
ob_end_clean();									// Buffer loeschen


///////////////////////////////////////
// Die Ausgabe der HP im Template    //
///////////////////////////////////////
$tpl = new tpl('frame');
$tpl->assign('title', $ACP_ApplicationInfo['title']);
$tpl->assign('content', $content);

$framehead = '';
if (isset($ACP_ApplicationInfo['javascript']))
	$framehead .= '<script type="text/javascript">'.$ACP_ApplicationInfo['javascript'].'</script>';
if (isset($ACP_ApplicationInfo['stylesheet']))
	$framehead .= '<link rel="stylesheet" type="text/css" href="'.$ACP_ApplicationInfo['stylesheet'].'" />';
$tpl->assign('frame_head', $framehead);

$body_args = '';
if (isset($ACP_ApplicationInfo['body_onload']))
	$body_args .= ' onload="'.$ACP_ApplicationInfo['body_onload'].'"';
if (isset($ACP_ApplicationInfo['body_arg']))
	$body_args .= ' '.$ACP_ApplicationInfo['body_arg'];
$tpl->assign('body_args', $body_args);

if (isset($ACP_ApplicationInfo['body_css_class']))
	$tpl->assign('body_css_class', $ACP_ApplicationInfo['body_css_class']);
else
	$tpl->assign('body_css_class', 'wym_dialog_insert');

header('Content-Type: text/html; charset=utf-8');
if (EN_DEBUG) {
	$tpl->out();
}
else {
	$tpl->compress_gzip();
}


///////////////////////////////////////
// Datenbankverbindung beenden       //
///////////////////////////////////////
Database::instance()->close();

?>
