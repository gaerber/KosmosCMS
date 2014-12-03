<?php

 /*
 =====================================================
 Name ........: Plugin: Neuigkeiten Kategorien
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
 |1.0     | 18.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Kategorien der Neuigkeiten bearbeiten/
 umbenennen.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_NEWS_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_NEWS_CAT, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 2;
///////////////////////////////////////////////////////

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$name = $form->addElement('text', 'name', 'Namen', NULL, true);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$categorie_id = (int) $_GET['id'];
	echo "<h1 class=\"first\">Kategorie bearbeiten</h1>";
}
else {
	$categorie_id = 0;
	echo "<h1 class=\"first\">Neue Kategorie</h1>";
}

/* Kategorie ID pruefen */
if ($categorie_id) {
	$result = mysql_query("SELECT name FROM ".DB_TABLE_PLUGIN."news_categorie
			WHERE id=".$categorie_id, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		/* Defaultwerte Setzen */
		if (!$form->checkSubmit())
			$name->setValue($line['name']);
	}
	else {
		/* Kategorie existiert nicht */
		echo ActionReport(REPORT_EINGABE, "Kategorie existiert nicht",
				"Diese Kategorie existiert in der Datenbank nicht!");
		$dont_show_form = true;
	}
}

/* Auswertung */
if (!isset($dont_show_form)) {
	/* Formular pruefen */
	if ($form->checkForm()) {
		/* Pruefen dass Kategorie noch nicht existiert */
		$validate_id_str = ValidateFileSystem($name->getValue());
		$result = mysql_query("SELECT id FROM ".DB_TABLE_PLUGIN."news_categorie
				WHERE (id_str='".$validate_id_str."' || name='".StdSqlSafety($name->getValue())."')
				 && id!=".$categorie_id, DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		if (mysql_num_rows($result) == 0) {
			/* Speichern */
			if ($categorie_id) {
				if (mysql_query("UPDATE ".DB_TABLE_PLUGIN."news_categorie 
						SET id_str='".$validate_id_str."', name='".StdSqlSafety($name->getValue())."'
						WHERE id=".$categorie_id, DB_CMS))
					echo ActionReport(REPORT_OK, "Kategorie gespeichert",
							"Die Änderungen wurden erfolgreich übernommen.");
				else
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Die Änderungen konnten nicht übernommen werden.
							<br />MySQL Fehler: ".mysql_error(DB_CMS));
			}
			else {
				if (mysql_query("INSERT INTO ".DB_TABLE_PLUGIN."news_categorie(id_str, name)
						VALUE('".$validate_id_str."', '".StdSqlSafety($name->getValue())."')", DB_CMS))
					echo ActionReport(REPORT_OK, "Kategorie erstellt",
							"Die Kategorie wurden erfolgreich erstellt.");
				else
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Die Kategorie konnten nicht erstellt werden.
							<br />MySQL Fehler: ".mysql_error(DB_CMS));
			}
		}
		else {
			/* Kategorienamen existiert bereits */
			$name->setError(true);
			echo ActionReport(REPORT_EINGABE, "Kategorienamen existiert bereits",
					"Der angegebene Kategorienamen existiert bereits!");
			echo $form->getForm();
		}
	}
	else {
		/* Ausgabe Formular */
		echo $form->getForm();
	}
}

?>