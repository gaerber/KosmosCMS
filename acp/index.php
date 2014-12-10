<?php

 /*
 =====================================================
 Name ........: Admin Controll Panel
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: index.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 11.07.2008 | Programm erstellt
 |2.0     | 29.01.2011 | Uebernahme
 |2.0.1   | 11.07.2011 | UTF-8 Kodierung
 -----------------------------------------------------
 Beschreibung :
 Verwaltung des gesamten ACPs.

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
// Functions & Menues                //
///////////////////////////////////////
/* Check ob alle wichtigen dateien vorhanden sind */
if (!(file_exists("../_settings.php")
		&& file_exists("../_classes.php")
		&& file_exists("../_functions.php")
		&& file_exists("_functions_acp.php")
		&& file_exists("_menu.php"))) {
	die("Loading Error");
}

/* Einstellungen holen */
include("../_settings.php");

/* Initialisieren der Klassen & Funktionen */
include("../_classes.php");
include("../_functions.php");
include("_functions_acp.php");

/* Menue */
include("_menu.php");

/* Datenbankverbindung herstellen */
define("DB_CMS", DatabaseConnect());

/* Informationen aus den Applikationen */
$ACP_ApplicationInfo = array();


///////////////////////////////////////
// Login                             //
///////////////////////////////////////
if (isset($_POST["acp_login_name"], $_POST['acp_login_password'])) {
	// User versucht sich einzuloggen
	$result = mysql_query("SELECT *	FROM ".DB_TABLE_ROOT."cms_admin
			WHERE login='".StdSqlSafety($_POST["acp_login_name"])."' && locked=0", DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($login_data = mysql_fetch_array($result)) {
		/* Loginname existiert */
		if (sha1($_POST['acp_login_password']) == $login_data['password']) {
			/* Login richtig -> Sessionen setzen */
			$_SESSION['admin_id'] = $login_data['admin_id'];
			$_SESSION['admin_login'] = $login_data['login'];
			$_SESSION['admin_name'] = $login_data['name'];
			$_SESSION['admin_password'] = sha1($_POST['acp_login_password']);
			$_SESSION['admin_lastlogin'] = $login_data['last_login'];
			$_SESSION['admin_ipadress'] = $login_data['ip_adress'];
			$_SESSION['admin_time_lastaction'] = TIME_STAMP;
			/* Rechte */
			$_SESSION['admin_access'] = $login_data['access'];
			/* Datum fuer last_login speichern */
			mysql_query("UPDATE ".DB_TABLE_ROOT."cms_admin
					SET last_login=".TIME_STAMP.", ip_adress='".$_SERVER["REMOTE_ADDR"]."'
					WHERE admin_id=".$_SESSION['admin_id'], DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			/* Relogin */
			if (isset($_GET['page']) && $_GET['page'] == "relogin") {
				header("Location: ".ROOT_TEMPLATE."relogin".TEMPLATE_TYPE);
				die();
			}
			/* Weiterleitung auf die gleiche Seite, damit der Browser die Logindaten loescht */
			$query = "?";
			if (isset($_GET['page']) && ($_GET['page'] != "logout"))
				$query .= "page=".$_GET['page']."&";
			if (isset($_GET['id']))		$query .= "id=".$_GET['id']."&";
			if (isset($_GET['mode']))	$query .= "mode=".$_GET['mode']."&";
			if (isset($_GET['folder']))	$query .= "folder=".$_GET['folder']."&";
			if (isset($_GET['album']))	$query .= "album=".$_GET['album']."&";
			$query = substr($query, 0, -1);
			header("Location: index.php".$query);
			die();
		}
		else {
			// Falsches Passwort
			die(LoginFormular(1));
		}
	}
	else {
		// Name gibt es nicht
		die(LoginFormular(2));
	}
}

if (isset($_GET['page']) && $_GET['page'] == "logout") {
	SessionDelete();
	die(LoginFormular(4));
}
if (isset($_GET['page']) && $_GET['page'] == "relogin") {
	SessionDelete();
	die(LoginFormular(5));
}

///////////////////////////////////////
// Login-System                      //
///////////////////////////////////////
if (!LoginSystem(DB_CMS)) {
	die(LoginFormular(0));
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
	die(LoginFormular(3));
}
$_SESSION['admin_time_lastaction'] = TIME_STAMP;


///////////////////////////////////////
// Page auswaehlen                   //
///////////////////////////////////////
if(isset($_GET['page'])) {
	$datei = mb_strtolower($_GET['page']);		// Alles in Kleinbuchstaben
	$datei = str_replace("-", "/", $datei); 	// Datei in einem Unterordner
}
else {
	$datei = "home";
}

// Existiert diese Datei ueberhaubt?
if(!file_exists("content/".$datei.".php")) {
	// Datei existiert nicht!
	$datei = "home";
}

///////////////////////////////////////
// Der Buffer muss den Inhalt holen  //
///////////////////////////////////////
ob_start();										// Buffer starten
include("content/".$datei.".php");				// datei wird in Buffer geladen
$content = ob_get_contents();					// Buffer wird in $content geschrieben
ob_end_clean();									// Buffer wird geloescht

///////////////////////////////////////
// Menue generieren                  //
///////////////////////////////////////
$header_menu = HeaderMenu($ACP_ApplicationInfo['categorie']);
$second_menu = SecondMenu($ACP_ApplicationInfo['categorie']);

if (isset($ACP_ApplicationInfo['menu_search'], $ACP_ApplicationInfo['menu_replace'])) {
	$second_menu = str_replace($ACP_ApplicationInfo['menu_search'],
			$ACP_ApplicationInfo['menu_replace'], $second_menu);
}

///////////////////////////////////////
// Die Ausgabe der HP im Template    //
///////////////////////////////////////
$design = new tpl("tpl");
$replace = array("menu_header" => $header_menu, "content" => $content, "menu_second" => $second_menu,
		"admin_name" => $_SESSION['admin_name'],
		"servertime" => printDate(TIME_STAMP)." ".date(FORMAT_TIME, TIME_STAMP));
$design->assign($replace);

if (isset($acp_info_header)) {
	$design->assign('head', $acp_info_header);
}
else {
	$design->assign('head', '');
}

// Ausgabe GZip komprimiert
//$design->compress_gzip();
$design->out();

///////////////////////////////////////
// Datenbankverbindung beenden       //
///////////////////////////////////////
mysql_close(DB_CMS);

?>