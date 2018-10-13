<?php

 /*
 =====================================================
 Name ........: Plugin: Gaestebuch Kommentieren
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: comment.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 16.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Administartoren können Gaestebucheintraege
 kommentieren.

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

echo "<h1 class=\"first\">Gästebucheintrag kommentieren</h1>";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	/* Formular vorbereiten */
	$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
	$comment = $form->addElement('textarea', 'comment', 'Kommentar');
	$comment->setRowsCols(7,20);
	$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

	/* Eintrag selektieren */
	$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."guestbook
			WHERE id=".StdSqlSafety($_GET['id']))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		/* Daten verarbeiten */
		if ($line['writer_id']) {
			$res_user = Database::instance()->query("SELECT user_name, user_email, user_website
					FROM ".DB_TABLE_ROOT."cms_access_user
					WHERE user_id=".$line['writer_id'])
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line_user = $res_user->fetch_assoc()) {
				/* Daten ueberschreiben */
				$line['writer_name'] = $line_user['user_name'];
				$line['writer_email'] = $line_user['user_email'];
				$line['writer_website'] = $line_user['user_website'];
			}
		}

		/* Benutzerinformationen */
		$user_infos = array();
		if ($line['writer_email'])
			$user_infos[] = $line['writer_email'];
		if ($line['writer_website'])
			$user_infos[] = $line['writer_website'];
		$user_infos[] = printDate($line['timestamp'])." ".date(FORMAT_TIME, $line['timestamp']);

		/* Defaultwerte Setzen */
		if (!$form->checkSubmit()) {
			$comment->setValue(StdContentEdit($line['admin_comment']));
		}

		/* Formularauswertung */
		if ($form->checkForm()) {
			/* Aenderung abspeichern */
			if (Database::instance()->query("UPDATE ".DB_TABLE_PLUGIN."guestbook SET
					admin_comment='".StdSqlSafety(StdContent($comment->getValue(),false))."',
					admin_id=".$_SESSION['admin_id']."
					WHERE id=".StdSqlSafety($_GET['id'])))
				echo ActionReport(REPORT_OK, "Änderung übernommen",
						"Die Änderung wurde erfolgreich übernommen!");
			else
				echo ActionReport(REPORT_ERROR, "Fehler", "Es trat ein Fehler beim Abspeichern auf!
						<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
		}
		else {
			/* Ausgabe */
			echo printBoxStart();
			echo printBox($line['writer_name'], $line['comment'],
					"<a href=\"?page=guestbook-edit&amp;id=".$line['id']."\" onmouseover=\"Tip('Eintrag bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/guestbook/edit.png\" alt=\"\" /></a>
					<a href=\"javascript:confirmDeletion('?page=guestbook-guestbook&amp;delete=".$line['id']."', 'Wollen Sie diesen Eintrag wirklich löschen?')\" onmouseover=\"Tip('Eintrag löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/guestbook/delete.png\" alt=\"\" /></a>",
					$user_infos);
			echo printBoxEnd();

			echo $form->getForm();
		}
	}
	else {
		/* Eintrag nicht gefunden */
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
