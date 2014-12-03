<?php

 /*
 =====================================================
 Name ........: Gesetzesartikel Kategorien
 Projekt .....: Linkverzeichnis
 Datiename ...: edit.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 13.08.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :

 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_LAWDB_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_LAWDB, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 20;
///////////////////////////////////////////////////////

/* Ueberschrift */
if (isset($_GET['id']))
	echo '<h1 class="first">Quelle bearbeiten</h1>';
else
	echo '<h1 class="first">Neue Quelle</h1>';

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$name = $form->addElement('text', 'name', 'Unternehmensbereiche', NULL, true);
$url = $form->addElement('text', 'url', 'Link', NULL, true);
$url->setCustomValidation('website', '');
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && isset($_GET['id'])) {
	$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_source
			WHERE id='.StdSqlSafety($_GET['id']), DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		$name->setValue($line['name']);
		$url->setValue($line['url']);
	}
	else {
		$form_hide = true;
		echo ActionReport(REPORT_ERROR, 'Quelle existiert nicht',
				'Die gewünschte Quelle wurde in der Datenbank nicht gefunden!');
	}
}

/* Formular Auswertung */
if ($form->checkForm()) {
	/* Keine doppelten Eintraege erlaubt */
	if (isset($_GET['id'])) {
		$result = mysql_query('SELECT id FROM '.DB_TABLE_PLUGIN.'lawdb_source
				WHERE name="'.StdSqlSafety($name->getValue()).'" && id!='.StdSqlSafety($_GET['id']), DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	else {
		$result = mysql_query('SELECT id FROM '.DB_TABLE_PLUGIN.'lawdb_source
				WHERE name="'.StdSqlSafety($name->getValue()).'"', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	if (mysql_num_rows($result)) {
		echo ActionReport(REPORT_EINGABE, 'Quelle existiert bereits',
				'Diesee Quelle existiert bereits in der Datenbank.');
	}
	else {
		/* Aenderung abspeichern */
		if (isset($_GET['id'])) {
			if (mysql_query('UPDATE '.DB_TABLE_PLUGIN.'lawdb_source SET
					name="'.StdSqlSafety($name->getValue()).'",
					url="'.StdSqlSafety($url->getValue()).'"
					WHERE id='.(int)$_GET['id'], DB_CMS)) {
				echo ActionReport(REPORT_OK, 'Änderung gespeichert',
						'Die Änderungen wurden erfolgreich übernommen.');
			}
			else {
				echo ActionReport(REPORT_EINGABE, 'Fehler beim abspeichern',
						'Die Quelle konnte nicht gespeichert werden.
						<br />MySQL Fehler: '.mysql_error());
			}
		}
		else {
			if (mysql_query('INSERT INTO '.DB_TABLE_PLUGIN.'lawdb_source(name, url)
					VALUE("'.StdSqlSafety($name->getValue()).'", "'.StdSqlSafety($url->getValue()).'")',
					DB_CMS)) {
				echo ActionReport(REPORT_OK, 'Quelle gespeichert',
						'Die neue Quelle wurden erfolgreich erstellt.');
			}
			else {
				echo ActionReport(REPORT_EINGABE, 'Fehler beim abspeichern',
						'Die Quelle konnte nicht gespeichert werden.
						<br />MySQL Fehler: '.mysql_error());
			}
		}
	}
}
else {
	if (!isset($form_hide)) {
		/* Formular ausgeben */
		echo $form->getForm();
	}
}