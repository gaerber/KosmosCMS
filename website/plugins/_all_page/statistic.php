<?php

 /*
 =====================================================
 Name ........: Plugin: Statistik
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: statistic.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |4.0     | 02.10.2011 | Programm erstellt
 |4.1     | 17.11.2012 | Datenbankkonzept, Seitenrang
 -----------------------------------------------------
 Beschreibung :
 Plugin fuer die Besucherstatistik.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

/*** Einstellungen ***********************************/
/* Reloadsperre in Sekunden (1800 = 30min) */
define("PLUGIN_STATS_RELOAD", 14400);

/*** Funktionen **************************************/
/**
 * Ueberprueft ob es sich um einen Suchbot handelt
 * @param $string HTTP User Agent
 * @return TRUE wenn es ein BOT ist
 */
function checkBot($string) {
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

/*** Software ****************************************/

if (1 || ACP_MODULE_STATISTIC) {

	/* Abgelaufene IPs loeschen */
	Database::instance()->query('DELETE FROM '.DB_TABLE_PLUGIN.'stats_ip
			WHERE timestamp<='.(TIME_STAMP-PLUGIN_STATS_RELOAD));

	/* Sessionen */
	if (isset($_SESSION['stats_lastpage']) && $_SESSION['stats_lastpage'] <= (TIME_STAMP - PLUGIN_STATS_RELOAD)) {
		unset($_SESSION['stats_lastpage']);
	}

	if(!checkBot($_SERVER['HTTP_USER_AGENT']) /*&& gethostbyaddr($_SERVER["REMOTE_ADDR"])!="d1.x-mailer.de"*/) {
		/* Menschlicher Besucher */
		$today = Database::instance()->query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'stats_day WHERE day=CURRENT_DATE')
				OR FatalError(FATAL_ERROR_MYSQL);
		$line = $today->fetch_row();

		/* Existiert der Heutige Tag in der DB ? */
		if (!$line[0]) {
			/* Erster Besucher Heute -> Neue DB-Zeile anlegen */
			Database::instance()->query('INSERT INTO '.DB_TABLE_PLUGIN.'stats_day(day)
					VALUES(CURRENT_DATE)')
					OR FatalError(FATAL_ERROR_MYSQL);
		}

		/* Sofern die Seite in der Datenbank noch nicht existiert, muss Sie erstellt werden */
		$result = Database::instance()->query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'stats_page
				WHERE page_id='.$HomepageContent['id'])
				OR FatalError(FATAL_ERROR_MYSQL);
		$line = $result->fetch_row();
		if (!$line[0]) {
			/* Seite in der Statistik neu anlegen */
			Database::instance()->query('INSERT INTO '.DB_TABLE_PLUGIN.'stats_page(page_id)
					VALUES('.$HomepageContent['id'].')')
					OR FatalError(FATAL_ERROR_MYSQL);
		}

		/** \todo Spam-Bots ausschliessen */

		/* Handelt es sich um einen NEUEN Besucher ? */
		$result = Database::instance()->query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'stats_ip
				WHERE ip="'.$_SERVER['REMOTE_ADDR'].'"')
				OR FatalError(FATAL_ERROR_MYSQL);
		$line = $result->fetch_row();

		if ($line[0] || isset($_SESSION['stats_lastpage'])) {
			/* Der Besucher wurde bereits gezählt -> Reload verlängern */
			Database::instance()->query('UPDATE '.DB_TABLE_PLUGIN.'stats_ip SET timestamp='.TIME_STAMP.'
					WHERE ip="'.$_SERVER['REMOTE_ADDR'].'"');
			$_SESSION['stats_lastpage'] = TIME_STAMP;
		}
		else {
			/* NEUER Besucher */
			Database::instance()->query('UPDATE '.DB_TABLE_PLUGIN.'stats_day SET visitors=(visitors+1)
					WHERE day=CURRENT_DATE')
					OR FatalError(FATAL_ERROR_MYSQL);

			Database::instance()->query('UPDATE '.DB_TABLE_ROOT.'cms_register SET number=(number+1)
					WHERE name="Stats_CtrVisitors"')
					OR FatalError(FATAL_ERROR_MYSQL);

			/* Seitenstatistik */
			Database::instance()->query('UPDATE '.DB_TABLE_PLUGIN.'stats_page SET visitors=(visitors+1)
					WHERE page_id='.$HomepageContent['id'])
					OR FatalError(FATAL_ERROR_MYSQL);

			/* IP abspeichern (Reloadsperre) */
			Database::instance()->query('INSERT INTO '.DB_TABLE_PLUGIN.'stats_ip(ip, timestamp)
					VALUES("'.$_SERVER["REMOTE_ADDR"].'", '.TIME_STAMP.')')
					OR FatalError(FATAL_ERROR_MYSQL);

			$_SESSION['stats_lastpage'] = TIME_STAMP;
		}

		/* Views zaehlen */
		Database::instance()->query('UPDATE '.DB_TABLE_PLUGIN.'stats_day SET views=(views+1)
				WHERE day=CURRENT_DATE')
				OR FatalError(FATAL_ERROR_MYSQL);

		Database::instance()->query('UPDATE '.DB_TABLE_PLUGIN.'stats_views SET views=(views+1)
				WHERE hour='.date('G', TIME_STAMP))
				OR FatalError(FATAL_ERROR_MYSQL);

		/* Seitenstatistik */
		Database::instance()->query('UPDATE '.DB_TABLE_PLUGIN.'stats_page SET views=(views+1)
				WHERE page_id='.$HomepageContent['id'])
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	else {
		/* BOT - Wird in einer seperaten Statistik gezaehlt */
		Database::instance()->query('UPDATE '.DB_TABLE_ROOT.'cms_register SET number=(number+1)
				WHERE name="Stats_CtrBots"')
				OR FatalError(FATAL_ERROR_MYSQL);
	}

	/* Anzeige alle Besucher */
	$result = Database::instance()->query('SELECT number FROM '.DB_TABLE_ROOT.'cms_register
			WHERE name="Stats_CtrVisitors"')
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = $result->fetch_assoc();
	$PluginContent['plugin_stat_allvisitors'] = $line['number'];

}

?>
