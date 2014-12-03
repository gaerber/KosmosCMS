<?php

 /*
 =====================================================
 Name ........: Passwort aendern
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: password.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 01.07.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Eigenes Administrationspasswort aendern.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
///////////////////////////////////////////////////////

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');

$pw_old = $form->addElement('password', 'pw_old', 'Altes Passwort', NULL, true);
$pw_new = $form->addElement('password', 'pw_new', 'Neues Passwort', NULL, true);
$pw_new2 = $form->addElement('password', 'pw_new2', 'Neues Passwort wiederholen', NULL, true);
$submit = $form->addElement('submit', 'btn', NULL, 'Ändern');

/* Auswertung */
if ($form->checkSubmit() && $form->checkForm()) {
	/* Alle Felder ausgefuellt */
	if ($pw_new->getValue() == $pw_new2->getValue()) {
		/* Altes Passwort pruefen */
		$result = mysql_query("SELECT password FROM ".DB_TABLE_ROOT."cms_admin
				WHERE admin_id=".StdSqlSafety($_SESSION['admin_id']), DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			if ($line['password'] == sha1($pw_old->getValue())) {
				/* Passwort aendern */
				echo "<h1 class=\"first\">Passwortändern</h1>";
				if (mysql_query("UPDATE ".DB_TABLE_ROOT."cms_admin SET password='".sha1($pw_new->getValue())."'
						WHERE admin_id=".StdSqlSafety($_SESSION['admin_id']), DB_CMS)) {
					/* Passwort aktualisieren */
					$_SESSION['admin_password'] = sha1($pw_new->getValue());
					echo ActionReport(REPORT_OK, "Passwort geändert",
							"Ihr Passwort wurde erfolgreich geändert!");
				}
				else {
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Das Passwort konnte nicht geändert werden!<br />".mysql_error(DB_CMS));
				}
			}
			else {
				/* Falsches Passwort */
				SessionDelete();
				die(LoginFormular(1));
			}
		}
		else {
			/* Admin nicht gefunden -> Notbremse */
			SessionDelete();
			die(LoginFormular(0));
		}
	}
	else {
		/* Passwoerter stimmen nicht ueberein */
		echo "<h1 class=\"first\">Passwort ändern</h1>";
		echo ActionReport(REPORT_EINGABE, "Passwörter stimmen nicht", "Die neuen Passwörter stimmen nicht überein!");
		echo $form->getForm();
	}
}
else {
	/* Ausgabe Formular */
	echo "<h1 class=\"first\">Passwort ändern</h1>";
	echo $form->getForm();
}

?>