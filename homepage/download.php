<?php

 /*
 =====================================================
 Name ........: Downloader
 Projekt .....: CMS 2.1 Kosmos
 Datiename ...: download.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 19.07.2013 | Programm erstellt.
 |1.1     | 08.08.2013 | Modul Erweiterung (Fotoalbum)
 |2.0     | 24.11.2014 | Config in JSON
 -----------------------------------------------------
 Beschreibung :
 Downloader fuer interne Dateien.
 Parameter:
 -path   Pfad zur Datei auf FTP Server.
 -inline Falls gesetzt wird Datei automatisch 
         geoeffnet.
 -thumb  Falls Thumb aus dem Modul Photos geladen
         werden soll.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////
// Session Starten                   //
///////////////////////////////////////
error_reporting(E_ALL);
session_start();

/* Programmkonstante */
define('SWISS_WEBDESIGN', '2.2');

$mime = array(
		'zip'  => 'application/zip',
		'rar'  => 'application/x-rar-compressed',
		'tar'  => 'application/x-tar',
		'pdf'  => 'application/pdf',
		'swf'  => 'application/x-shockwave-flash',
		'doc'  => 'application/msword',
		'docx' => 'application/msword',
		'dot'  => 'application/msword',
		'xls'  => 'application/excel',
		'xlsx' => 'application/excel',
		'ppt'  => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.ms-powerpoint',
		'pps'  => 'application/vnd.ms-powerpoint',
		'ppsx' => 'application/vnd.ms-powerpoint',
		
		'exe'  => 'application/octet-stream',
		'txt'  => 'text/plain',
		'htm'  => 'text/html',
		'html' => 'text/html',
		'sql'  => 'text/plain',
		
		'mp2'  => 'audio/mpeg',
		'mp3'  => 'audio/mpeg',
		'wav'  => 'audio/x-wav',
		
		'jpg'  => 'image/jpeg',
		'gif'  => 'image/gif',
		'png'  => 'image/png',
		'bmp'  => 'image/bmp',
		'jpeg' => 'image/jpeg',
		
		'mpeg' => 'video/mpeg',
		'mpg'  => 'video/mpeg',
		'mov'  => 'video/quicktime',
		'avi'  => 'video/x-msvideo'
		);


///////////////////////////////////////
// Parameter pruefen                 //
///////////////////////////////////////
if (!isset($_GET['path']) || $_GET['path'] == '') {
	//header("HTTP/1.1 404 NOT FOUND");
	die("No Path found.");
}


///////////////////////////////////////
// Functions                         //
///////////////////////////////////////
/* Check ob alle wichtigen dateien vorhanden sind */
if (!(file_exists('_settings.php')
		&& file_exists('_classes.php')
		&& file_exists('_functions.php')
		&& file_exists('acp/_functions_acp.php')
		)) {
	//header('HTTP/1.1 503 Service Unavailable');
	die('Loading Error.');
}

/* Einstellungen holen */
include('_settings.php');

/* Initialisieren der Klassen & Funktionen */
include('_classes.php');
include('_functions.php');
include('acp/_functions_acp.php');

/* Datenbankverbindung herstellen */
define('DB_CMS', DatabaseConnect());


///////////////////////////////////////
// Login-System                      //
///////////////////////////////////////
if (ACP_ACCESS_SYSTEM_EN) {
	include(ROOT_PLUGIN.'access/main/autologin.php');
	include(ROOT_PLUGIN.'access/main/check.php');
}
else {
	$_SESSION['user_id'] = 0;
}


///////////////////////////////////////
// Sicherheitschecks                 //
///////////////////////////////////////
$path = str_replace('../', '', $_GET['path']);
$path = str_replace('./', '', $path);

$file_info = pathinfo($path);

/* Erleubter Dateitypen */
if (!isset($file_info['extension']) || !in_array($file_info['extension'], $FileSystem_AllowedDataTypes)) {
	/* Solche Dateien duerfen nicht runtergeladen werden */
	//header('HTTP/1.1 403 Forbidden');
	die('Filetype not allowed');
}

/* Pfad */
$path_folder = $file_info['dirname'];

/* Dateinamen */
$path_file = $file_info['basename'];


///////////////////////////////////////
// Verzeichnis oeffnen               //
///////////////////////////////////////
$ftp = new ftp();

/* Verzeichnis und Datei muessen existieren */
if (!$ftp->folderExists($path_folder) && $ftp->fileExists($path_folder.'/'.$path_file)) {
	/* Verzeichnis oder Datei existiert nicht */
	//header("HTTP/1.0 404 Not Found");
	die('File not exists');
}

/* Die Konfigurationen werde rekursive vererbt */
$access_merge = 0;
$locked_merge = 0;

for ($ap = explode('/', $path_folder); sizeof($ap) > 0; array_pop($ap)) {
	$p = implode('/', $ap);
	/* Im Root Verzeichniss duerfen keine Einschraenkungen sein */
	if (($p != '') && ($c = $ftp->readFolderConfig($p))) {
		if (!(isset($c['access'], $c['locked']) && is_numeric($c['access']) && is_numeric($c['locked']))) {
			/* Ungueltige Config Datei */
			header("HTTP/1.0 404 Not Found");
			die('Invalide config');
		}
		if ($c['access'] != 0) {
			$access_merge = ($access_merge != 0) ? $access_merge&$c['access'] : $c['access'];
		}
		$locked_merge |= $c['locked'];
		
		/* Letzte Konfiguration uebernehmen */
		$config = $c;
	}
}

/* Es muss mindestens einmal eine Konfigurationsdatei vorhanden sein */
if (!isset($config)) {
	/* Configdatei existiert nicht */
	//header("HTTP/1.0 404 Not Found");
	die('Config not exists');
}

/* Erstellen der aktuellen Konfiguration */
$config['access'] = $access_merge;
$config['locked'] = $locked_merge;


///////////////////////////////////////
// Berechtigung pruefen              //
///////////////////////////////////////

if (!($config['access'] > 0 && CheckAccess($config['access']) && $config['locked'] == 0
		|| (isset($config['module']) && LoginSystem(DB_CMS) && $_SESSION['admin_time_lastaction'] >= TIME_STAMP - MAX_ACP_LOGIN_TIME && (
				($config['module']=='photos' && ACP_AdminAccess(ACP_ACCESS_M_PHOTOS))
				||($config['module']=='mysqlbackups' && ACP_AdminAccess(ACP_ACCESS_ADMIN))
			))
		)) {
	/* Kein Zugriff */
	//header('HTTP/1.1 403 Forbidden');
	die('Access denied');
}


///////////////////////////////////////
// Dateioeffnen                      //
///////////////////////////////////////

$content = $ftp->FileContents($path_folder.'/'.$path_file);
/* HTTP //headers senden */
header("HTTP/1.1 200 OK");
header('Content-type: '.$mime[$file_info['extension']]);
header("Content-Length: ".$ftp->fileSize($path_folder.'/'.$path_file));
if (!(isset($_GET['inline'])))
	header('Content-Disposition: attachment; filename="'.$path_file.'"');
/* Ausgabe des Dateiinhaltes */
echo $content;



///////////////////////////////////////
// Datenbankverbindung beenden       //
///////////////////////////////////////
$ftp->close();
mysql_close(DB_CMS);

?>