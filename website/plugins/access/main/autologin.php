<?php

 /*
 =====================================================
 Name ........: Plugin: Access System Autologin
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: autologin.php
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
 Plugin: Automatische anmeldung fuer Benutzer.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_ACCESS_SYSTEM_EN && MAX_AUTOLOGIN_TIME) {
	if (!isset($_SESSION['user_id'], $_SESSION['user_login'], $_SESSION['user_password'])
			&& isset($_COOKIE['cms_autologin_login']) && isset($_COOKIE['cms_autologin_password'])) {
		$result = Database::instance()->query("SELECT user_id, user_login, user_password, user_lastlogin, user_access
				FROM ".DB_TABLE_ROOT."cms_access_user
				WHERE user_login='".StdSqlSafety($_COOKIE["cms_autologin_login"])."'
				&& user_password='".StdSqlSafety($_COOKIE["cms_autologin_password"])."'
				&& user_lastlogin>=".(TIME_STAMP - MAX_AUTOLOGIN_TIME)."
				&& user_locked=0")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			/* Anmeldung erfolgreich -> in Session abspeichern */
			$_SESSION['user_id'] = $line['user_id'];
			$_SESSION['user_login'] = $line['user_login'];
			$_SESSION['user_password'] = $line['user_password'];
			$_SESSION['user_lastlogin'] = $line['user_lastlogin'];
			$_SESSION['user_access'] = $line['user_access'];
			/* Cookie erneuern */
			setcookie("cms_autologin_login", $line['user_login'], TIME_STAMP + MAX_AUTOLOGIN_TIME, "/");
			setcookie("cms_autologin_password", $line['user_password'], TIME_STAMP + MAX_AUTOLOGIN_TIME, "/");
			/* Letzter Login nachtragen */
			Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_access_user SET user_lastlogin=".TIME_STAMP."
					WHERE user_id=".$_SESSION['user_id'])
					OR FatalError(FATAL_ERROR_MYSQL);
		}
		else {
			/* Logindaten nicht gültig -> Cookies loeschen */
			$_COOKIE['cms_autologin_login'] = NULL;
			$_COOKIE['cms_autologin_password'] = NULL;
			setcookie("cms_autologin_login", "", TIME_STAMP - 3600, "/");
			setcookie("cms_autologin_password", "", TIME_STAMP - 3600, "/");
		}
	}
}

?>
