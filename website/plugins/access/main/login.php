<?php

 /*
 =====================================================
 Name ........: Plugin: Access System Login
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: login.php
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
 Plugin: Benutzeranmeldung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_ACCESS_SYSTEM_EN) {
	/* Darf noch nicht angemeldet sein */

	/* Logindaten forhanden */
	if (isset($_POST['cms_login'], $_POST['cms_password'])
			&& $_POST['cms_login'] != "" && $_POST['cms_password'] != "") {
		$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_ROOT."cms_access_user
				WHERE user_login='".StdSqlSafety($_POST['cms_login'])."'
				&& user_password='".sha1($_POST['cms_password'])."'
				&& user_locked=0")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			/* Anmeldung erfolgreich -> in Session abspeichern */
			$_SESSION = array_merge($_SESSION, $line);
			/*$_SESSION['user_id'] = $line['user_id'];
			$_SESSION['user_login'] = $line['user_login'];
			$_SESSION['user_password'] = $line['user_password'];
			$_SESSION['user_access'] = $line['user_access'];
			$_SESSION['user_lastlogin'] = $line['user_lastlogin'];*/
			/* Autologin */
			if (isset($_POST['cms_autologin']) && $_POST['cms_autologin'] == 1) {
				/* Cookie speichern */
				setcookie("cms_autologin_login", $line['user_login'], TIME_STAMP + MAX_AUTOLOGIN_TIME);
				setcookie("cms_autologin_password", $line['user_password'], TIME_STAMP + MAX_AUTOLOGIN_TIME);
			}
			/* Letzter Login nachtragen */
			Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_access_user SET user_lastlogin=".TIME_STAMP."
					WHERE user_id=".$_SESSION['user_id'])
					OR FatalError(FATAL_ERROR_MYSQL);
		}
		else {
			/* Logindaten nicht gültig -> Fehlerausgabe */
			$show_error_page = $DefaultErrorPages['550'];
		}
	}
	/* ELSE: Keine Logindaten oder Felder sind leer */
}

?>
