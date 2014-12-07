<?php

 /*
 =====================================================
 Name ........: ACP Funktionen
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: _functions_acp.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 05.05.2011 | Uebernommen und geaendert
 |2.0.2   | 14.09.2012 | writeAlbumConfig() hinzu
 |2.0.3   | 07.12.2014 | printInfoBox() hinzu
 -----------------------------------------------------
 Beschreibung :
 Alle Funktionen fuer den ACP, ausgenommen PlugIns.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////
// Admin Raenge                      //
///////////////////////////////////////
define("ACP_ACCESS_NOTALLOWED", 1<<0);
define("ACP_ACCESS_WEBSITE", 1<<1);
define("ACP_ACCESS_FILESYSTEM", 1<<2);
define("ACP_ACCESS_FILESYSTEM_IMAGES", 1<<12);
define("ACP_ACCESS_FILESYSTEM_DATA", 1<<12);
define("ACP_ACCESS_USER", 1<<3);
define("ACP_ACCESS_ADMIN", 1<<4);
define("ACP_ACCESS_M_NEWS", 1<<5);
define("ACP_ACCESS_M_NEWS_COM", 1<<6);
define("ACP_ACCESS_M_NEWS_LETTER", 1<<7);
define("ACP_ACCESS_M_NEWS_CAT", 1<<8);
define("ACP_ACCESS_M_POLL", 1<<9);
define("ACP_ACCESS_M_GUESTBOOK", 1<<10);
define("ACP_ACCESS_M_PHOTOS", 1<<11);
define("ACP_ACCESS_M_LAWDB", 1<<13);

/* Max Zeit 10min */
define("MAX_ACP_LOGIN_TIME", 600);

///////////////////////////////////////
// Allgemeine Funktionen             //
///////////////////////////////////////
/**
 * SessionDelete()
 * Loeschen der Session
 */
function SessionDelete() {
	$_SESSION['admin_id'] = 0;
	unset($_SESSION['admin_id'], $_SESSION['admin_password'], $_SESSION['admin_access']);
	
	// Sessions Daten loeschen
	//$_SESSION = array();
	// Session Cookie loeschen
	//if (isset($_COOKIE[session_name()])) {
    //	setcookie(session_name(), '', time()-42000, '/');
	//}
	// Session vom Server loeschen
	//session_destroy();
}

/**
 * LoginFormular($fehler)
 * Ausgabe des Loginformulars
 * @param int $fehler Fehlermeldungsnummer
 * 0 = Kein Fehler (Erster Login)
 * 1 = Falsches Passwort
 * 2 = Username existiert nicht
 * 3 = Time Out
 * 4 = Ausgelogt
 * 5 = Relogin
 */
function LoginFormular($fehler) {
	switch($fehler) {
		case 0:
			$nachricht = "";
			break;
		case 1:
			$nachricht = "Sie haben einene falschen Namen oder ein falsches Passwort eingegeben.";
			break;
		case 2:
			$nachricht = "Sie haben einene falschen Namen oder ein falsches Passwort eingegeben.";
			break;
		case 3:
			$nachricht = "Aufgrund längerer inaktivität wurden Sie automatisch abgemeldet.";
			break;
		case 4:
			$nachricht = "Sie haben sich erfolgreich abgemeldet.";
			break;
		case 5:
			$nachricht = "Aufgrund längerer inaktivität wurden Sie automatisch abgemeldet. Um Ihre Änderung abzuspeichern müssen Sie sich erneut anmelden.";
			break;
	}
	$tpl = new tpl("login");
	$tpl->assign("error", $nachricht);
	$tpl->assign("request_uri", str_replace("&", "&amp;", $_SERVER['REQUEST_URI']));
	$tpl->out();
}

/**
 * LoginSystem()
 * Ueberpruefen ob User angemeldet ist
 * @return bool Session=OK -> TRUE else FALSE
 */
function LoginSystem($db_cms) {
	if (isset($_SESSION['admin_id'], $_SESSION['admin_login'], $_SESSION['admin_password'],
			$_SESSION['admin_time_lastaction'])) {
		/* Inaktive Session loeschen */
//		if ($_SESSION['admin_time_lastaction'] < TIME_STAMP - MAX_ACP_LOGIN_TIME) {
//			// TIME OUT -> Session loeschen
//			SessionDelete();
//			return false;
//		}
//		$_SESSION['admin_time_lastaction'] = TIME_STAMP;
		/* Administrator pruefen */
		$result = mysql_query("SELECT name, access FROM ".DB_TABLE_ROOT."cms_admin
				WHERE admin_id=".$_SESSION['admin_id']." && login='".$_SESSION['admin_login']."'
				&& password='".$_SESSION['admin_password']."' && locked=0", $db_cms)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			/* Rechte aktualisieren falls sie geaendert wurden */
			if ($_SESSION['admin_access'] != $line['access'])
				$_SESSION['admin_access'] = $line['access'];
			if ($_SESSION['admin_name'] != $line['name'])
				$_SESSION['admin_name'] = $line['name'];
		}
		else {
			/* Admin wurde gesperrt oder Passwort gilt nicht mehr */
			SessionDelete();
			return false;
		}
		return true;
	}
	return false;
}

