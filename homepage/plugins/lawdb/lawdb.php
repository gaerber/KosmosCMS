<?php

 /*
 =====================================================
 Name ........: Excel Gesetzesartikel
 Projekt .....: Linkverzeichnis
 Datiename ...: lawdb.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Autor .......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 16.07.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Konfiguration fuer das Erstellen der Excel-Datei.

 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined('SWISS_WEBDESIGN'))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_LAWDB_EN) {

	/* Formular zum erstellen */
	$form = new formWizard('form', '', 'post', 'form_standard');
	/* Liste mit allen Kategorien */
	$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie ORDER BY name ASC', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$i = 0;
	if (!$_SESSION['user_id']) {
		/* Demonstrationsmodus */
		$element = $form->addElement('checkbox', 'categorie', 'Kategorien', 0);
		$element->setSubLabel('Beispiel');
		$element->setChecked(true);
		$i++;
	}
	while ($row = mysql_fetch_array($result)) {
		if ($i > 0)
			$element = $form->addElement('checkbox', 'categorie', NULL, $row['id']);
		else
			$element = $form->addElement('checkbox', 'categorie', 'Kategorien', $row['id']);
		$element->setSubLabel($row['name']);
		if (!$_SESSION['user_id'])
			$element->setReadonly(true);
		$i++;
	}
	/* Button */
	$submit = $form->addElement('submit', 'btn', NULL, 'Generieren');

	/* Auswertung */
	if ($form->checkForm() && $element->getRequest()) {
		/* Array zum Generieren  vorbereiten */
		$mysql_source = array();
		$mysql_categorie = array();
		$bitfield_categorie = 0x00;
		$mysql_office = array();
		
		/* Kategorien */
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
			$categories = implode(',', $element->getRequest());
			$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie
					WHERE id IN ('.StdSqlSafety($categories).')
					ORDER BY name ASC', DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			while ($row = mysql_fetch_array($result)) {
				$mysql_categorie[] = array(1<<$row['id'], $row['name']);
				$bitfield_categorie |= 1<<$row['id'];
			}
		}
		else {
			/* Demonstrationsmodus */
			$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie
					ORDER BY name ASC', DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			while ($row = mysql_fetch_array($result)) {
				$mysql_categorie[] = array(1<<$row['id'], $row['name']);
			}
			$bitfield_categorie = 0x01;
		}
		
		/* Quellen */
		$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_source
				ORDER BY name ASC', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$mysql_source[$row['id']] = array($row['name'], $row['url']);
		}
		
		/* Unternehmensbereiche */
		//$result = mysql_query('SELECT office FROM '.DB_TABLE_PLUGIN.'lawdb_list
		//		WHERE categorie & '.$bitfield_categorie, DB_CMS)
		//		OR FatalError(FATAL_ERROR_MYSQL);
		$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_office
				ORDER BY name ASC', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$mysql_office[] = array(1<<$row['id'], $row['name']);
		}
		
		/* Liste erstellen */
		$mysql_result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_list
				WHERE categorie & '.$bitfield_categorie.' ORDER BY abbr ASC', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		
		/* Excel generieren */
		include('generate.php');
		
		/* Direkte Ausgabe an den Browser */
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Gesetze und Verordnungen '
				.date('Y-m-d', TIME_STAMP).'.xlsx"');
		header('Cache-Control: max-age=0');
		readfile('/home/httpd/vhosts/swiss-webdesign.ch/subdomains/miso/httpdocs/excel/'.$outputfile);
		/* Datei wieder loeschen */
		unlink('/home/httpd/vhosts/swiss-webdesign.ch/subdomains/miso/httpdocs/excel/'.$outputfile);
		die();
		
	}
	else {
		/* Ausgabe Formular */
		echo $form->getForm();
		
		/* Stand der Datenbank */
		$result = mysql_query('SELECT timestamp FROM '.DB_TABLE_PLUGIN.'lawdb_list
				ORDER BY timestamp DESC LIMIT 1', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			echo '<p>Letzte Datenbankaktualisierung: '.printDate($line['timestamp']).' '
			.date('H:i', $line['timestamp']);
		}
	}

}

?>