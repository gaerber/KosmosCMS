<?php

 /*
 =====================================================
 Name ........: Alle Funktionen
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: _functions.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 29.01.2011 | Uebernahme
 |2.0.1   | 13.10.2011 | moduleCallback Funktion
 |2.0.2   | 17.10.2011 | W3C Validation von Bildern.
 |2.0.3   | 25.02.2012 | ValidateFileSystem() in Plulic
 |2.0.4   | 14.09.2012 | Funktionen fuer Fotoalbum
 |2.0.5   | 23.10.2012 | REPORT_SPAM hinzu
 |2.0.6   | 21.11.2012 | Bugfix BinaryMultiples()
 |2.0.7   | 13.12.2012 | FTP Fotoalbum Funktionen
 |2.0.8   | 10.04.2013 | Bugfix class first StdWysiwymPrepare
 |2.0.9   | 29.04.2013 | ImageResizeFtp()
 |2.0.10  | 19.11.2014 | Bugfix ImageResize()
 |2.0.11  | 30.11.2014 | ImageResizeFtp fertig!
 |2.0.12  | 07.12.2014 | getRecursiveAlbumAccess hinzu
 -----------------------------------------------------
 Beschreibung :
 Alle Funktionen fuer die CMS Software

 (c) by Kevin Gerber
 =====================================================
 */

/**
 * Stellt die Datenbankverbindung her
 * @return Stream der Datenbankverbindung
 */
function DatabaseConnect() {
	/* Verbinden */
	$db_cms = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) OR FatalError(FATAL_ERROR_MYSQL);
	/* Datenbank waehlen */
	mysql_select_db(DB_NAME) OR FatalError(FATAL_ERROR_MYSQL);
	/* Verbindungskodierung */
	mysql_query("set names 'utf8'") OR FatalError(FATAL_ERROR_MYSQL);
	/* Rueckgabe */
	return $db_cms;
}

/**
 * Generiert den UNIX Zeitstempel inklusive Mikrosekunden zum berechnen der Runtime
 * @return UNIX Zeitstempel inklusive Mikrosekunden
 */
function getMicrotime() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

/**
 * Ausgabe einer Fehlermeldung und Abbruch des Programms
 */
define("FATAL_ERROR_MYSQL", 0);
define("FATAL_ERROR_CONTENT", 1);
define("FATAL_ERROR_MENU", 3);
define("FATAL_ERROR_FILE", 4);
function FatalError($error_nr) {
	/* Datenbankverbindung beenden */
	//mysql_close(DB_CMS);
	
	debug_print_backtrace();

	/* Fehlerausgabe und Programmabbruch */
	if ($error_nr == FATAL_ERROR_MYSQL)
		die("MySqlError: " . mysql_error(DB_CMS));
	if ($error_nr == FATAL_ERROR_CONTENT)
		die("Can't read error content!");
	if ($error_nr == FATAL_ERROR_MENU)
		die("Menu Error!");
	if ($error_nr == FATAL_ERROR_FILE)
		die("File System Error!");
}

/**
 * Erstellt eine MySQL Abfrage-Bedingung, damit nur Seiten herausgelesen
 * werden koennen, die fuer den Benutzer bestimmt sind.
 * @param[in] table Name der Tabelle. Verwendung bei komplexen Abfragen ueber mehrere Tabellen.
 * @return MySQL Query String fuer die WHERE Bedingung
 */
function CheckSQLAccess($table = '') {
	if ($table != '') {
		$table .= '.';
	}
	
	if (isset($_SESSION, $_SESSION['user_id'], $_SESSION['user_access'])) {
		/* Benutzer ist angemeldet */
		$sql = '(('.$table.'access = 0) || ('.$table.'access & '.$_SESSION['user_access'].'))';
	}
	else {
		/* Nicht angemeldeter Besucher */
		$sql = '('.$table.'access = 0)';
	}

	return $sql;
}

/**
 * Ueberpruefung ob der Besucher das recht hat diese Seite zu sehen
 *
 * @param $rechte Access-Flags der Seite
 * @return true falls er Zugriff hat, sonst false
 */
function CheckAccess($access) {
	/* Alle duerfen diese Seite sehen */
	if ($access == 0) {
		return true;
	}
	/* Nur angemeldete Besucher */
	if (isset($_SESSION, $_SESSION['user_id'], $_SESSION['user_access'])) {
		if ($access & $_SESSION['user_access']) {
			return true;
		}
	}
	/* Benutzer hat auf dieser Seite keine Berechtigung */
	return false;
}

/**
 * Informationen ueber einen Autor
 */
