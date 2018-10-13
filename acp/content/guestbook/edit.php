<?php

 /*
 =====================================================
 Name ........: Plugin: Gaestebuch
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: edit.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 15.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Gaestebucheintraege bearbeiten.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_GUESTBOOK_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_GUESTBOOK, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 4;
///////////////////////////////////////////////////////

$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	echo "<h1 class=\"first\">Gästebucheintrag bearbeiten</h1>";

	/* Daten holen */
	$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."guestbook
			WHERE id=".StdSqlSafety($_GET['id']))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		/* Registrierte Benutzer */
		if ($line['writer_id']) {
			$res = Database::instance()->query("SELECT user_name, user_email, user_website
					FROM ".DB_TABLE_ROOT."cms_access_user
					WHERE user_id=".$line['writer_id'])
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line_user = $res->fetch_assoc()) {
				/* Kontaktdaten des registrierten Benutzers */
				if (ACP_AdminAccess(ACP_ACCESS_USER))
					$user_edit = "<a href=\"?page=user-edit&id=".$line['writer_id']."\" onmouseover=\"Tip('Benutzer bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/guestbook/user_edit.png\" alt=\"\" /></a>";
				else
					$user_edit = "";
				$registred_user = printBoxStart();
				$registred_user .= printBox("Registrierter Benutzer",
						$line_user['user_name']."<br />".$line_user['user_email'],
						$user_edit,	$line_user['user_website']);
				$registred_user .= printBoxEnd();
			}
			/* ELSE: Benutzer existiert nicht mehr */
		}
		/* ELSE: Kein registrierter Benutzer */

		/* Formular */
		if (!isset($registred_user)) {
			$name = $form->addElement('text', 'name', 'Name', NULL, true);
			$email = $form->addElement('text', 'email', 'Email Adresse');
			$website = $form->addElement('text', 'website', 'Website');
		}
		$comment = $form->addElement('textarea', 'comment', 'Nachricht', NULL, true);
		$comment->setRowsCols(7,20);
		$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

		/* Defaultwerte Setzen */
		if (!$form->checkSubmit()) {
			if (!isset($registred_user)) {
				$name->setValue($line['writer_name']);
				$email->setValue($line['writer_email']);
				$website->setValue($line['writer_website']);
			}
			$comment->setValue(StdContentEdit($line['comment']));
		}

		/* Formular pruefen */
		if ($form->checkForm()) {
			/* Aenderung abspeichern */
			if (!isset($registred_user)) {
				$sql = "writer_name='".StdSqlSafety($name->getValue())."',
						writer_email='".StdSqlSafety($email->getValue())."',
						writer_website='".StdSqlSafety($website->getValue())."', ";
			}
			else {
				$sql = "";
			}
			if (Database::instance()->query("UPDATE ".DB_TABLE_PLUGIN."guestbook SET ".$sql."
					comment='".StdSqlSafety(StdContent($comment->getValue(),false))."'
					WHERE id=".StdSqlSafety($_GET['id'])))
				echo ActionReport(REPORT_OK, "Änderung übernommen",
						"Die Änderung wurde erfolgreich übernommen!");
			else
				echo ActionReport(REPORT_ERROR, "Fehler", "Es trat ein Fehler beim Abspeichern auf!
						<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
		}
		else {
			/* Ausgabe Formular */
			if (isset($registred_user))
				echo $registred_user;
			echo $form->getForm();
		}
	}
	else {
		/* Eintrag existiert nicht in Datenbank */
		echo ActionReport(REPORT_EINGABE, "Eintrag existiert nicht",
				"Dieser Eintrag existiert in der Datenbank nicht!");
	}
}
else {
	/* Eingabefehler */
	echo ActionReport(REPORT_EINGABE, "Eingabefehler",
			"Es wurde kein Eintrag ausgewählt!");
}

?>
