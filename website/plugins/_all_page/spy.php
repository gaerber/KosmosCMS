<?php

 /*
 =====================================================
 Name ........: Plugin: Besucher-Spion
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: spy.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |4.0     | 12.10.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Datenlogger der alle Besucher und deren Aktionen
 aufzeichnet. Dieses Modul darf nur fuer Analyse
 Zwecken eingesetzt werden.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

function checkBot_TEMP($string) {
	$bots = array(
		'bot', 'spider', 'spyder', 'crawl', 'robo', 'agentname',
		'altaVista intranet', 'appie', 'arachnoidea', 'asterias',
		'beholder', 'bumblebee', 'cherrypicker', 'cosmos', 'openxxx',
		'fido', 'crescent', 'emailsiphon', 'emailwolf', 'extractorpro',
		'gazz', 'gigabaz', 'gulliver', 'hcat', 'hloader', 'incywincy',
		'infoseek', 'inktomi', 'link', 'internetami', 'internetseer',
		'scan', 'fireball', 'larbin', 'libweb', 'trivial', 'mata hari',
		'medicalmatrix', 'mercator', 'miixpc', 'moget', 'muscatferret',
		'slurp', 'quosa', 'scooter', 'sly', 'webbandit', 'spy', 'wisewire',
		'ultraseek', 'piranha', 't-h-u-n-d-e-r-s-t-o-n-e', 'indy library',
		'ezresult', 'informant', 'swisssearch', 'sqworm',
		'ask jeeves/teoma', 'libwww', 'archiver', 'exabot', 'fast', 'firefly',
		'googlebot', 'msnbot', 'yahoo-mmcrawler', 'gigabot',
		'validator'
	);
	$string = strtolower($string);
	$summe = count($bots);

	for ($i=0; $i < $summe; $i++) {
		if(strstr($string, $bots[$i])) {
			return true;
		}
	}
	return false;
}

$user_data = array();

$user_data['ip'] = $_SERVER['REMOTE_ADDR'];
$user_data['host'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);
$user_data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

$user_data['object'] = checkBot_TEMP($_SERVER['HTTP_USER_AGENT']) ? 'BOT Suche' : 'BESUCHER';

$user_data['page'] = $_SERVER['REQUEST_URI'];

$user_data['get'] = isset($_GET) && sizeof($_GET) > 0 ? print_r($_GET, true) : '';
$user_data['post'] = isset($_POST) && sizeof($_POST) > 0 ? print_r($_POST, true) : '';
$user_data['session'] = isset($_SESSION) && sizeof($_SESSION) > 0 ? print_r($_SESSION, true) : '';

/* Speicher in Datenbank */
$col = '';
$data = '';
foreach ($user_data as $key => $value) {
	$col .= $key.', ';
	$data .= '\''.$value.'\', ';
}

$col .= 'day, time';
$data .= 'now(), now()';

Database::instance()->query('INSERT INTO '.DB_TABLE_PLUGIN.'spy('.$col.')VALUES('.$data.')') OR FatalError(FATAL_ERROR_MYSQL);

?>
