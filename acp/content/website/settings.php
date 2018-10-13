<?php

 /*
 =====================================================
 Name ........: Einstellungen der Website
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: settings.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 01.07.2011 | Programm erstellt.
 |1.0.1   | 05.07.2011 | Ueberarbeitung Sicherheit
 |1.0.2   | 28.09.2011 | Newsletter Einstellungen
 -----------------------------------------------------
 Beschreibung :
 Globale Einstellungen der Website.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_ADMIN, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_setting\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_setting\"";
///////////////////////////////////////////////////////

/* Titel */
echo "<h1 class=\"first\">Einstellungen</h1>";

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$company = $form->addElement('text', 'company', 'Firmenname', NULL, true);
$header = $form->addElement('text', 'header', 'Schlagwort', NULL, true);
$description = $form->addElement('textarea', 'description', 'Beschreibung');
$description->setRowsCols(3,20);
$admin_email = $form->addElement('text', 'admin_email', 'Emailadresse', NULL, true);
$admin_email->setCustomValidation('email', NULL);
if (ACP_MODULE_NEWSLETTER_EN) {
	$newsletter_sender = $form->addElement('text', 'newsletter_sender', 'Newsletter Absender', NULL, true);
	$newsletter_email = $form->addElement('text', 'newsletter_email', 'Newsletter Email', NULL, true);
	$newsletter_email->setCustomValidation('email', NULL);
}
$online = $form->addElement('checkbox', 'online', 'Website online', '1');
$offlinemessage = $form->addElement('textarea', 'offlinemessage', 'Offlinenachricht');
$offlinemessage->setRowsCols(3,20);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');


/* Auswertung */
if ($form->checkSubmit() && $form->checkForm()) {
	/* Offlinenachricht */
	if (!$online->getValue() && $offlinemessage->getValue() == "") {
		echo ActionReport(REPORT_EINGABE, "Offline Nachricht",
				"Es muss eine Offlinenachricht eingegeben werden, wenn die Website offline ist!");
		$offlinemessage->setError(true);
		/* Ausgabe Formular */
		echo $form->getForm();
	}
	else {
		/* Spezialfaelle vorbereiten */
		if (ACP_MODULE_NEWSLETTER_EN) {
			$sql_col = "newsletter_sender, newsletter_email, ";
			$sql_data = "'".StdSqlSafety($newsletter_sender->getValue())."',
					'".StdSqlSafety($newsletter_email->getValue())."',";
		}
		else {
			$sql_col = "";
			$sql_data = "";
		}
		/* Alles abspeichern */
		if (Database::instance()->query("INSERT INTO ".DB_TABLE_ROOT."cms_setting
				(".$sql_col." company, header, description, admin_email, online, offlinemessage)
				VALUES(".$sql_data." '".StdSqlSafety($company->getValue())."',
				'".StdSqlSafety($header->getValue())."',
				'".StdSqlSafety($description->getValue())."',
				'".StdSqlSafety($admin_email->getValue())."',
				".$online->getValue().",
				'".StdSqlSafety($offlinemessage->getValue())."')")) {
			echo ActionReport(REPORT_OK, "Einstellungen geändert",
					"Die Einstellungen wurden erfolgreich geändert!");
		}
		else {
			echo ActionReport(REPORT_ERROR, "Fehler",
					"Die Einstellungen konnten nicht geändert werden!<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
		}
	}
}
else {
	if (!$form->checkSubmit()) {
		/* Daten aus Datenbank holen */
		$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_ROOT."cms_setting
				ORDER BY id DESC LIMIT 1")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			/* Zeile gefunden */
			$company->setValue($line['company']);
			$header->setValue($line['header']);
			$description->setValue($line['description']);
			$admin_email->setValue($line['admin_email']);
			if (ACP_MODULE_NEWSLETTER_EN) {
				$newsletter_sender->setValue($line['newsletter_sender']);
				$newsletter_email->setValue($line['newsletter_email']);
			}
			if ($line['online'])
				$online->setChecked(true);
			$offlinemessage->setValue($line['offlinemessage']);
		}
		/* ELSE: Leeres Formular anzeigen */
	}

	/* Ausgabe Formular */
	echo $form->getForm();
}

?>