function getWriterInfo($a_id, &$a_name, &$a_email) {
	$result = mysql_query('SELECT name, email FROM '.DB_TABLE_ROOT.'cms_admin
			WHERE admin_id='.$a_id, DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
	if (mysql_num_rows($result)) {
		$line = mysql_fetch_array($result);
		$a_name = $line['name'];
		$a_email = $line['email'];
		return true;
	}
	
	/* Admin nicht gefunden */
	return false;
}

function getUserInfo($field) {
	if (isset($_SESSION[$field]))
		return $_SESSION[$field];
	return NULL;
}


/**
 * Datum vorbereiten
 */
function printDate($timestamp) {
	global $GlobalMonthsLAN;

	$temp = str_replace('%m%', '$1', FORMAT_DATE);

	$temp = date($temp, $timestamp);

	return str_replace('$1', $GlobalMonthsLAN[date('n', $timestamp) - 1], $temp);
}


/**
 * Datenbankeintraege zaehlen
 */
function mysql_count($sql_from, $sql_where) {
	if ($sql_where != '')
		$sql_where = ' WHERE '.$sql_where;
	$result = mysql_query('SELECT count(*) FROM '.$sql_from.$sql_where, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result))
		return $line[0];
	else
		return -1;
}

/**
 * Error Report der Homepage
 *
 * @param $Report Art des Reports: REPORT_OK, REPORT_ERROR, REPORT_INFO
 * @param $stringTitel ist der kurze Titel
 * @param $stringNachricht ist die ausfuehrliche Nachricht
 */
define('REPORT_OK', 0);
define('REPORT_EINGABE', 1);
define('REPORT_WARNING', 2);
define('REPORT_ERROR', 3);
define('REPORT_SPAM', 4);
define('REPORT_INFO', 5);
function ActionReport($report, $stringTitel, $stringNachricht) {
	switch ($report) {
		case REPORT_OK: $code = 'box ok'; break;
		case REPORT_EINGABE:
		case REPORT_WARNING:
		case REPORT_ERROR:
		case REPORT_SPAM: $code = 'box error'; break;
		case REPORT_INFO: $code = 'box info'; break;
	}
	$stringNachricht = preg_replace("/^<p>(.*)<\/p>$/", "$1", $stringNachricht);
	return "      <div class=\"".$code."\">
        <h1>".$stringTitel."</h1>
        <p>".$stringNachricht."</p>
      </div>";
}

