<?php

 /*
 =====================================================
 Name ........: Plugin: Neuigkeiten bearbeiten
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: guestbook.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 18.09.2011 | Programm erstellt.
 |1.0.1   | 30.03.2013 | Gruppenwahl verstecken.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Bearbeiten und schreiben von Neuigkeiten.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_NEWS_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_NEWS | ACP_ACCESS_M_NEWS_COM, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 2;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_news\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_news\"";
///////////////////////////////////////////////////////

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	echo "<h1 class=\"first\">Neuigkeiten bearbeiten</h1>";
	$news_id = (int) $_GET['id'];
}
else {
	echo "<h1 class=\"first\">Neuigkeiten schreiben</h1>";
	$news_id = 0;
}


/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');

$news_categorie = $form->addElement('select', 'news_group', 'Kategorie', NULL, true);
$news_categorie->addOption("Bitte wählen", 0);

$caption = $form->addElement('text', 'caption', 'Titel', null, true);

$news_short = $form->addElement('textarea', 'news_short', 'Kurztext', NULL, true);
$news_short->setRowsCols(4, 20);

$news_long = $form->addElement('textarea', 'news_long', 'Neuigkeit');
$news_long->setWysiwyg(true);
$news_long->setRowsCols(20, 80);
$news_long->setValue("<p>&nbsp;</p>");

if (ACP_ACCESS_SYSTEM_EN) {
	$access_all = $form->addElement('radio', 'access', 'Berechtigung', '0');
	$access_log = $form->addElement('radio', 'access', NULL, '1');
	$access_grp = $form->addElement('radio', 'access', NULL, '2');
	$access_groups = $form->addElement('select', 'access_group', 'Gruppen');
	
	$access_groups->setCssClass('select_groups hide');
	$access_all->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'none\';"');
	$access_log->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'none\';"');
	$access_grp->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'block\';"');

	$access_all->setSubLabel("Alle Besucher");
	$access_log->setSubLabel("Nur angemeldete Besucher");
	$access_grp->setSubLabel("Nur Besucher aus bestimmten Gruppen");

	$access_groups->setMultiple(true);
	$access_groups->setSize(7);
}

$locked = $form->addElement('checkbox', 'locked', 'Sperren', 1);
if ($news_id)
	$new_writer = $form->addElement('checkbox', 'new_writer', 'Author &amp; Datum', 1);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');
$submit->setCssClassField("wymupdate");

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && $news_id) {
	/* Beitrag selektieren */
	$result = mysql_query("SELECT * FROM ".DB_TABLE_PLUGIN."news WHERE id=".$news_id, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		if (ACP_AdminAccess(ACP_ACCESS_M_NEWS_COM)
				|| ($line['writer'] == $_SESSION['admin_id'])) {
			$caption->setValue($line['caption']);
			$news_short->setValue(StdContentEdit($line['news_short']));
			$news_long->setValue($line['news_long']);
			if ($line['locked'])
				$locked->setChecked(true);
			/* Kategorien */
			$res_cat = mysql_query("SELECT id, name FROM ".DB_TABLE_PLUGIN."news_categorie
					ORDER BY name ASC", DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			while ($row = mysql_fetch_array($res_cat)) {
				$news_categorie->addOption($row['name'], $row['id'],
						(bool)($row['id'] == $line['categorie_id']));
			}
			/* Berechtigungen */
			if (ACP_ACCESS_SYSTEM_EN) {
				if ($line['access'] == 0)
					$access_all->setChecked(true);
				else if ($line['access'] == 1)
					$access_log->setChecked(true);
				else {
					$access_grp->setChecked(true);
				}
				/* Gruppen */
				$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
						ORDER BY name ASC", DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
				while ($row = mysql_fetch_array($result)) {
					$access_groups->addOption($row['name'], 1<<$row['id'],
							(bool)($line['access'] & (1<<$row['id'])));
				}
			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, "Unerlaubte Aktion",
					"Sie haben keine Berechtigung diesen Beitrag zu bearbeiten!");
			$abort = true;
		}
	}
	else {
		/* Neuigkeit existiert nicht */
		echo ActionReport(REPORT_EINGABE, "Beitrag existiert nicht",
				"Dieser Beitrag existiert in der Datenbank nicht!");
		$abort = true;
	}
}
else {
	/* Kategorien */
	$res_cat = mysql_query("SELECT id, name FROM ".DB_TABLE_PLUGIN."news_categorie
			ORDER BY name ASC", DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	while ($row = mysql_fetch_array($res_cat)) {
		$news_categorie->addOption($row['name'], $row['id']);
	}
	
	/* Benutzer Gruppen */
	if (ACP_ACCESS_SYSTEM_EN) {
		if (!$form->checkSubmit())
			$access_all->setChecked(true);
		$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
				ORDER BY name ASC", DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$access_groups->addOption($row['name'], 1<<$row['id']);
		}
	}
}

