<?php

 /*
 =====================================================
 Name ........: Home Dir
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
 |2.0     | 29.01.2011 | Uebernahme
 |2.0.1   | 11.07.2011 | UTF-8 Kodierung
 |2.0.2   | 15.07.2011 | URL Check Kategorie/Seite
 |2.0.3   | 04.08.2011 | Menuegenerierung
 |2.0.4   | 13.10.2011 | Modul Parameter
 |2.0.5   | 12.02.2012 | Menugenerierung second
 |2.0.5.1 | 13.02.2012 | Startseitenbug fix
 |2.0.5.2 | 29.08.2012 | Offlinenachricht mit HTTP E.
 |2.0.5.3 | 16.09.2012 | Bugfix {module_path}
 |2.0.5.4 | 14.03.2015 | Debugausgabe Projektabhaengig
 |2.1     | 13.10.2018 | SQL API Umstellung
 -----------------------------------------------------
 Beschreibung :
 CMS Software fuer die Seiteninhalte.

 (c) by Kevin Gerber
 =====================================================
 */

/* Programmkonstante */
$f = @file('.version', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) OR die('Missing Version');
define('SWISS_WEBDESIGN', $f[0]);

/* Check ob alle wichtigen Dateien vorhanden sind */
if (!(file_exists('_settings.php')
		&& file_exists('_classes.php')
		&& file_exists('_functions.php'))) {
	header('HTTP/1.1 503 Service Unavailable');
	die('Loading Error');
}

/* Einstellungen holen */
include('_settings.php');

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
include('_classes.php');
include('_functions.php');

/* Messung der Generierungszeit starten */
$Anfangszeit = getMicrotime();

/* Globale Template Variablen */
$HomepageContent = array();
$PluginContent = array();
$MenuOutput = array();