function getIdStr($name, $db_tabel, $sql_conditions="", $db_col="id_str") {
	$name = ValidateFileSystem($name);
	
	/* Laenge pruefen */
	$name = substr($name, 0, 30);
	$name = preg_replace("/-$/", "", $name);
	
	if ($db_tabel == "NOCHECK")
		return $name;
	
	/* Abbruch nur durch return */
	while (true) {
        $result = mysql_query("SELECT count(*) FROM ".$db_tabel." 
				WHERE (".$db_col."='".$name."') ".$sql_conditions, DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		$line = mysql_fetch_array($result);
		if ($line[0]) {
			/* Ist eine numerische Zahl am Schluss */
			if (preg_match("/[0-9]+$/", $name)) {
				$num = preg_replace("/^(.*)([0-9]+)$/", "$2", $name);
				$num++;
				$name = substr($name, 0, 30 - strlen($num));
				$name = preg_replace("/^(.*)([0-9]+)$/", "$1", $name).$num;
			}
			else {
				$name = substr($name, 0, 20 - 1);
				$name .= "2";
			}
		}
		else {
			return $name;
		}
	}
}

/**
 * Falidiert einen Namen fuer das Filesystem
 */
function ValidateFileSystem($string, $zusatz=false) {
	/* Ueberfluessige Leerschlaege entfernen */
	$string = preg_replace("/ +/", " ", $string);
	$string = preg_replace("/^ +/", "", $string);
	$string = preg_replace("/ +$/", "", $string);

	/* Alles in Kleinbuchstaben */
	$string = mb_strtolower($string);

	/* Leerschlaege ersetzen */
	$string = str_replace(" ", "-", $string);

	/* Umlaute ersetzen */
	$string = str_replace("ä", "ae", $string);
	$string = str_replace("ö", "oe", $string);
	$string = str_replace("ü", "ue", $string);

	/* Franz. Umlaute */


	/* Doppel S */
	$string = str_replace("ß", "ss", $string);	// ß
    /* Kaufmaengisches Und */
    $string = str_replace("&amp;", "u", $string);		// &

	/* Murks entfernen */
	$validated = "";
	$Zeichen = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
			'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
			'1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '_', '-');
	if ($zusatz) {
		$Zeichen[] = $zusatz;
	}
	$anzZeichen = strlen($string);
	for ($i=0; $i<$anzZeichen; $i++) {
		$temp = substr($string, $i, 1);
		if (in_array($temp, $Zeichen)) {
			$validated .= $temp;
		}
	}

	/* Wiederholungen entfernen */
	$string = preg_replace("/-+/", "-", $string);
	$string = preg_replace("/-_-+/", "_", $string);
	$string = preg_replace("/_+/", "_", $string);

	/* Rueckgabe */
	return $validated;
}

/**
 * Email Adressen verschluesseln
 * @param $emailString Die Emailadresse, welche verschluesselt
 *        werden soll (String)
 * @param $mailto Soll "mailto:" fuer Link an den Anfang $mailto=1;
 *        Standart $mailto=0 (Freiwillig, Bool)
 * @return Die verschluesselte Emailadresse (String)
 */
function chgToUC($emailString, $mailto=0) {
    /* Assoziatives Array */
    /* Beinhaltet alle gueltigen Zeichen und ihr
       zugehoeriges Unicode-Pendant */
    $unicode = array(
        "a" => "&#97;",
        "b" => "&#98;",
        "c" => "&#99;",
        "d" => "&#100;",
        "e" => "&#101;",
        "f" => "&#102;",
        "g" => "&#103;",
        "h" => "&#104;",
        "i" => "&#105;",
        "j" => "&#106;",
        "k" => "&#107;",
        "l" => "&#108;",
        "m" => "&#109;",
        "n" => "&#110;",
        "o" => "&#111;",
        "p" => "&#112;",
        "q" => "&#113;",
        "r" => "&#114;",
        "s" => "&#115;",
        "t" => "&#116;",
        "u" => "&#117;",
        "v" => "&#118;",
        "w" => "&#119;",
        "x" => "&#120;",
        "y" => "&#121;",
        "z" => "&#122;",
        "-" => "&#45;",
        "." => "&#46;",
        "@" => "&#64;",
        "_" => "&#95;",
        "0" => "&#48;",
        "1" => "&#49;",
        "2" => "&#50;",
        "3" => "&#51;",
        "4" => "&#52;",
        "5" => "&#53;",
        "6" => "&#54;",
        "7" => "&#55;",
        "8" => "&#56;",
        "9" => "&#57;"
    );

    /* Alles in Kleinbuchstaben umwandeln */
    $emailString = mb_strtolower($emailString);
    /* Anzahl Zeichen, welche die Email Adresse beinhaltet */
    $anzZeichen = mb_strlen($emailString);
    /*$newString wird mit "mailto:" vorbelegt, falls erwuenscht */
    if ($mailto) {
        $newString = "&#109;&#97;&#105;&#108;&#116;&#111;&#58;";
    }
    else {
        $newString = "";
    }
    /* Die Schlaufe wird fuer jedes Zeichen einzeln duchlaufen */
    for ($i=0; $i<$anzZeichen; $i++){
        /* Das $i Zeichen selektieren */
        $part = mb_substr($emailString,$i,1);
        /* Das Zeichen ($part) wird durch seinen Unicode ersetzt
           und dem $newString beigefuegt */
        $newString .= $unicode[$part];
    }
    /* Die Funktion liefert die verschluesselte Email Adresse zurueck */
    return $newString;
}


/**
 * Das aktuelle Alter berechnen
 * @param $geburtsdatum in form von Jahr-Monat-Tag (String)
 * @return Das aktuelle Alter (Int)
 */
function Alter($geburtsdatum) {
    /* $geburtsdatum aufteilen in Jahr, Monat und Tag */
    $geburtsdatum = explode("-", $geburtsdatum);
    /* Heutiges Jahr - Geburtsjahr */
    $alter = date("Y") - $geburtsdatum[0];

    /* Timestamp des Geburtstages in diesem Jahr generieren */
    $differenz = mktime(0, 0, 0, $geburtsdatum[1],
            $geburtsdatum[2], date("Y"));
    /* Wurde in diesem Jahr bereits Geburtstag gefeiert ? */
    if ($differenz > TIME_STAMP) {
        /* Ein Jahr abziehen, da  noch kein Geburtstag in diesem Jahr */
        $alter--;
    }
    /* Das aktuelle Alter zuruekgeben */
    return $alter;
}


/**
 * BinaryMultiples
 * Generiert eine verstaendliche Zahl fuer eine Dateigroesse
 *
 * @param   $size          Groessen in Bytes
 *
 * @return  String in der Normgerundeten Form
 *
 * @version	1.1
 * @date	21.11.2012
 */
function BinaryMultiples($size) {
	$norm = array('B', 'kB', 'MB', 'GB');
	for ($i=0; $size >= 1024 && $i < sizeof($norm)-1; $i++) {
		$size /= 1024;
	}
	if ($i == 0)
		return sprintf("%01.0f", $size)." ".$norm[$i];
	else
		return sprintf("%01.2f", $size)." ".$norm[$i];
}


/**
 * URL Anzeige verkuerzen
 */
function getSmallUrlView($url) {
	$url = preg_replace("/\/$/s", "", $url);
	
	$service = preg_replace("/^(http|https|ftp|ftps)\:\/\/(.+)/s", "$1://", $url);
	$url = substr($url, strlen($service));
	$url_parts = explode("/", $url);
	
	if (sizeof($url_parts) > 2)
		return $service.$url_parts[0]."/.../".$url_parts[sizeof($url_parts)-1];
	if (sizeof($url_parts) == 2)
		return $service.$url_parts[0]."/".$url_parts[1];
	else
		return $service.$url_parts[0];
}


/**
 * Eingegebene Zeichenkette vorbereiten
 */
function StdString($string) {
	$string = str_replace("\\\"", "\"", $string);
	$string = str_replace("\\'", "'", $string);
	$string = str_replace("\\\\", "\\", $string);

	/* HTML-Eigene Zeichen ersetzten */
	/* Die Maske der Sonderzeichen wird hier auch gleich vorgenommen! */
	//$string = htmlentities($string, ENT_QUOTES);
	$string = htmlspecialchars($string, ENT_QUOTES, "UTF-8", true);
	//$string = preg_replace("/&amp;#(.[0-9]{0,3});/", "&$1;", $string);
	$string = str_replace("\\", "&#92;", $string);

	return $string;
}

/**
 * Einfaches Aera Feld als Eingabe
 */
function StdArea($string, $prepare_string=true) {
	if ($prepare_string)
		$string = StdString($string);

	$string = trim($string);

	$string = str_replace("\r\n", "<br />", $string);
	$string = str_replace("\n", "<br />", $string);
	$string = str_replace("\r", "<br />", $string);

	return $string;
}

/**
 * Eingabefeld fuer Inhalte
 */
function StdContent($string, $prepare_string=true) {
	$string = StdArea($string, $prepare_string);

	/* Bei lerem String Funktion nicht ausfuehren */
	if ($string == "")
		return "";

	/* Paragraph */
	$string = "<p>".$string."</p>";
	$string = preg_replace("/(<br \/>){2,}/i", "</p>\r\n<p>", $string);

	/* Links ersetzen */
	$string = str_replace("http://www.","www.",$string);
	$string = str_replace("www.","http://www.",$string);
	$string = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i",
			"<a href=\"$1\" target=\"_blank\">$1</a>", $string);
	/* Email Adressen ersetzen */
	$string = preg_replace("/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i",
			"<a href=\"mailto:$1\">$1</a>", $string);

	return $string;
}

