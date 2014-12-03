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
 -----------------------------------------------------
 Beschreibung :
 Sicherheitsstufen von Frames. Frames werden b.B. beim
 WYMeditor fuer Bilderupload und Links verwendet.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////
// Session Starten                   //
///////////////////////////////////////
error_reporting(E_ALL);
session_start();
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=utf-8");


///////////////////////////////////////
// Functions                         //
///////////////////////////////////////
/* Check ob alle wichtigen dateien vorhanden sind */
if (!(file_exists("../_settings.php")
		&& file_exists("../_classes.php")
		&& file_exists("../_functions.php")
		&& file_exists("_functions_acp.php")
		)) {
	die("Loading Error");
}

/* Einstellungen holen */
include("../_settings.php");

/* Initialisieren der Klassen & Funktionen */
include("../_classes.php");
include("../_functions.php");
include("_functions_acp.php");

/* Datenbankverbindung herstellen */
define("DB_CMS", DatabaseConnect());

/* Informationen aus den Applikationen */
$ACP_ApplicationInfo = array();


///////////////////////////////////////
// Login-System                      //
///////////////////////////////////////
if (!LoginSystem(DB_CMS)) {
	echo ActionReport(REPORT_ERROR, "Keine berechtigung", "Sie müssen sich anmelden um diese Seite zu sehen!");
	die();
}
else {
	define("ACP_CHECK_SUM", 707);
}


///////////////////////////////////////
// Inaktive Sessionen loeschen       //
///////////////////////////////////////
if ($_SESSION['admin_time_lastaction'] < TIME_STAMP - MAX_ACP_LOGIN_TIME) {
	// TIME OUT -> Session loeschen
	SessionDelete();
	echo ActionReport(REPORT_ERROR, "Keine berechtigung",
			"Aufgrund längerer inaktivität wurden Sie automatisch abgemeldet.");
	die();
}
$_SESSION['admin_time_lastaction'] = TIME_STAMP;


///////////////////////////////////////
// Seite auswaehlen                  //
///////////////////////////////////////
if(isset($_GET['page'])) {
	$datei = mb_strtolower($_GET['page']);		// Alles in Kleinbuchstaben
	$datei = str_replace("-", "/", $datei); 	// Datei in einem Unterordner
}
else {
	$datei = "none";
}

// Existiert diese Datei ueberhaubt?
if(!file_exists("content/frames/".$datei.".php")) {
	// Datei existiert nicht!
	$datei = "none";
}


///////////////////////////////////////
// Der Buffer muss den Inhalt holen  //
///////////////////////////////////////
ob_start();										// Buffer starten
include("content/frames/".$datei.".php");		// datei wird in Buffer geladen
$content = ob_get_contents();					// Buffer wird in $content geschrieben
ob_end_clean();									// Buffer loeschen


///////////////////////////////////////
// Die Ausgabe der HP im Template    //
///////////////////////////////////////
$tpl = new tpl("frame");
$tpl->assign("title", $ACP_ApplicationInfo['title']);
$tpl->assign("content", $content);

if (isset($ACP_ApplicationInfo['javascript']))
	$tpl->assign("javascript", "  <script type=\"text/javascript\">\r\n    "
			.$ACP_ApplicationInfo['javascript']."\r\n  </script>");
else
	$tpl->assign("javascript", "");

if (isset($ACP_ApplicationInfo['body_onload']))
	$tpl->assign("body_onload", " onload=\"".$ACP_ApplicationInfo['body_onload']."\"");
else
	$tpl->assign("body_onload", "");

if (isset($ACP_ApplicationInfo['body_css_class']))
	$tpl->assign("body_css_class", $ACP_ApplicationInfo['body_css_class']);
else
	$tpl->assign("body_css_class", "wym_dialog_insert");

//$tpl->compress_gzip();
$tpl->out();

///////////////////////////////////////
// Datenbankverbindung beenden       //
///////////////////////////////////////
mysql_close(DB_CMS);

?>