<?php

 /*
 =====================================================
 Name ........: Gesetzesartikel erstellen/bearbeiten
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
 |1.0     | 08.07.2012 | Programm erstellt
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
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_lawdb\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_lawdb\"";
///////////////////////////////////////////////////////

/* Ueberschrift */
if (isset($_GET['id']))
	echo "<h1 class=\"first\">Gesetzesartikel bearbeiten</h1>";
else
	echo "<h1 class=\"first\">Gesetzesartikel erstellen</h1>";

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$abbr = $form->addElement('text', 'abbr', 'Abkürzung', NULL, true);
$caption = $form->addElement('text', 'caption', 'Titel', NULL, true);
$url = $form->addElement('text', 'url', 'Link', NULL, true);
$url->setCustomValidation('website', '');
$source = $form->addElement('select', 'source', 'Quelle', NULL, true);
$date = $form->addElement('text', 'date', 'Ausgabedatum');
$date->setCustomValidation( '%^(0?[1-9]|[12][0-9]|3[01])[.](0?[1-9]|1[012])[.](19|20)?[\d]{2}$%',
		ActionReport(REPORT_EINGABE, 'Datumsformat', 'Das Format des Ausgabedatums muss DD.MM.YYYY sein.'));

$content = $form->addElement('textarea', 'content', 'Inhalt');
$content->setRowsCols(4, 70);
$commitment = $form->addElement('textarea', 'commitment', 'Verpflichtungen');
$commitment->setRowsCols(2, 70);
$amendment = $form->addElement('textarea', 'amendment', 'Änderungen');
$amendment->setRowsCols(2, 70);
$hint = $form->addElement('checkbox', 'hint', 'Hinweis', 1);

$categorie = $form->addElement('select', 'categorie', 'Kategorien', NULL, true);
$categorie->setMultiple(true);
$categorie->setSize(4);

$office = $form->addElement('select', 'office', 'Unternehmensbereiche');
$office->setMultiple(true);
$office->setSize(4);

$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

$form_hide = false;

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && isset($_GET['id'])) {
	$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_list
			WHERE id='.StdSqlSafety($_GET['id']), DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		$abbr->setValue($line['abbr']);
		$caption->setValue($line['caption']);
		$url->setValue($line['url']);
		$source->setValue($line['source']);
		$date->setValue(date('d.m.Y', $line['date']));
		$content->setValue($line['content']);
		$commitment->setValue($line['commitment']);
		$amendment->setValue($line['amendment']);
		if ($line['hint']) {
			$hint->setChecked(true);
		}
	}
	else {
		$form_hide = true;
		echo ActionReport(REPORT_ERROR, 'Artikel existiert nicht',
				'Der gewünschte Gesetzesartikel wurde in der Datenbank nicht gefunden!');
	}
}

/* Aenderung hervorheben */

/* Quellen */
$source->addOption('Bitte wählen', 0);
$res = mysql_query('SELECT id, name FROM '.DB_TABLE_PLUGIN.'lawdb_source
			ORDER BY name ASC', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($res)) {
	$source->addOption($row['name'], $row['id'],
			(bool) (!$form->checkSubmit() && isset($_GET['id'])
					&& ($line['source'] == $row['id'])));
}

/* Kategorien */
$categorie->addOption('Öffentlich', 0,
		(bool) (!$form->checkSubmit() && isset($_GET['id'])
				&& ($line['categorie'] & 0x01)));
$res = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie
			ORDER BY name ASC', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($res)) {
	$categorie->addOption($row['name'], $row['id'],
			(bool) (!$form->checkSubmit() && isset($_GET['id'])
					&& ($line['categorie'] & (1<<$row['id']))));
}
/* Unternehmensbereiche */
$res = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_office
			ORDER BY name ASC', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($res)) {
	$office->addOption($row['name'], $row['id'],
			(bool) (!$form->checkSubmit() && isset($_GET['id'])
					&& ($line['office'] & (1<<$row['id']))));
}

/* Formular Auswertung */
if ($form->checkForm()) {
	/* Kategorien vorbereiten */
	if (is_array($categorie->getValue())) {
		$categorie_bitflag = 0;
		foreach ($categorie->getValue() as $e) {
			$categorie_bitflag |= (1<<$e);
		}
	}
	else {
		$categorie_bitflag = $categorie->getValue();
	}
	
	/* Unternehmensbereiche vorbereiten */
	if (is_array($office->getValue())) {
		$office_bitflag = 0;
		foreach ($office->getValue() as $e) {
			$office_bitflag |= (1<<$e);
		}
	}
	else {
		$office_bitflag = $office->getValue();
	}
	
	/* Ausgabestand umrechnen */
	$date_int = explode('.', $date->getValue());
	if (sizeof($date_int) == 3)
		$date_int = mktime(12, 0, 0, $date_int[1], $date_int[0], $date_int[2]);
	else
		$date_int = NULL;
	
	/* Aenderung abspeichern */
	if (isset($_GET['id'])) {
		if (mysql_query('UPDATE '.DB_TABLE_PLUGIN.'lawdb_list SET
				abbr="'.StdSqlSafety($abbr->getValue()).'",
				caption="'.StdSqlSafety($caption->getValue()).'",
				url="'.StdSqlSafety($url->getValue()).'",
				source='.(int) $source->getValue().',
				date='.(int) $date_int.',
				categorie='.(int)$categorie_bitflag.',
				office='.(int)$office_bitflag.',
				content="'.StdSqlSafety($content->getValue()).'",
				commitment="'.StdSqlSafety($commitment->getValue()).'",
				amendment="'.StdSqlSafety($amendment->getValue()).'",
				hint='.(int) $hint->getValue().',
				writer='.$_SESSION['admin_id'].', timestamp='.TIME_STAMP.'
				WHERE id='.(int)$_GET['id'], DB_CMS)) {
			echo ActionReport(REPORT_OK, 'Änderung gespeichert',
					'Die Änderungen wurden erfolgreich übernommen.');
		}
		else {
			echo ActionReport(REPORT_EINGABE, 'Fehler beim abspeichern',
					'Gesetzesartikel konnte nicht gespeichert werden.
					<br />MySQL Fehler: '.mysql_error());
		}
	}
	else {
		if (mysql_query('INSERT INTO '.DB_TABLE_PLUGIN.'lawdb_list(abbr, caption, url, source, date,
				categorie, office, content, commitment, amendment, hint, writer, timestamp)
				VALUE("'.StdSqlSafety($abbr->getValue()).'",
				"'.StdSqlSafety($caption->getValue()).'",
				"'.StdSqlSafety($url->getValue()).'",
				'.(int) $source->getValue().',
				'.(int)$date_int.',
				'.(int)$categorie_bitflag.',
				'.(int)$office_bitflag.',
				"'.StdSqlSafety($content->getValue()).'",
				"'.StdSqlSafety($commitment->getValue()).'",
				"'.StdSqlSafety($amendment->getValue()).'",
				'.(int) $hint->getValue().',
				'.$_SESSION['admin_id'].', '.TIME_STAMP.')', DB_CMS)) {
			echo ActionReport(REPORT_OK, 'Gesetzesartikel gespeichert',
					'Der neue Gesetzesartikel wurden erfolgreich erstellt.');
		}
		else {
			echo ActionReport(REPORT_EINGABE, 'Fehler beim abspeichern',
					'Gesetzesartikel konnte nicht gespeichert werden.
					<br />MySQL Fehler: '.mysql_error());
		}
	}
}
else {
	if ($form_hide == false) {
		/* Formular ausgeben */
		echo $form->getForm();
	}
}

?>