/**
 * Bearbeitung fon Eingabefeldern fuer Inhalte
 */
function StdContentEdit($string) {
	/* Paragrphen rueckwandeln */
	$string = preg_replace("/^<p>/i", "", $string);
	$string = preg_replace("/<\/p>$/i", "", $string);
	$string = str_replace("</p>\r\n<p>", "\r\n\r\n", $string);
	/* Zeilenumbrueche rueckwandeln */
	$string = str_replace("<br />", "\r\n", $string);
	/* Links rueckwandeln */
	$string = preg_replace("/<a href=\"([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])\" target=\"_blank\">([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])<\/a>/i", "$1", $string);
	$string = preg_replace("/<a href=\"mailto:([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))\">([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))<\/a>/i", "$1", $string);
	return $string;
}

/**
 * Korrigieren der Fehler im HTML Code
 */
function StdWysiwymPrepare($string) {
	$string = str_replace("\\\"", "\"", $string);
	$string = str_replace("\\'", "'", $string);
	$string = str_replace("\\\\", "\\", $string);
	$string = str_replace("&#160;", " ", $string);

	/* Verwendung von relativen URLs */
	$string = preg_replace("/(href=\"|src=\")http:\/\/".$_SERVER["HTTP_HOST"]."/s", "$1", $string);

	/* Bilder Validierung nach W3C */
	$string = preg_replace_callback("/<img (.+) \/>/s", "callbackWysiwymImage", $string);
	
	/* Erste Ueberschrift */
	$string = str_replace(' class="first"', '', $string);
	$string = preg_replace("/^<h1>(.+)<\/h1>/s", "<h1 class=\"first\">$1</h1>", $string);

	return $string;
}

function callbackWysiwymImage($treffer) {
	if (!preg_match("/alt=\"(.+)\"/s", $treffer[1])) {
		return "<img ".$treffer[1]." alt=\"\" />";
	}
	return "<img ".$treffer[1]." />";
}

/**
 * SQL absichern
 */
function StdSqlSafety($string) {
	return mysql_real_escape_string($string, DB_CMS);
}

/**
 * PrepareEmail($string)
 * Prepariert einen Email-Text vor dem Senden (Zeilenumbrueche & Anfuehrungszeichen)
 * @param $string Email-Text
 * @return Preparierte Email-Text
 */
function StdStringEmail($string) {
	/* Zeilenumbrueche (Ohne Wagenrueklauf) */
	$string = StdArea($string, false);
	$string = str_replace("<br />", "\n", $string);

	/* Maskierung der Anfuerungszeichen entfernen */
	$string = str_replace("\'", "'", $string);
	$string = str_replace("\\\"", "\"", $string);
	/* Rueckgabe */
	return $string;
}


/*** CMS Callback Funktionen *****************************/

/**
 * Callback Function fuer alle Abstract Plugins
 * @param Array mit den Inhalttreffern
 * @return mixed Replace
 */