/**
 * ACP_Admin_Rechte($rang_zone)
 * Ueberprueft ob eine Admin diese Seite ansehen darf
 * @param $rang_zone In welche Kategorie diese Zone gehoert
 * @param $main Ist sie TRUE, so wird bei Unberechtigten automatisch die Errorseite gezeigt.
 * @return TRUE wenn er berechtigt ist sonst FALSE
 */
function ACP_AdminAccess($rang_zone, $main=false) {
	if ($rang_zone != ACP_ACCESS_NOTALLOWED && ($_SESSION['admin_access'] & $rang_zone)) {
		return true;
	}
	else {
		if ($main) {
			/* Error Seite anzeigen */
			/* Session loeschen */
			SessionDelete();
			LoginFormular(0);
			/* Weiteres ausfuehren verhindern */
			@mysql_close($db_cms);
			die();
		}
		else {
			return false;
		}
	}
}

/**
 * Kompletter Ordnerhirarchie erstellen
 */
function FileSystemFolders($folder_name, $level, $path) {
	global $FileSystem_ModulePahts;
	global $folderList;
	
	/* Darf kein Ordner von Modulen sein */
	if (!in_array($path.$folder_name.'/', $FileSystem_ModulePahts)) {
		/* Der Formular-Klasse hinzufuegen */
		$folderList->addOption(str_repeat('&nbsp;', 3*$level).$folder_name, $path.$folder_name.'/',
		(bool) (isset($_GET['folder']) && $path.$folder_name.'/' == $_GET['folder']));
		return true;
	}
	
	return false;
}

/**
 * Druckt eine Box (anstelle von Templates)
 * @return HTML-Code
 */
function printBox($title, $content, $icons=NULL, $info=NULL, $comment=NULL, $watchme=false) {
    if ($watchme)
    	$html = "    <li class=\"watchme\">\r\n";
   	else
   		$html = "    <li>\r\n";

	$html .= "      <h1>".$title."</h1>\r\n      <div>\r\n        ".$content."\r\n";

	if ($comment)
		$html .= "        <div class=\"comment\">\r\n          ".$comment."\r\n        </div>\r\n";

	if ($info || $icons)
		$html .= "        <hr />\r\n";

	if ($info) {
		if (is_array($info)) {
			$info_last = array_pop($info);
			foreach ($info as $info_element) {
				$html .= "        <p>".$info_element."</p>\r\n";
			}
		}
		else {
			$info_last = $info;
		}
	}

	if ($icons)
		$html .= "        <p class=\"icons\">".$icons."</p>\r\n";

	if (isset($info_last))
		$html .= "        <p>".$info_last."</p>\r\n";


	$html .= "      </div>\r\n    </li>\r\n";

	return $html;
}

function printBoxStart() {
	return "\r\n  <ol class=\"message\">\r\n";
}
function printBoxEnd() {
	return 	"  </ol>\r\n  <p class=\"form-after\">&nbsp;</p>\r\n";
}

/**
 * Erstellt eine Informationsbox.
 * @param[in] title Titel der Informationsbox.
 * @param[in] content Inhaltstext der Informationsbox.
 * @param[in] icons Array mit den Icons. Jedes Icon besteht aus einem assoziativen Array mit den Schluesseln icon, url, comment.
 */
function printInfoBox($title, $comment = '', $icons = NULL) {
	$html = '<div class="acp-infobox">
  <h2>'.$title.'</h2>
  '.$comment;

	if (is_array($icons)) {
		$html .= '<div class="icons">';
		foreach ($icons as $i) {
			$html .= ' <a href="'.$i['url'].'" onmouseover="Tip(\''.$i['comment'].'\')" onmouseout="UnTip()"><img src="'.$i['icon'].'" alt="" /></a>';
		}
		$html .= '</div>';
	}
	$html .= '</div>';

	return $html;
}


/**
 * Sicherung der Datenbank in einen Outputstream.
 * @param teble Array mit den Tabellen, die gesichert werden sollen. '*' fuer die komplete Datenbank.
 */