if (!isset($abort)) {
	/* Formular pruefen */
	if ($form->checkForm()) {
		if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue() && !sizeof($access_groups->getValue())) {
			/* Es muss nim. eine Gruppe ausgewaehlt werden */
			$access_groups->setError(true);
			$access_groups->setCssClass('select_groups show');
			/* Ausgabe des Formulars */
			echo $form->getForm();
		}
		else {
	        /* ID Str ermitteln */
			$validate_id_str = getIdStr($caption->getValue(),
					DB_TABLE_PLUGIN."news", "&& id!=".$news_id);
			
			if (ACP_ACCESS_SYSTEM_EN) {
				/* Berechtigungen berechnen */
				if ($access_grp->getValue()) {
					$access = 0;
					foreach ($access_groups->getValue() as $group) {
						$access |= $group;
					}
				}
				else if ($access_log->getValue())
					$access = 1;
				else
					$access = 0;
			}
			else {
				$access = 0;
			}
			
			/* Aenderung abspeichern */
			if ($news_id) {
				$sql = "UPDATE ".DB_TABLE_PLUGIN."news SET id_str='".$validate_id_str."',
						categorie_id=".(int)$news_categorie->getValue().",
						caption='".StdSqlSafety($caption->getValue())."',
						news_short='".StdSqlSafety(StdContent($news_short->getValue(), false))."',
						news_long='".StdSqlSafety(StdWysiwymPrepare($news_long->getValue(), false))."',
						access=".$access.", locked=".(int)$locked->getValue();
				if ($new_writer->getValue())
					$sql .= ", writer=".$_SESSION['admin_id'].", timestamp=".TIME_STAMP;
				$sql .= " WHERE id=".$news_id;
			}
			else {
				$sql = "INSERT INTO ".DB_TABLE_PLUGIN."news(id_str, categorie_id, caption, news_short,
						news_long, writer, timestamp, access, locked)
						VALUE('".$validate_id_str."', ".(int)$news_categorie->getValue().",
						'".StdSqlSafety($caption->getValue())."',
						'".StdSqlSafety(StdContent($news_short->getValue(), false))."',
						'".StdSqlSafety(StdWysiwymPrepare($news_long->getValue(), false))."',
						".$_SESSION['admin_id'].", ".TIME_STAMP.", ".$access.", ".(int)$locked->getValue().")";
			}
			if (mysql_query($sql, DB_CMS)) {
				/* Speicherung erfolgreich -> Newsletter */
				echo ActionReport(REPORT_OK, "Gespeichert", "Die Neuigkeit wurde erfolgreich gespeichert!");
			}
			else {
				/* Fehler beim speichern */
				echo ActionReport(REPORT_ERROR, "Fehler", "Es trat eine Fehler beim abspeichern auf!
						<br />MySQL Fehler: ".mysql_error(DB_CMS));
			}
		}
	}
	else {
		/* Gruppenwahl verstecken */
		if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
			$access_groups->setCssClass('select_groups show');
		}
		/* Ausgabe des Formulars */
		echo $form->getForm();
	}
}

?>