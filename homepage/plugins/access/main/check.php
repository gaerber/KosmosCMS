<?php

 /*
 =====================================================
 Name ........: Plugin: Access System Check
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: check.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 02.10.2011 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin: Ueberprueft einen Benutzer.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_ACCESS_SYSTEM_EN) {
	if (isset($_SESSION['user_id'], $_SESSION['user_login'], $_SESSION['user_password'])
			&& $_SESSION['user_id']) {
		/* Angemeldeter Benutzer */
		/* Angaben pruefen */
		$result = mysql_query("SELECT user_name, user_email, user_email_show, user_tel,
				user_website, user_description, user_access
				FROM ".DB_TABLE_ROOT."cms_access_user
				WHERE user_id=".$_SESSION['user_id']."
				&& user_login='".$_SESSION['user_login']."'
				&& user_password='".$_SESSION['user_password']."'
				&& user_locked=0")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_assoc($result)) {
			/* Alle Infos aktualisieren */
			$_SESSION = array_merge($_SESSION, $line);
		}
		else {
			/* Ungueltige angaben -> Abmelden */
			/* Benutzerdaten aus Session loeschen */
			$_SESSION['user_id'] = 0;
			$_SESSION['user_access'] = 0;
			unset($_SESSION['user_login'], $_SESSION['user_password']);
			/* Autologin Cookie loeschen */
			setcookie("cms_autologin_login", "", TIME_STAMP - 3600);
			setcookie("cms_autologin_password", "", TIME_STAMP - 3600);
			/* Logindaten nicht gültig -> Fehlerausgabe */
			$show_error_page = $DefaultErrorPages['550'];
		}
	}
	else {
		/* Nicht angemeldet -> Gast */
		$_SESSION['user_id'] = 0;
		$_SESSION['user_access'] = 0;
	}
}

?>