function mysql_backup__DEL_($stream, $tables='*') {
	$output = '';
	$eol = "\r\n";
	
	if($tables == '*') {
		/* Liste mit allen Tabellen erstellen */
		$tables = array();
		$result = mysql_query('SHOW TABLES', $stream);
		while($row = mysql_fetch_row($result)) {
			$tables[] = $row[0];
		}
	}
	else {
		$tables = is_array($tables) ? $tables : explode(',',$tables);
	}
	
	/* Alle Tabellen einxeln Sichern */
	foreach($tables as $table) {
		$result = mysql_query('SELECT * FROM '.$table, $stream);
		$num_fields = mysql_num_fields($result);
		
		/* Alte Tabelle loeschen */
		//$output .= 'DROP TABLE '.$table.';'.$eol.$eol;
		/* Tabele erstellen */
		//$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table, $stream));
		//$return.= $row2[1].';'.$eol.$eol;
		
		/* Alte Tabelle leeren */
		$output .= 'TRUNCATE '.$table.';'.$eol.$eol;
		
		/* Alle Daten der Tabelle speichern */
		//for ($i = 0; $i < $num_fields; $i++) 
		//{
			while($row = mysql_fetch_row($result)) {
				$output .= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++) 
				{
					$row[$j] = addslashes($row[$j]);
					$row[$j] = ereg_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) { $output .= '"'.$row[$j].'"' ; } else { $output .= '""'; }
					if ($j<($num_fields-1)) { $output .= ','; }
				}
				$output .= ');'.$eol;
			}
		//}
		$output .= $eol.$eol;
	}
	
	return $output;
}

/**
 * Speichert eine Connfig Datei in einem Albumordner
 * @param $album_path Pfad zum Album
 * @param $info assoziatives Array mit allen Albumseigenschaften
 */
function writeAlbumConfig($album_path, $info) {
	/* Temproaere Datei erstellen */
	$ftemp_config = tmpfile();
	if (!$ftemp_config)
		return false;
	
	/* Htaccess falls noetig */
	if ($info['locked'] == 1 || $info['access']){
		$ftemp_htaccess = tmpfile();
		if (!$ftemp_htaccess) {
			fclose($ftemp_config);
			return false;
		}
		
		/* Verzeichnisschutz erstellen */
		fwrite($ftemp_htaccess, "Order deny,allow\r\nDeny from all");
		fseek($ftemp_htaccess, 0);
	}
	
	/* Config erstellen */
	fwrite($ftemp_config, $info['sort'].'|'.$info['title'].'|'.$info['description']
			.'|'.$info['access'].'|'.$info['locked']);
	fseek($ftemp_config, 0);
	
	/* Config Datei mit FTP hochladen */
	$ftp = new ftp();
	$ftp->ChangeDir(substr($album_path, strlen('../'.FILESYSTEM_DIR)));
	$ftp->FilePut('config.txt', $ftemp_config);
	fclose($ftemp_config);
	if ($info['locked'] == 1 || $info['access']){
		$ftp->FilePut('.htaccess', $ftemp_htaccess);
		fclose($ftemp_htaccess);
	}
	else if (file_exists($album_path.'.htaccess')) {
		/* Verzeichnisschutz muss entfernt werden, sofern er vohanden ist */
		$ftp->Delete('.htaccess');
	}
	$ftp->close();
	
	return true;
}
function writeAlbumConfigFtp($ftp, $album_path, $info) {
	/* Temproaere Datei erstellen */
	$ftemp_config = tmpfile();
	if (!$ftemp_config)
		return false;
	
	/* Htaccess falls noetig */
	if ($info['locked'] == 1 || $info['access']){
		$ftemp_htaccess = tmpfile();
		if (!$ftemp_htaccess) {
			fclose($ftemp_config);
			return false;
		}
		
		/* Verzeichnisschutz erstellen */
		fwrite($ftemp_htaccess, "Order deny,allow\r\nDeny from all");
		fseek($ftemp_htaccess, 0);
	}
	
	/* Config erstellen */
	fwrite($ftemp_config, $info['access'].'|photos|'.$info['sort'].'|'.$info['title'].'|'.$info['description']
			.'|'.$info['locked']);
	fseek($ftemp_config, 0);
	
	/* Config Datei mit FTP hochladen */
	$ftp->ChangeDir($album_path);
	$ftp->FilePut('.config', $ftemp_config);
	fclose($ftemp_config);
	
	/* Verzeichnisschutz hochladen */
	if ($info['locked'] == 1 || $info['access']){
		$ftp->FilePut('.htaccess', $ftemp_htaccess);
		fclose($ftemp_htaccess);
	}
	else if ($ftp->fileExists($album_path.'.htaccess')) {
		/* Verzeichnisschutz muss entfernt werden, sofern er vohanden ist */
		$ftp->Delete('.htaccess');
	}
	
	return true;
}

?>