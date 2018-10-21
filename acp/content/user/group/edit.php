<?php

 /*
 =====================================================
 Name ........: Gruppen Verwalten
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
 |1.0     | 06.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Erstellt eine Liste aller Gruppen der Benutzer.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_USER_SYSTEM_EN)		die();
ACP_AdminAccess(ACP_ACCESS_USER, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_user\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_user\"";
///////////////////////////////////////////////////////

/* Titel */
if (isset($_GET['id']))
	echo "<h1 class=\"first\">Gruppe bearbeiten</h1>";
else
	echo "<h1 class=\"first\">Neue Gruppe</h1>";

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$name = $form->addElement('text', 'name', 'Name', NULL, true);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && isset($_GET['id'])) {
	/* Daten lesen */
	$result = Database::instance()->query("SELECT name FROM ".DB_TABLE_ROOT."cms_access_group
			WHERE id=".StdSqlSafety($_GET['id']))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		$name->setValue($line['name']);
	}
	else {
		/* Gruppe existiert nicht */
		echo ActionReport(REPORT_EINGABE, "Gruppe existiert nicht", "Diese Gruppe existiert nicht!");
		$dont_show_form = true;
	}
}

/* Auswertung */
if (!isset($dont_show_form)) {
	if ($form->checkSubmit() && $form->checkForm()) {
		/* ID Namen pruefen */
		$validate_id_str = getIdStr($name->getValue(), "NOCHECK");

		if (isset($_GET['id'])) {
			$result = Database::instance()->query("SELECT id FROM ".DB_TABLE_ROOT."cms_access_group
					WHERE (id_str='".$validate_id_str."' || name='".StdSqlSafety($name->getValue())."')
						&& id!=".StdSqlSafety($_GET['id']))
					OR FatalError(FATAL_ERROR_MYSQL);
		}
		else {
			$result = Database::instance()->query("SELECT id FROM ".DB_TABLE_ROOT."cms_access_group
					WHERE id_str='".$validate_id_str."' || name='".StdSqlSafety($name->getValue())."'")
					OR FatalError(FATAL_ERROR_MYSQL);
		}
		if ($result->num_rows == 0) {
			/* Speichern */
			if (isset($_GET['id'])) {
				if (Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_access_group
						SET id_str='".$validate_id_str."', name='".StdSqlSafety($name->getValue())."'
						WHERE id=".StdSqlSafety($_GET['id'])))
					echo ActionReport(REPORT_OK, "Gruppe gespeichert",
							"Die Änderungen wurden erfolgreich übernommen.");
				else
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Die Änderungen konnten nicht übernommen werden.
							<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
			}
			else {
				if (Database::instance()->query("INSERT INTO ".DB_TABLE_ROOT."cms_access_group(id_str, name)
						VALUE('".$validate_id_str."', '".StdSqlSafety($name->getValue())."')"))
					echo ActionReport(REPORT_OK, "Gruppe erstellt",
							"Die Gruppe wurden erfolgreich erstellt.");
				else
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Die Gruppe konnten nicht erstellt werden.
							<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
			}
		}
		else {
			/* Gruppennamen existiert bereits */
			$name->setError(true);
			echo ActionReport(REPORT_EINGABE, "Gruppennamen existiert bereits",
					"Der angegebene Gruppennamen existiert bereits!");
			echo $form->getForm();
		}
	}
	else {
		/* Formularausgabe */
		echo $form->getForm();
	}
}

?>