function pluginCallback($treffer) {
	global $PublicAllowedFunctions;
	if ($treffer[1] == "PHP") {
		/* Es handelt sich um ein include Module */
		if ($treffer[2] == "PluginRun") {
			$treffer[3] = str_replace("&quot;", "", $treffer[3]);
			if (!file_exists(ROOT_PLUGIN.$treffer[3]))
				return "PlugIn could not found!";
			ob_start();								// Buffer starten
			include(ROOT_PLUGIN.$treffer[3]);		// datei wird in Buffer geladen
			$plugin_content = ob_get_contents();	// Buffer wird in $content geschrieben
			ob_end_clean();							// Buffer wird geloescht
			return $plugin_content;
		}
	}
	else if ($treffer[1] == "FUNC") {
		/* Es handelt sich um eine Funktion, die aufgerufen werden soll */
		if (in_array($treffer[2], $PublicAllowedFunctions)) {
			/* Gewuenschte Funktion ausfuehren */
			$treffer[3] = str_replace("&quot;", "\"", $treffer[3]);
			$treffer[3] = str_replace(")", "", $treffer[3]);
			$treffer[3] = str_replace(";", "", $treffer[3]);
			ob_start();								// Buffer starten
			eval("echo ".$treffer[2]."(".$treffer[3].");");
			$plugin_content = ob_get_contents();	// Buffer wird in $content geschrieben
			ob_end_clean();							// Buffer wird geloescht
			return $plugin_content;
		}
		else {
			return "Not allowed Function!";
		}
	}
	/* Alles so stehen lassen */
	return $treffer[0];
}

/**
 * Callback Funktion um die Modul Parameter zu isolieren
 * $moduleParameter muss vorher global als Array definiert werden
 */
function moduleCallback($treffer) {
	/* Parameter Array */
	global $moduleParameter;
	
	/* Muss bereits als Array definert sein */
	if (!is_array($moduleParameter))
		die("Error in Modul Parameter!");
	
	/* Alle Leerschlaege entfernen */
	$treffer[1] = str_replace(" ", "", $treffer[1]);
	
	/* Selektion der moeglichen Parameter
	 * Jeder Parameter soll mit einem ';' enden */
	$para = explode(";", $treffer[1]);
	
	/* Parsen aller Parameter */
	for ($i=0; $i < sizeof($para); $i++) {
		$para_data = explode("=", $para[$i]);
		/* Jeder Parameter benoetigt eine Bezeichnug und einen Wert mit = zugewiesen */
		if (sizeof($para_data) == 2) {
			/* Parameter speichern */
			$moduleParameter[$para_data[0]] = $para_data[1];
		}
	}
	
	/* Modul Platzhalter */
	return "{MODUL}";
}


/*** Funktionen fuer Modul: Photoalbum *******************/

/**
 * Liest die Config Datei aus einem Album aus und prueft sie auf plausibilitaet.
 * @param $album_path Pfad zum Album
 * @return Rueckgabe in einem Assoziativen Array, fals im Fehlerfall
 */
function readAlbumConfig($album_path) {
	if (file_exists($album_path.'.config')) {
		$s_config = implode('', file($album_path.'.config'));
		$a_config = explode('|', $s_config);
		/* Auf schematische Fehler pruefen */
		if (sizeof($a_config) == 5 && is_numeric($a_config[0]) && is_numeric($a_config[3])
				&& is_numeric($a_config[4]))
			return array('sort' => $a_config[0], 'title' => $a_config[1],
					'description' => $a_config[2], 'access' => (int)$a_config[3],
					'locked' => (int)$a_config[4]);
	}
	return false;
}
function readAlbumConfigFtp($ftp, $album_path) {
	if ($ftp->fileExists($album_path.'.config')) {
		/* Config Datei einlesen */
		$s_config = $ftp->FileContents($album_path.'.config');
		
		/* Parameter auftrennen */
		$a_config = explode('|', $s_config);
		/* Auf schematische Fehler pruefen */
		if (sizeof($a_config) == 6 && is_numeric($a_config[2]) && is_numeric($a_config[0])
				&& is_numeric($a_config[5]) && $a_config[1]=='photos')
			return array('sort' => $a_config[2], 'title' => $a_config[3],
					'description' => $a_config[4], 'access' => (int)$a_config[0],
					'locked' => (int)$a_config[5]);
	}
	return false;
}

/**
 * Lesen der Album Informationen.
 * @param[in]	$ftp FTP Stream.
 * @param[in]	$album_path der komplete Pfad zum Album auf dem FTP Server.
 * @return		Assoziatives Array mit allen Informationen des Albums.
 */
function readAlbumConfig2($ftp, $album_path) {
	global $FileSystem_ModulePahts;
	
	/* Spezialbehandlung ROOT */
	if ($album_path == $FileSystem_ModulePahts['photos']) {
		return array('id' => 0, 'locked' => 0, 'access' => 0);
	}
	
	/* Config Datei einlesen */
	if ($config = $ftp->readFolderConfig($album_path)) {
		if (is_numeric($config['album_id'])) {
			$album_id = (int) $config['album_id'];
			/* Abfrage des Datenbankaekuivalents */
			$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id='.$album_id, DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line = mysql_fetch_assoc($result)) {
				return $line;
			}
		}
	}

	return false;
}

