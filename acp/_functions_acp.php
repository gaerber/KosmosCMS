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
 |2.1     | 11.12.2014 | FTP Dateisystem
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

/* Max Zeit 10min */
define("MAX_ACP_LOGIN_TIME", 600);

///////////////////////////////////////
// Allgemeine Funktionen             //
///////////////////////////////////////
/**
 * Loeschen der Session
 */
function SessionDelete() {
	$_SESSION['admin_id'] = 0;
	unset($_SESSION['admin_id'], $_SESSION['admin_password'], $_SESSION['admin_access']);
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
		/* Administrator pruefen */
		$result = $db_cms->query("SELECT name, access FROM ".DB_TABLE_ROOT."cms_admin
				WHERE admin_id=".$_SESSION['admin_id']." && login='".$_SESSION['admin_login']."'
				&& password='".$_SESSION['admin_password']."' && locked=0")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
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
			Database::instance()->close();
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

?>