/*** Homepage offline schalten **********************/
$result = Database::instance()->query('SELECT * FROM '.DB_TABLE_ROOT.'cms_setting
		ORDER BY id DESC LIMIT 1')
		OR FatalError(FATAL_ERROR_MYSQL);
if ((!($line = $result->fetch_assoc()))
		|| ($line['online'] == 0)) {
	/* Homepage ist offline */
	header('HTTP/1.1 503 Service Unavailable');
	$tpl = new tpl('offline');
	/* Root Verzeichnis der Website */
	$tpl->assign('root_website', ROOT_WEBSITE);
	/* Versionsnummer */
	$tpl->assign('cms_version', SWISS_WEBDESIGN);
	$tpl->assign($line);
	/* Ausgabe */
	if (EN_DEBUG) {
		$tpl->out();
	}
	else {
		$tpl->compress_gzip();
	}
	/* Datenbankverbindung schliessen */
	Database::instance()->close();
	/* Der Rest nicht mehr ausfuehren */
	die();
}


/*** Security System ********************************/
if (ACP_ACCESS_SYSTEM_EN) {
	include(ROOT_PLUGIN.'access/main/autologin.php');
	include(ROOT_PLUGIN.'access/main/login.php');
	include(ROOT_PLUGIN.'access/main/logout.php');
	include(ROOT_PLUGIN.'access/main/check.php');
}
else {
	$_SESSION['user_id'] = 0;
}


/*** Seite selektieren ******************************/
$o_activePage = new activePage(Database::instance());

if ($o_activePage->calcActivePage(isset($_GET[CONTENT_POINTER])
		? StdSqlSafety($_GET[CONTENT_POINTER]) : "") == false) {
	/* Pfad stimmt nicht oder Seite wurde nicht gefunden */
	/* Ausgabe Error Seite 404 */
	if ($o_activePage->changeActivePage($DefaultErrorPages['404']) == false) {
		FatalError(FATAL_ERROR_CONTENT);
	}
}

/* Fehlerseite bei falscher Anmeldung (Aus Modul Access->Login) */
if (isset($show_error_page)) {
	/* Ausgabe Error Seite 550 */
	if ($o_activePage->changeActivePage($show_error_page) == false) {
		FatalError(FATAL_ERROR_CONTENT);
	}
}

/* URL pruefen auf Kategorien oder Seiten */
if (isset($_GET['is_categorie'])
		&& ($o_activePage->getUrlIsCategorie() != $_GET['is_categorie'])) {
	/* Unerlaubte URL manipulation */
	/* Ausgabe Error Seite 404 */
	if ($o_activePage->changeActivePage($DefaultErrorPages['404']) == false) {
		FatalError(FATAL_ERROR_CONTENT);
	}
}

/* Pruefen ob der Benutzer die Berechtigung hat */
if (!$o_activePage->getUserAccess()) {
	/* Besucher hat keinen Zugriff auf diese Seite */
	/* Ausgabe Error Seite 403 */
	if ($o_activePage->changeActivePage($DefaultErrorPages['403']) == false) {
		FatalError(FATAL_ERROR_CONTENT);
	}
}


/*** Seite aus Datenbank holen **********************/
$result = Database::instance()->query('SELECT * FROM '.DB_TABLE_ROOT.'cms_menu
		WHERE id='.$o_activePage->getActivePage().' LIMIT 1')
				OR FatalError(FATAL_ERROR_MYSQL);
/* Existiert diese Seite? */
if (!($HomepageContent = $result->fetch_assoc())) {
	/* Diese Seite muss existieren */
	FatalError(FATAL_ERROR_CONTENT);
}


/*** Inhalt der Seite auslesen **********************/
$result = Database::instance()->query('SELECT html FROM '.DB_TABLE_ROOT.'cms_content
		WHERE page_id='.$HomepageContent['id'].'
		ORDER BY timestamp DESC LIMIT 1')
		OR FatalError(FATAL_ERROR_MYSQL);
if ($line = $result->fetch_assoc()) {
	$HomepageContent['html'] = $line['html'];
}
else {
	FatalError(FATAL_ERROR_CONTENT);
}

/*** PlugIn System AllPage **************************/
foreach(glob(ROOT_PLUGIN_ALLPAGE.'*.php') as $filename) {
    /* Es werden alle PHP Dateien alphabetisch dem Dateinamen sortiert und ausgefuehrt */
	include($filename);
}


/*** Seiten Inhalt **********************************/
/* Autor Informationen */
$HomepageContent['writer_name'] = '';
$HomepageContent['writer_email'] = '';
getWriterInfo($HomepageContent['writer'],
		$HomepageContent['writer_name'], $HomepageContent['writer_email']);

/* Datum */
$HomepageContent['date'] = printDate($HomepageContent['timestamp']);

/* Module */
if ($HomepageContent['plugin'] > 0) {
	/* Plugin */
	$result = Database::instance()->query('SELECT path FROM '.DB_TABLE_ROOT.'cms_plugin
			WHERE id='.$HomepageContent['plugin'].' && locked = 0')
					OR FatalError(FATAL_ERROR_MYSQL);
	if ($result->num_rows == 0) {
		die('Module could not found!');
	}
	$line = $result->fetch_assoc();
	if (!file_exists(ROOT_PLUGIN.$line['path'])) {
		die('Module '.$line['path'].' could not load!');
	}

	/* Parameter */
	$moduleParameter = array();
	$HomepageContent['html'] = preg_replace_callback('/\{MODUL([^\}]+)\}/i',
			'moduleCallback', $HomepageContent['html']);

	/* Plugin ausfuehren */
	ob_start();										// Buffer starten
	include(ROOT_PLUGIN.$line['path']);				// Datei wird in Buffer geladen
	$plugin_content = ob_get_contents();			// Buffer wird in $content geschrieben
	ob_end_clean();									// Buffer wird geloescht

	/* PlugIn Ausgabe in Content schreiben */
	$HomepageContent['html'] = str_replace('<p>{MODUL}</p>', $plugin_content, $HomepageContent['html']);
	$HomepageContent['html'] = str_replace('{MODUL}', $plugin_content, $HomepageContent['html']);
}

/* Plugins (Small) */
$HomepageContent['html'] = preg_replace_callback('/{Abstract: (PHP|FUNC) (.*?)\((.*?)\)}/i',
		'pluginCallback', $HomepageContent['html']);


/*** Menue generieren *******************************/
$o_menuClass = new buildMenuTree(Database::instance(), $o_activePage->getSubElements());
$o_menuClass->setSqlCondition(" && menu_view=1 && locked=0 && ".CheckSQLAccess());

foreach ($array_MenuConfigurations AS $name => $settings) {
	if (sizeof($settings) == 5) {
		if ($settings[0] < 0) {
			$settings[0] *= -1;
			$temp = $o_activePage->getSubElements();
			if ($settings[0] <= sizeof($temp))
				$settings[0] = $temp[$settings[0]-1];
		}
		$MenuOutput[$name] = $o_menuClass->getMenuTree($settings[0],$settings[1],$settings[2],$settings[3],$settings[4]);
	}
	else if (sizeof($settings) == 1) {
		$MenuOutput[$name] = $o_menuClass->getMenuPath($settings[0]);
	}
}


/*** Ausgabe Template vorbereiten *******************/
$print = new tpl('website');

/* Plugin Inhalt */
$print->assign($PluginContent);
/* Seiteninhalt */
$print->assign($HomepageContent);
/* Menue */
$print->assign($MenuOutput);

/* Benutzerinformationen */
if (isset($_SESSION)) {
	if (isset($_SESSION['user_lastlogin'])) {
		$print->assign('user_lastlogin', printDate($_SESSION['user_lastlogin'])
				.' '.date('H:i', $_SESSION['user_lastlogin']));
	}
	if (isset($_SESSION['user_regist'])) {
		$print->assign('user_regist', printDate($_SESSION['user_regist']));
	}
	foreach($_SESSION as $key => $value) {
		if (!substr_compare($key, 'user_', 0, 5))
			$print->assign($key, $value);
	}
}

/* Pfad-Angaben */
/* Root Verzeichnis der Website */
$print->assign('root_website', ROOT_WEBSITE);
if ($o_activePage->isUrlCategorie())
	$print->assign('module_path', ROOT_WEBSITE.implode('/', $o_activePage->getActuelPath())
			.URL_ENDSTR_CATEGORIE);
else
	$print->assign('module_path', ROOT_WEBSITE.implode('/', $o_activePage->getActuelPath()).URL_ENDSTR_PAGE);

/* Versionsnummer */
$print->assign('cms_version', SWISS_WEBDESIGN);

/* Einstellungen */
$result = Database::instance()->query('SELECT company, header, description, admin_email
		FROM '.DB_TABLE_ROOT.'cms_setting ORDER BY id DESC LIMIT 1')
		OR FatalError(FATAL_ERROR_MYSQL);
if ($result->num_rows) {
	$print->assign($result->fetch_assoc());
}

/* Generierungszeit */
$print->assign('runtime', number_format(getMicrotime()-$Anfangszeit, 4, ',', '.'));

/*** Ausgabe ****************************************/
if ($o_activePage->getActivePage() == $DefaultErrorPages['404']) {
	header('HTTP/1.1 404 NOT FOUND');
}
header('Cache-Control: post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Last-Modified: '.date(DATE_RFC822, $HomepageContent['timestamp']));
header('Content-Type: text/html; charset=utf-8');
if (EN_DEBUG) {
	$print->out();
}
else {
	$print->compress_gzip();
}


/*** Datenbankverbindung trennen ********************/
Database::instance()->close();

?>
