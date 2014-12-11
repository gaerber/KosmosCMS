<?php

 /*
 =====================================================
 Name ........: Einstellungen
 Projekt .....: CMS 2.0 Kosmos DEMOSEITE gaerber.ch v5
 Datiename ...: _settings.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 28.04.2011 | Programm erstellt
 |2.1     | 04.08.2011 | Menuegenerierung
 |2.2     | 04.10.2011 | Plugins
 |2.3     | 12.02.2012 | Add Default Error Page List
 |2.4     | 14.02.2012 | Module Ordner sperre
 |2.4.1   | 23.10.2012 | Modul SPAM-Schutz hinzu
 |2.4.3   | 19.11.2012 | Wochentage fuer Statistik
 |2.4.4   | 31.03.2013 | Ordner mysqlbackups hinzu
 |2.4.5   | 25.11.2014 | FILE_SYSTEM_CONFIGFILE hinzu
 -----------------------------------------------------
 Beschreibung :
 Einstellungen fuer die CMS Software

 (c) by Kevin Gerber
 =====================================================
 */

/**
 * Unix Zeitstempel (int)
 */
define('TIME_STAMP', time());

/**
 * Format der Daten im date() Standart
 */
define('FORMAT_DATE', 'j. %m% Y');

/**
 * Format der Daten im date() Standart
 */
define('FORMAT_TIME', 'H:i');

/**
 * Ausgeschriebene Wochentage
 */
$GlobalWeekdaysLAN = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');

/**
 * Ausgeschriebene Monate
 */
$GlobalMonthsLAN = array('Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
		'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');

/**
 * Erlaubte Oeffentliche Abstract Funktionen
 */
$PublicAllowedFunctions = array('BinaryMultiples', 'Alter', 'ActionReport', 'chgToUC', 'TheTime');


/*** Datenbankverbindung ****************************/
/**
 * Datenbank Name
 */
define('DB_NAME', 'gaerber_cms2');

/**
 * Datenbank Host
 */
define('DB_HOST', 'localhost');

/**
 * Datenbank Username
 */
define('DB_USER', 'cms2_user');

/**
 * Datenbank Passwort
 */
define('DB_PASSWORD', 'Atc6NEWGTjEWHLbXJA');

/**
 * Tabellen Vor-Name
 */
define('DB_TABLE_ROOT', '');

/**
 * Datenbankname fuer die Plugins (excl. DB_TABLE_ROOT)
 */
define('DB_TABLE_PLUGIN', DB_TABLE_ROOT.'cms_plugin_');


/*** FTP Einstellungen ******************************/
/**
 * FTP Server
 */
define('FTP_SERVER_HOST', 'ftp.gaerber.ch');

/**
 * FTP Server Port
 */
define('FTP_SERVER_HOST_PORT', 21);

/**
 * Use SSL Connection
 */
define('FTP_SERVER_USE_SSL', true);

/**
 * FTP User
 */
define('FTP_USER', 'gaerber_cms');

/**
 * Passwort
 */
define('FTP_USER_PASSWORD', 'RLwaFMvT8ZbMNetdt2V3ZtUs');

/**
 * FTP Startverzeichnis
 */
define('FTP_DIR', '/');

/**
 * Startverzeichnis der oeffentlichen Dateien
 */
define('FILESYSTEM_DIR', 'upload');
define('FILESYSTEM_DIR_V21', '/upload');

/**
 * Temporaerer Ordner fuer bestimmte Funktionen.
 * Vom Webseiten Verzeichnis aus.
 * sys_get_temp_dir() benoetigt PHP5
 */
define('FILESYSTEM_TEMP', sys_get_temp_dir().'/');

/**
 * Maximale erlaubte Dateigroesse beim Upload
 * in Bytes
 */
define('FILE_SIZE_LIMIT', 3145728);

/**
 * Alle erlaubten Bildertypen
 */
$FileSystem_AllowedImageTypes = array('jpg', 'gif', 'png');

/**
 * Alle erlaubten Dateitypen
 */
$FileSystem_AllowedDataTypes = array_merge($FileSystem_AllowedImageTypes,
		array('zip', 'rar', 'tar', 'pdf', 'swf', 'doc', 'docx', 'dot', 'xls', 'xlsx', 'ppt', 'pps', 'pptx', 'ppsx',
				'exe', 'txt', 'html', 'sql', 'bmp', 'jpeg', 'mpeg', 'mpg', 'mov', 'avi'));

/**
 * Ordnerpfade zu den Modulen (wird mit FTP_DIR zusammengesetzt)
 * Diese Ordner koennen im File System nicht bearbeitet werden
 */
$FileSystem_ModulePahts = array(
		'photos' => '/photos2/',
		'user-system-images' => '/benutzer-bilder/',
		'mysqlbackups' => '/private-mysql-backups/'
);

/**
 * Dateinamen der Konfigurationen auf dem Server
 */
