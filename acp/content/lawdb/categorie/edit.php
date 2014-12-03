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
	echo '<h1 class="first">Kategorie bearbeiten</h1>';
else
	echo '<h1 class="first">Neue Kategorie</h1>';

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$name = $form->addElement('text', 'name', 'Kategorienamen', NULL, true);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && isset($_GET['id'])) {
	$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie
			WHERE id='.StdSqlSafety($_GET['id']), DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		$name->setValue($line['name']);
	}
	else {
		$form_hide = true;
		echo ActionReport(REPORT_ERROR, 'Kategorie existiert nicht',
				'Die gewünschte Kategorie wurde in der Datenbank nicht gefunden!');
	}
}

/* Formular Auswertung */
if ($form->checkForm()) {
	/* Keine doppelten Eintraege erlaubt */
	if (isset($_GET['id'])) {
		$result = mysql_query('SELECT id FROM '.DB_TABLE_PLUGIN.'lawdb_categorie 
				WHERE name="'.StdSqlSafety($name->getValue()).'" && id!='.StdSqlSafety($_GET['id']), DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	else {
		$result = mysql_query('SELECT id FROM '.DB_TABLE_PLUGIN.'lawdb_categorie 
				WHERE name="'.StdSqlSafety($name->getValue()).'"', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	if (mysql_num_rows($result)) {
		echo ActionReport(REPORT_EINGABE, 'Kategorienamen existiert bereits',
				'Dieser Kategorienamen existiert bereits in der Datenbank.');
	}
	else {
		/* Aenderung abspeichern */
		if (isset($_GET['id'])) {
			if (mysql_query('UPDATE '.DB_TABLE_PLUGIN.'lawdb_categorie SET
					name="'.StdSqlSafety($name->getValue()).'"
					WHERE id='.(int)$_GET['id'], DB_CMS)) {
				echo ActionReport(REPORT_OK, 'Änderung gespeichert',
						'Die Änderungen wurden erfolgreich übernommen.');
			}
			else {
				echo ActionReport(REPORT_EINGABE, 'Fehler beim abspeichern',
						'Kategorie konnte nicht gespeichert werden.
						<br />MySQL Fehler: '.mysql_error());
			}
		}
		else {
			if (mysql_query('INSERT INTO '.DB_TABLE_PLUGIN.'lawdb_categorie(name)
					VALUE("'.StdSqlSafety($name->getValue()).'")', DB_CMS)) {
				if (mysql_insert_id(DB_CMS) > 31) {
					/* Maximal erreicht */
					mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'lawdb_categorie
							WHERE id='.mysql_insert_id(DB_CMS), DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);
					echo ActionReport(REPORT_ERROR, 'Erstellen  nicht möglich',
							'Die maximal Anzahl unterstützten Kategorien ist erreicht und es können
							keine neuen Kategorien mehr erstellt werden. Nehmen Sie bitte Konatkt mit
							dem CMS Support auf.');
				}
				else {
					echo ActionReport(REPORT_OK, 'Kategorie gespeichert',
							'Die neue Kategorie wurden erfolgreich erstellt.');
				}
			}
			else {
				echo ActionReport(REPORT_EINGABE, 'Fehler beim abspeichern',
						'Kategorie konnte nicht gespeichert werden.
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