/**
 * Berechnet die vererbten Zugriffsrechte eunes Albums.
 * Bei einem eingeschraenkten Zugriff koennen die Fotos nur ueber das Download-Modul angezeigt werden.
 * @param[in] album_id Die ID des Albums.
 * @return Assoziatives Array mit den Werten von access und locked.
 */
function getRecursiveAlbumAccess($album_id) {
	$retval = array('access' => 0, 'locked' => 0);
	
	/* Rekursive Vererbung der Zurgiffsrechte 
	 * Quelle: http://wiki.yaslaw.info/dokuwiki/doku.php/mysql/AdjacencyTree/index */
	$sql = 'SELECT nav.access AS access, nav.locked AS locked
	FROM
	(
		SELECT  
			@cnt := @cnt + 1 AS cnt,
			-- Die letzte ParentID als ID ausgeben
			@id AS id,
			-- Die nächste ParentID ermitteln
			@id := IF(@id IS NOT NULL, (SELECT menu_sub FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id = @id), NULL) AS parentID
		FROM
			'.DB_TABLE_PLUGIN.'photoalbum AS nav,
			-- Die Variablen initialisieren
			(SELECT @id := '.$album_id.', @cnt:= 0) AS vars
		WHERE
			@id IS NOT NULL
	) AS dat
	-- Das ganze mit der Navigationstabelle verlinken on den Titel 
	-- und ggf. weitere Informationen auszulesen
	 INNER JOIN '.DB_TABLE_PLUGIN.'photoalbum AS nav
		ON dat.id = nav.id';
		
	$result = mysql_query($sql) OR FatalError(FATAL_ERROR_MYSQL);
	while ($row = mysql_fetch_assoc($result)) {
		/* Der selbe Mechanismus wie im Download-Modul */
		if ($row['access'] != 0) {
			$retval['access'] = ($retval['access'] != 0) ? $retval['access']&$row['access'] : $row['access'];
		}
		$retval['locked'] |= $row['locked'];
	}
	
	return $retval;
}


/**
 * Liste aller Unteralben und Fotos erstellen
 * @param $current_album Pfad zum Album
 * @param $a_albums Adresse auf Array, dort werden die gefundenen Alben gespeichert
 * @param $a_photos Adresse auf Array, dort werden die gefundenen Fotos gespeichert
 * @return Timestamp der neusten Datei/Ordner.
 */
function readAlbumPhotos($current_album, &$a_albums, &$a_photos, $sort_lists=false) {
	global $FileSystem_AllowedImageTypes;
	
	$data_handler = opendir($current_album);
	if (!$data_handler)
		return false;
	
	/* Default Wert fuer return */
	$newest_data = 1;
	
	/* Albumsortierungs-Hilfe */
	$a_album_sort = array();
	
	while($file = readdir($data_handler)) {
		if($file != '.' && $file != '..') {
			if (is_dir($current_album.$file)) {
				if ($album_info = readAlbumConfig($current_album.$file.'/')) {
					$a_albums[] = $file.'/';
					$a_album_sort[] = $album_info['sort'];
					if ($newest_data < filemtime($current_album.$file.'/'))
						$newest_data = filemtime($current_album.$file.'/');
				}
			}
			else {
				$infos = pathinfo($current_album.$file);
				if (isset($infos['extension']) 
						&& in_array(strtolower($infos['extension']), $FileSystem_AllowedImageTypes)) {
					$a_photos[] = $file;
					if ($newest_data < filemtime($current_album.$file))
						$newest_data = filemtime($current_album.$file);
				}
			}
		}
	}
	closedir($data_handler);
	
	/* Listen sortieren */
	if ($sort_lists) {
		array_multisort($a_album_sort, SORT_DESC, SORT_NUMERIC, $a_albums, SORT_ASC, SORT_STRING);
		array_multisort($a_photos, SORT_ASC, SORT_STRING);
	}
	
	return $newest_data;
}
function readAlbumPhotosFtp($ftp, $current_album, &$a_albums, &$a_photos, $sort_lists=false) {
	global $FileSystem_AllowedImageTypes;
	
	/* Verzeichnis das durchsucht werden soll, wird geoeffnet */
	$folder_pointer = $ftp->openDir($current_album);
	if (!$folder_pointer)
		return false;
	
	/* Default Wert fuer return */
	$newest_data = 1;
	
	/* Albumsortierungs-Hilfe */
	$a_album_sort = array();
	
	while($file = $folder_pointer->readDir()) {
		if ($folder_pointer->isDir($file)) {
			/* Moegliches Album */
			if ($album_info = readAlbumConfigFtp($ftp, $current_album.$file.'/')) {
				$a_albums[] = $file.'/';
				$a_album_sort[] = $album_info['sort'];
				$time = $ftp->fileTime($current_album.$file.'/config.txt');
				if ($newest_data < $time)
					$newest_data = $time;
			}
		}
		else {
			/* Moegliches Bild */
			if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)),
					$FileSystem_AllowedImageTypes)) {
				$a_photos[] = $file;
				$time = $ftp->fileTime($current_album.$file);
				if ($newest_data < $time)
					$newest_data = $time;
			}
		}
	}
	
	$ftp->closeDir($folder_pointer);
	
	/* Listen sortieren */
	if ($sort_lists) {
		array_multisort($a_album_sort, SORT_DESC, SORT_NUMERIC, $a_albums, SORT_ASC, SORT_STRING);
		array_multisort($a_photos, SORT_ASC, SORT_STRING);
	}
	
	return $newest_data;
}