define('FILE_SYSTEM_CONFIGFILE', '.config');


/*** Ordnerstruktur *********************************/
/**
 * Installationsort der Homepage (fuer Mode ReWrite)
 */
define('ROOT_WEBSITE', '/');

/**
 * URL Endung einer Kategorie
 */
define('URL_ENDSTR_CATEGORIE', '/index.html');

/**
 * URL Endung einer Seite
 */
define('URL_ENDSTR_PAGE', '.html');

/**
 * Stammverzeichnis der PlugIns
 * (Relativ vom index Verieichnis aus!)
 */
define('ROOT_PLUGIN', 'plugins/');

/**
 * Stammverzeichnis der AllPage PlugIns
 * (Relativ vom index Verieichnis aus!)
 */
define('ROOT_PLUGIN_ALLPAGE', 'plugins/_all_page/');

/**
 * Stammverzeichnis der Templates
 */
define('ROOT_TEMPLATE', 'templates/');

/**
 * Dateityp der Templates
 */
define('TEMPLATE_TYPE', '.htm');

/**
 * Default Error Pages
 */
$DefaultErrorPages = array('403' => 1,
		'404' => 2,
		'550' => 3);

define('ROOT_IMAGES', 'img/website/');


/*** Module Freigabe ********************************/
/**
 * Dateiverwaltung
 */
define('ACP_FILE_SYSTEM_EN', true);

/**
 * Benutzerverwaltung
 */
define('ACP_USER_SYSTEM_EN', true);

/**
 * Berechtigungssystem (Vorausgesetzt: ACP_USER_SYSTEM_EN)
 */
define('ACP_ACCESS_SYSTEM_EN', true);

/**
 * Allgemeriner Spamschutz bei Formularen
 */
define('ACP_MODULE_SPAM', true);

/**
 * Verwendung eines Captcha als Spamschutz
 */
define('ACP_MODULE_SPAM_CAPTCHA', false);

/**
 * Drag ans Drop Dateiupload.
 */
define('ACP_MODULE_DRAG_AND_DROP', true);

/**
 * Interne Besucherstatistik
 */
define('ACP_MODULE_STATISTIC', true);

/**
 * Newssystem
 */
define('ACP_MODULE_NEWS_EN', true);

/**
 * Newsletter (Vorausgesetzt: ACP_USER_SYSTEM_EN)
 */
define('ACP_MODULE_NEWSLETTER_EN', true);

/**
 * Umfrage
 */
define('ACP_MODULE_POLL_EN', true);

/**
 * Gaestebuch
 */
define('ACP_MODULE_GUESTBOOK_EN', true);

/**
 * Fotoalbum
 */
define('ACP_MODULE_PHOTOS_EN', true);

/**
 * Linkverzeichnis
 */
define('ACP_MODULE_LAWDB_EN', false);


/*** Plugin Seitenzahlen ****************************/
/**
 * SeitenZahlen: Anzahl Eintraege pro Seite
 */
define('PAGINATION_PER_PAGE', 7);

/**
 * SeitenZahlen: Anzahl Zeileneintraege pro Seite
 */
define('PAGINATION_PER_PAGE_LINE', 20);

/**
 * SeitenZahlen: Anzahl Nummerische Links
 */
define('PAGINATION_NUM', 5);


/*** Website ****************************************/
/**
 * Benutzerfreundliche Links erlauben (ReWrite Mode on)
 */
define('MODE_REWRITE_ON', true);

/**
 * Indexdatei (Meistens index.php)
 */
define('INDEX_FILE', 'index');

/**
 * GET Parameter mit dem Seitenname (ID)
 */
define('CONTENT_POINTER', 'cms_seite');

/**
 * GET Parameter mit der aktuellen Seite von den Seitenzahlen
 */
define('PAGE_POINTER', 'seite');

/**
 * GET Parameter fuer die Suche
 */
//define('SEARCH_POINTER', 'suche');


/*** Menue ******************************************/
/**
 * Anzahl moegliche Ebene gesamt
 */
define('MENU_MAX_LEVEL', 4);

/**
 * Anzahl moegliche Ebene, die in der Navigation
 * sichtbar werden
 */
define('MENU_MAX_LEVEL_VIEW', 3);

/**
 * Anzahl moeglicher Ebene fuer Kategorien
 */
define('MENU_MAX_LEVEL_CATEGORIE', 1);

/**
 * Menuegenerierungs Einstellungen
 * 1. Startkategorie
 * 2. Max. anz. Ebene
 * 3. Anz. Ebene die angezeigt werden
 * 4. Kompletter Stammbaum anzeigen
 * 5. Template Ordner
 */
$array_MenuConfigurations = array(
		'menu_main' => array(0,1,1,true,'menu/main/def{active}'),
		'menu_second' => array(-1,2,1,false,'menu/second/level{level}/{active}/{pos}'),
		'menu_path' => array('menu/path/def')
	);


