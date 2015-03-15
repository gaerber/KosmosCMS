<?php

 /*
 =====================================================
 Name ........: Plugin: Access System Logout
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: logout.php
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
 Plugin: Einen Benutzer abmelden.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_ACCESS_SYSTEM_EN) {
	if (isset($_GET['cms_logout'])) {
		/* Benutzerdaten aus Session loeschen */
		$_SESSION['user_id'] = 0;
		$_SESSION['user_access'] = 0;
		unset($_SESSION['user_login'], $_SESSION['user_password']);
		
		/* Autologin Cookie loeschen */
		$_COOKIE['cms_autologin_login'] = NULL;
		$_COOKIE['cms_autologin_password'] = NULL;
		setcookie("cms_autologin_login", "", TIME_STAMP - 3600, "/");
		setcookie("cms_autologin_password", "", TIME_STAMP - 3600, "/");
	}
}

?>