/**
 * JPG Bilder Skalieren
 * @param $img_src Pfad zum Originalbild (lokal)
 * @param $img_dest Pfad zum Speicherort der Thumbnails (FTP Server)
 * @param $max_hight Maximale Hoehe des Thumbnails
 * @param $max_width Maximale Breite des Thumbnails
 * @param $quality Qualitaetsstufe des skalierten Bildes (in %, bei jpg & png)
 * @return true falls das Thumbnail erstellt wurde, sonst false
 */
function ImageResizeFtp($ftp, $img_src, $img_dest, $hight, $width, $proportional=true, $quality=80) {
	//return ImageResize('../upload'.$img_src, '../upload'.$img_dest, $hight, $width, $proportional, $quality);
	
	if ($ftp->fileExists($img_src)) {
		/* Daten in eine temporaere Datei packen (auf dem lokalen Server) */
		$image_src_temp = tempnam(FILESYSTEM_TEMP, 'img');
		$image_src_temp_resource = fopen($image_src_temp, 'rw+');
		if (!$image_src_temp_resource || !$ftp->FileRead($img_src, $image_src_temp_resource)) {
			return false;
		}
		fclose($image_src_temp_resource);
		/* Die Quelldatei muss ueberschrieben werden */
		$img_src = $image_src_temp;
	}
	else if (!file_exists($img_src)) {
		/* Quelldatei existiert nicht */
		return false;
	}
	
	/* Originalbildgroesse */
	$size_src = getimagesize($img_src);
    /* Erstellen des urspruenglichen Bildes */
    switch($size_src[2]) {
        /* GIF Grafik */
        case 1:
            $img_src_data = ImageCreateFromGIF($img_src);
        	break;
        /* JPG Grafik */
        case 2:
            $img_src_data = ImageCreateFromJPEG($img_src);
        	break;
        /* PNG Grafik */
        case 3:
            $img_src_data = ImageCreateFromPNG($img_src);
        	break;
        /* Keine Grafik */
        default:
            return false;
    }
    
    /* Falls eine temporaere Quelldatei existiert, kann diese non geloescht werden */
    if (isset($image_src_temp)) {
    	unlink($image_src_temp);
    }
    
    if ($img_src_data) {
    	/* Berechnung der Streckungsfaktoren */
    	$factor_h = $size_src[1] / $hight;
    	$factor_w = $size_src[0] / $width;
    	
    	/* Wie muss das Bild zugeschnitten werden */
    	if ($factor_h > $factor_w) {
    		/* Bild ist zu hoch -> Breite anpassen oder oben und unten abschneiden */
    		if ($proportional) {
    			$width = round($size_src[0] / $factor_h, 0);
   			}
   			else {
            	$size_src_big_w = $size_src[0];
            	$size_src_big_h = round($hight * $factor_w, 0);
				$x_offset = 0;
            	$y_offset = round(($size_src[1] - $size_src_big_h) / 2, 0);
   			}
    	}
    	else {
    		/* Bild ist zu breit -> Hoehe anpassen oder links und rechts abschneiden */
    		if ($proportional) {
    			$hight = round($size_src[0] / $factor_h, 0);
   			}
   			else {
            	$size_src_big_h = $size_src[1];
            	$size_src_big_w = round($width * $factor_h, 0);
				$y_offset = 0;
            	$x_offset = round(($size_src[0] - $size_src_big_w) / 2, 0);
   			}
    	}
   		
        /* Thumbnail anlegen */
		$img_thumb = ImageCreateTrueColor($width, $hight);
        
        if ($img_thumb) {
            /* Thumbnail skalieren */
            if ($proportional) {
            	$handler = imagecopyresized($img_thumb, $img_src_data, 0,0,0,0,
						$width, $hight, $size_src[0], $size_src[1]);
			}
            else {
            	$handler = imagecopyresized($img_thumb, $img_src_data, 0,0,$x_offset,$y_offset,
						$width, $hight, $size_src_big_w, $size_src_big_h);
            }
            
			if (!$handler) {
                /* Bilder loeschen */
                imagedestroy($img_src_data);
                imagedestroy($img_thumb);
                return false;
            }
            
			/* Temporaerer Speicherort auf Server */
			$image_scaled_temp = tempnam(FILESYSTEM_TEMP, 'img');
	
			$bool = false;
			if (file_exists($image_scaled_temp)) {
	            switch($size_src[2]) {
	                /* GIF */
	                case 1:
						$bool = ImageGIF($img_thumb, $image_scaled_temp);
	                	break;
	                /* JPG */
	                case 2:
	                    $bool = ImageJPEG($img_thumb, $image_scaled_temp, $quality);
	                	break;
	                /* PNG */
	                case 3:
	                    $bool = ImagePNG($img_thumb, $image_scaled_temp, round($quality / 10, 0));
	                    break;
	            }
	            
	            /* Thumbnail hochladen per FTP */
				if ($bool) {
					/* FTP Upload */
					$bool = $ftp->FilePut($img_dest, $image_scaled_temp);
	            }
	            unlink($image_scaled_temp);
      		}

            /* Bilder loeschen */
            imagedestroy($img_src_data);
            imagedestroy($img_thumb);
            
            /* Thumbnail sollte erstellt sein */
            return $bool;
        }
        else {
            imagedestroy($img_src_data);
            return false;
        }
        
    }
    else {
         return false;
    }
}
function ImageResize($img_src, $img_dest, $hight, $width, $proportional=true, $quality=80) {
	if (!file_exists($img_src)) {
	    return false;
    }
        
    /* Originalbildgroesse */
    $size_src = getimagesize($img_src);

    /* Erstellen des urspruenglichen Bildes */
    switch($size_src[2]) {
        /* GIF Grafik */
        case 1:
            $img_src_data = ImageCreateFromGIF($img_src);
        	break;
        /* JPG Grafik */
        case 2:
            $img_src_data = ImageCreateFromJPEG($img_src);
        	break;
        /* PNG Grafik */
        case 3:
            $img_src_data = ImageCreateFromPNG($img_src);
        	break;
        /* Keine Grafik */
        default:
            return false;
    }
    
    if ($img_src_data) {
    	/* Berechnung der Streckungsfaktoren */
    	$factor_h = $size_src[1] / $hight;
    	$factor_w = $size_src[0] / $width;
    	
    	/* Wie muss das Bild zugeschnitten werden */
    	if ($factor_h > $factor_w) {
    		/* Bild ist zu hoch -> Breite anpassen oder oben und unten abschneiden */
    		if ($proportional) {
    			$width = round($size_src[0] / $factor_h, 0);
   			}
   			else {
            	$size_src_big_w = $size_src[0];
            	$size_src_big_h = round($hight * $factor_w, 0);
				$x_offset = 0;
            	$y_offset = round(($size_src[1] - $size_src_big_h) / 2, 0);
   			}
    	}
    	else {
    		/* Bild ist zu breit -> Hoehe anpassen oder links und rechts abschneiden */
    		if ($proportional) {
    			$hight = round($size_src[0] / $factor_h, 0);
   			}
   			else {
            	$size_src_big_h = $size_src[1];
            	$size_src_big_w = round($width * $factor_h, 0);
				$y_offset = 0;
            	$x_offset = round(($size_src[0] - $size_src_big_w) / 2, 0);
   			}
    	}
   		
        /* Thumbnail anlegen */
		$img_thumb = ImageCreateTrueColor($width, $hight);
        
        if ($img_thumb) {
            /* Thumbnail skalieren */
            if ($proportional) {
            	$handler = imagecopyresized($img_thumb, $img_src_data, 0,0,0,0,
						$width, $hight, $size_src[0], $size_src[1]);
			}
            else {
            	$handler = imagecopyresized($img_thumb, $img_src_data, 0,0,$x_offset,$y_offset,
						$width, $hight, $size_src_big_w, $size_src_big_h);
            }
            
			if (!$handler) {
                /* Bilder loeschen */
                imagedestroy($img_src_data);
                imagedestroy($img_thumb);
                return false;
            }
            
			/* Thumbnail abspeichern */
			//$server_temp = 'temp/thumb.tmp';
            switch($size_src[2]) {
                /* GIF */
                case 1:
					$bool = ImageGIF($img_thumb, $img_dest);
                	break;
                /* JPG */
                case 2:
                    $bool = ImageJPEG($img_thumb, $img_dest, $quality);
                	break;
                /* PNG */
                case 3:
                    $bool = ImagePNG($img_thumb, $img_dest, $quality);
                    break;
            }
            
            /*if ($bool) {
	            /* Test FTP Upload *
	            $ftp = new ftp();
				$ftp->ChangeDir('/');
				$bool = $ftp->FilePut($img_dest, $server_temp);
				$ftp->close();
            }*/

            /* Bilder loeschen */
            imagedestroy($img_src_data);
            imagedestroy($img_thumb);
            
            /* Thumbnail sollte erstellt sein */
            return $bool;
        }
        else {
            imagedestroy($img_src_data);
            return false;
        }
        
    }
    else {
         return false;
    }
}

?>