/*** Admin Controll Panel ***************************/
/**
 * Enable Keywords (Tags)
 */
define('ACP_TAGS_EN', true);

/**
 * Enable Sloagen
 */
define('ACP_SLOGAN_EN', true);

/**
 * Enable Seiten und Kategorien spezifisches Bild
 */
define('ACP_IMAGE_EN', false);

/**
 * Enable Seiten und Kategorien spezives Bild
 */
define('DEFAULT_IMAGE_PAGE', '');

/**
 * Enable Seiten und Kategorien spezives Bild
 */
define('DEFAULT_IMAGE_CATEGORIE', '');


/*** Benutzerverwaltung *****************************/
if (ACP_USER_SYSTEM_EN) {
	/**
	 * Benutzerdefinierte Felder
	 */
	$UserSystem_customParameters = array(
				array('user_description', 'text', 'Beschreibung')
				/*array('user_points', 'text', 'Punkte'),
				array('user_tel', 'text', 'Telefon vorhanden')*/
			);
	
	/**
	 * Benutzerbilder
	 */
	$UserSystem_imagesSettings = array(
				'height' => 100,
				'width' => 100,
				'default' => 'default/acp.png'
			);
}


/*** Security System ********************************/
if (ACP_ACCESS_SYSTEM_EN) {
	/**
	 * Time Out in Sekunden 600 = 10min
	 */
	define('MAX_LOGIN_TIME', 600);
	
	/**
	 * Gueltigkeitsdauer, in Sekunden, der Cookies fuer das Autologin
	 * 2419200 = 4 Wochen
	 * 0 = Autologin nicht erlauben
	 */
	define('MAX_AUTOLOGIN_TIME', 2419200);
}


/*** Plugin: Suche **********************************/
if (false) {
	/**
	 * Enable des Plugins
	 */
	define('PLUGIN_SUCHE_ENABLE', true);
	
	/**
	 * Seitenname in der das Plugin Suche ausgefuehrt wird
	 */
	define('PLUGIN_SUCHE_SEITENNAME', 'suche');
	
	/**
	 * GET Parameter mit dem Suchstring
	 */
	define('PLUGIN_SUCHE_ZEIGER', 'suche');
	
	/**
	 * Resultate pro Seite
	 */
	define('PLUGIN_SUCHE_ANZTREFFER', 10);
	
	/**
	 * Darstellung der Seitenzahlen
	 */
	define('PLUGIN_SUCHE_SEITENZAHLEN', 5);
	
	/**
	 * Highlight Links Enable
	 */
	define('PLUGIN_SUCHE_HIGHLIGHT', false);
}


/*** Modul: Neuigkeiten *****************************/
if (ACP_MODULE_NEWS_EN) {
	/* GET Parameter */
	define('PLUGIN_NEWS_GETP_LONGNEWS', 'artikel');
	define('PLUGIN_NEWS_GETP_COM', 'artikel');
	define('PLUGIN_NEWS_GETP_CAT', 'kategorie');
	define('PLUGIN_NEWS_GETP_WRITER', 'autor');
	define('PLUGIN_NEWS_GETP_COM_PAGE', 'seite');
	
	/**
	 * News Kommentare Freigabe
	 */
	define('PLUGIN_NEWS_COMMENT_EN', true);
	
	/**
	 * Seitenzahlen mit allen News
	 */
	define('PLUGIN_NEWS_VIEW_ALL', true);
	
	/**
	 * Anzahl News, die maximal Ausgegeben werden koennen
	 */ 
	define('PLUGIN_NEWS_NUM', 4);
	
	/**
	 * Maximale Anzahl Kommentare pro Seite
	 */ 
	define('PLUGIN_NEWS_NUM_COMMENT', 7);
}


/*** Modul: Gaestebuch ******************************/
if (ACP_MODULE_GUESTBOOK_EN) {
	define('PLUGIN_GUESTBOOK_GETP_PAGE', 'seite');
	
	/**
	 * Anzahl News, die maximal Ausgegeben werden koennen
	 */ 
	define('PLUGIN_GUESTBOOK_NUM', 4);
}


/*** Modul: Photoalbum ******************************/
if (ACP_MODULE_PHOTOS_EN) {
	define('MODULE_PHOTOS_GETP_ALBUM', 'album');
	
	/**
	 * Unterordner fuer Thumbnails
	 */
	define('MODULE_PHOTOS_THUMB', 'thumb/');
	
	 /**
	  * Anzahl Vorschaubilder eines Albums
	  */
	define('MODULE_PHOTOS_ALBUMPREVIEWPICS', 3);
	
	/**
	 * Thumbnail einstellungen
	 */
	$PluginPhotos_imagesSettings = array(
				'height' => 120,
				'width' => 180,
				'proportional' => false,
				'default' => 'default/acp.png'
			);
}


?>