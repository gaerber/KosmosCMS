<?php

 /*
 =====================================================
 Name ........: Bearbeitung und Erstellung
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
 |1.0     | 05.05.2011 | Programm erstellt.
 |1.0.1   | 05.07.2011 | Ueberarbeitung Sicherheit
 |1.0.2   | 04.08.2011 | Error Seiten speziell
 |1.0.3   | 30.03.2013 | Gruppenwahl verstecken.
 -----------------------------------------------------
 Beschreibung :
 Bearbeitung und Erstellung von Kategorien und Seiten.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_menutree\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_menustamm\"";
///////////////////////////////////////////////////////

/**
 * Submenu Auswahllist erstellen.
 * @param $o_submenu ist das Formularobjekt in der die Optionen hinzugefuegt werden.
 * @param $active ist das aktive Submenu.
 */
function addSubmenuOptions($o_submenu, $active=NULL) {
	/* Erster Eintrag ohne Mutterelement */
	$o_submenu->addOption('Kein Mutterelement', 0);

	$o_menutree = new buildMenuTree(DB_CMS);

	if ($_GET['mode'] == "page") {
		$menutree_txt = $o_menutree->getMenuTree(0, (MENU_MAX_LEVEL-2), 1, true,
				"menu/select/def");
	}
	else {
		$menutree_txt = $o_menutree->getMenuTree(0, (MENU_MAX_LEVEL_CATEGORIE-2), 1,
				true, "menu/select/def");
	}

	$a_options = explode("|", $menutree_txt);
	/* Letzter (leerer) Eintrag entfernen (Verursacht von Template) */
	array_pop($a_options);
	for ($i=0; $i < sizeof($a_options); $i++) {
		$a_options_data = explode("$", $a_options[$i]);
		$o_submenu->addOption(str_repeat("&nbsp;", 4 * ($a_options_data[1]-1)).$a_options_data[2],
				$a_options_data[0], $active == $a_options_data[0]);
	}
}

/* Ueberschrift */
if (isset($_GET['id'])) {
	if ($_GET['mode'] == "page") {
		echo "<h1 class=\"first\">Seite bearbeiten</h1>\r\n";
	}
	else {
		echo "<h1 class=\"first\">Kategorie bearbeiten</h1>\r\n";
		if (!MENU_MAX_LEVEL_CATEGORIE) {
			echo ActionReport(REPORT_EINGABE, "Nicht erlaubt",
					"In Ihrer Installation ist die Verwendeung von Kategorien nicht zulässig!");
			$form_hide = true;
		}
	}
}
else {
	if ($_GET['mode'] == "page") {
		echo "<h1 class=\"first\">Neue Seite</h1>\r\n";
	}
	else {
		echo "<h1 class=\"first\">Neue Kategorie</h1>\r\n";
		if (!MENU_MAX_LEVEL_CATEGORIE) {
			echo ActionReport(REPORT_EINGABE, "Nicht erlaubt",
					"In Ihrer Installation ist die Verwendeung von Kategorien nict zulässig!");
			$form_hide = true;
		}
	}
}

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');

$id_str = $form->addElement('text', 'id_str', 'Name', NULL, true);
$label = $form->addElement('text', 'label', 'Navigationstitel', NULL, true);
if (!(isset($_GET['id']) && in_array($_GET['id'], $DefaultErrorPages)))
	$menu_view = $form->addElement('checkbox', 'menu_view', 'Sichtbar in Navigation', 1);
if (!isset($_GET['id']))
	$menu_sub = $form->addElement('select', 'menu_sub', 'Mutterelement');
$caption = $form->addElement('text', 'caption', 'Titel');
if (ACP_SLOGAN_EN) {
	$slogan = $form->addElement('textarea', 'slogan', 'Slogan');
	$slogan->setRowsCols(3, 80);
}
if ($_GET['mode'] == "page") {
	$content = $form->addElement('textarea', 'content', 'Inhalt');
	$content->setWysiwyg(true);
	$content->setRowsCols(20, 80);
}
if (ACP_TAGS_EN && ($_GET['mode'] == "page"))
	$tags = $form->addElement('text', 'tags', 'Tags');
if (ACP_IMAGE_EN)
	$image = $form->addElement('file', 'image', 'Bild');
if ($_GET['mode'] == "page")
	$plugin = $form->addElement('select', 'plugin', 'Modul');
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
if (!(isset($_GET['id']) && in_array($_GET['id'], $DefaultErrorPages)))
	$locked = $form->addElement('checkbox', 'locked', 'Sperren', 1);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');
$submit->setCssClassField("wymupdate");

/* Defaultwerte Setzen */
if (!$form->checkSubmit()) {
	if (isset($_GET['id'])) {
		/* Seite bearbeiten */
		$result = mysql_query("SELECT * FROM ".DB_TABLE_ROOT."cms_menu
				WHERE id=".StdSqlSafety($_GET["id"])."
				&& menu_is_categorie=".(int)($_GET["mode"] != "page"), DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);

		if ($line = mysql_fetch_array($result)) {
			$id_str->setValue($line['id_str']);
			$label->setValue($line['label']);
			if ($line['menu_view']
					&& (!(isset($_GET['id']) && in_array($_GET['id'], $DefaultErrorPages))))
				$menu_view->setChecked(true);
			$caption->setValue($line['caption']);
			if (ACP_SLOGAN_EN)
				$slogan->setValue($line['slogan']);
			if ($line['locked']
					&& (!(isset($_GET['id']) && in_array($_GET['id'], $DefaultErrorPages))))
				$locked->setChecked(true);
			if ($_GET['mode'] == "page") {
				/* Inhalt */
				$result = mysql_query("SELECT html FROM ".DB_TABLE_ROOT."cms_content
						WHERE page_id=".$line["id"]."
						ORDER BY timestamp DESC LIMIT 1", DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
				if ($line_content = mysql_fetch_array($result))
					if ($line_content['html'])
						$content->setValue($line_content['html']);
					else
						$content->setValue("<p>&nbsp;</p>");
			}
			if (ACP_TAGS_EN && ($_GET['mode'] == "page"))
				$tags->setValue($line['tags']);
			if (ACP_IMAGE_EN)
				$image->setLabel("Neues Bild");
			if ($_GET['mode'] == "page") {
				/* Plugins */
				$plugin->addOption("Kein Modul", 0);
				$result = mysql_query("SELECT id, label FROM ".DB_TABLE_ROOT."cms_plugin
						WHERE locked=0 ORDER BY label ASC", DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
				while ($row = mysql_fetch_array($result)) {
					$plugin->addOption($row['label'], $row['id'],
							(bool) ($line['plugin'] == $row['id']));
				}
			}
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
			/* Datensatz existiert nicht */
			$form_hide = true;
			if ($_GET['mode'] == "page")
				echo ActionReport(REPORT_ERROR, "Seite existiert nicht",
						"Die gewünschte Seite wurde in der Datenbank nicht gefunden.");
			else
				echo ActionReport(REPORT_ERROR, "Kategorie existiert nicht",
						"Die gewünschte Kategorie wurde in der Datenbank nicht gefunden.");
		}
	}
	else {
		/* Neue Kategorie / Seite */
		$menu_view->setChecked(true);
		if ($_GET['mode'] == "page")
			$content->setValue("<p>&nbsp;</p>");
		if (ACP_ACCESS_SYSTEM_EN)
			$access_all->setChecked(true);
	}
}
if ($form->checkSubmit() || (!$form->checkSubmit() && !isset($_GET['id']))) {
	/* Submenu nur bei neuen Kategorien / Seiten */
	if (!isset($_GET['id'])) {
		addSubmenuOptions($menu_sub);
	}

	/* Plugins */
	if ($_GET['mode'] == "page") {
		$plugin->addOption("Kein Modul", 0);
		$result = mysql_query("SELECT id, label FROM ".DB_TABLE_ROOT."cms_plugin
				WHERE locked=0 ORDER BY label ASC", DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$plugin->addOption($row['label'], $row['id']);
		}
	}

	/* Gruppen */
	if (ACP_ACCESS_SYSTEM_EN) {
		$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
				ORDER BY name ASC", DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$access_groups->addOption($row['name'], 1<<$row['id']);
		}
	}
}


/* Auswertung */
if ($form->checkSubmit() && $form->checkForm()) {
	/* Checken, dass id_str nicht doppelt existiert */
	if (isset($_GET['id'])) {
		$result = mysql_query("SELECT menu_sub FROM ".DB_TABLE_ROOT."cms_menu
				WHERE id=".StdSqlSafety($_GET["id"]), DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			$check_menu_sub = $line['menu_sub'];
		}
		else {
			/* Error beim Update */
			$form_hide = true;
			if ($_GET['mode'] == "page")
				echo ActionReport(REPORT_ERROR, "Seite existiert nicht",
						"Die gewünschte Seite wurde in der Datenbank nicht gefunden.");
			else
				echo ActionReport(REPORT_ERROR, "Kategorie existiert nicht",
						"Die gewünschte Kategorie wurde in der Datenbank nicht gefunden.");
		}
	}
	else {
		$check_menu_sub = StdSqlSafety($menu_sub->getValue());
	}

	if (isset($check_menu_sub)) {
		if (isset($_GET['id']))
			$result = mysql_query("SELECT id FROM ".DB_TABLE_ROOT."cms_menu
					WHERE id!=".StdSqlSafety($_GET["id"])."
					&& menu_sub=".$check_menu_sub."
					&& id_str='".getIdStr($id_str->getValue(), "NOCHECK")."'", DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
		else
			$result = mysql_query("SELECT id FROM ".DB_TABLE_ROOT."cms_menu
					WHERE menu_sub=".$check_menu_sub."
					&& id_str='".getIdStr($id_str->getValue(), "NOCHECK")."'", DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			/* Dieser Seitennamen existiert bereits */
			$id_str->setError(true);
			/* Fehlerausgabe */
			if ($_GET['mode'] == "page")
				echo ActionReport(REPORT_EINGABE, "Seitennamen existiert bereits",
							"Es Existiert bereits eine Seite mit gleichem Namen.");
			else
				echo ActionReport(REPORT_EINGABE, "Kategorienamen existiert bereits",
							"Es Existiert bereits eine Kategorie mit gleichem Namen.");
			/* Gruppenwahl verstecken */
			if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
				$access_groups->setCssClass('select_groups show');
			}
			/* Formular ausgeben */
			echo $form->getForm();
		}
		else {
			/* Checken, dass bei access_gpr min 1 Gruppe ausgewählt ist */
			if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue() && !sizeof($access_groups->getValue())) {
				/* Es muss nim. eine Gruppe ausgewaehlt werden */
				$access_groups->setError(true);
				/* Fehlerausgabe */
				if ($_GET['mode'] == "page")
					echo ActionReport(REPORT_EINGABE, "Min. eine Gruppe auswählen",
							"Wenn Sie die Seite nur bestimmten Gruppen zur Verfügung stellen ".
							"möchten, müssen Sie mindestens eine Gruppe auswählen.");
				else
					echo ActionReport(REPORT_EINGABE, "Min. eine Gruppe auswählen",
							"Wenn Sie die Kategorie nur bestimmten Gruppen zur Verfügung stellen ".
							"möchten, müssen Sie mindestens eine Gruppe auswählen.");
				/* Formular ausgeben */
				$access_groups->setCssClass('select_groups show');
				echo $form->getForm();
			}
			else {
				/* Alles richtig -> Vorbereiten zum Abspeichern */
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

				if (isset($_GET['id'])) {
					$sql = "UPDATE ".DB_TABLE_ROOT."cms_menu SET
							id_str='".ValidateFileSystem($id_str->getValue())."',
							label='".StdSqlSafety($label->getValue())."',
							caption='".StdSqlSafety($caption->getValue())."',
							writer=".$_SESSION["admin_id"].", timestamp=".TIME_STAMP;
					if (!(isset($_GET['id']) && in_array($_GET['id'], $DefaultErrorPages)))
						$sql .= ", menu_view=".(int)$menu_view->getValue().",
								locked=".(int)$locked->getValue();
					if (ACP_SLOGAN_EN)
						$sql .= ", slogan='".StdSqlSafety($slogan->getValue())."'";
					if (ACP_ACCESS_SYSTEM_EN)
						$sql .= ", access=".$access;
					if ($_GET['mode'] == "page") {
						$sql .= ", plugin=".$plugin->getValue();
						if (ACP_TAGS_EN)
							$sql .= ", tags='".StdSqlSafety($tags->getValue())."'";
					}
					$sql .= " WHERE id=".StdSqlSafety($_GET["id"]);
				}
				else {
					/* Berechnung menu_order */
					$result = mysql_query("SELECT menu_order FROM ".DB_TABLE_ROOT."cms_menu
							WHERE menu_sub=".$menu_sub->getValue()."
							ORDER BY menu_order DESC LIMIT 1", DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);

					if ($line = mysql_fetch_array($result)) {
						$menu_order = $line['menu_order'] + 1;
					}
					else {
						/* Erste Unterseite/-kategorie */
						$menu_order = 1;
					}

					$sql = "INSERT INTO ".DB_TABLE_ROOT."cms_menu
							(id_str, label, menu_sub, menu_order, menu_view, caption, locked, writer, timestamp,
							menu_is_categorie";
					if (ACP_SLOGAN_EN)
						$sql .= ", slogan";
					if (ACP_ACCESS_SYSTEM_EN)
						$sql .= ", access";
					if ($_GET['mode'] == "page") {
						$sql .= ", plugin";
						if (ACP_TAGS_EN)
							$sql .= ", tags";
					}
					$sql .= ")VALUES('".ValidateFileSystem($id_str->getValue())."',
							'".StdSqlSafety($label->getValue())."',
							".$menu_sub->getValue().", ".$menu_order.", ".(int)$menu_view->getValue().",
							'".StdSqlSafety($caption->getValue())."',
							".(int)$locked->getValue().",
							".$_SESSION["admin_id"].", ".TIME_STAMP;
					if ($_GET['mode'] == "page")
						$sql .= ", 0";
					else
						$sql .= ", 1";
					if (ACP_SLOGAN_EN)
						$sql .= ", '".StdSqlSafety($slogan->getValue())."'";
					if (ACP_ACCESS_SYSTEM_EN)
						$sql .= ", ".$access;
					if ($_GET['mode'] == "page") {
						$sql .= ", ".$plugin->getValue();
						if (ACP_TAGS_EN)
							$sql .= ", '".StdSqlSafety($tags->getValue())."'";
					}
					$sql .= ")";
				}

				/* Abspeichern */
				if (mysql_query($sql, DB_CMS)) {
					/* Seite / Kategorie abgespeichert */
					if ($_GET['mode'] == "page") {
						if (isset($_GET['id'])) {
							/* Inhalt abspeichern wenn er geaendert wurde */
							$result = mysql_query("SELECT html FROM ".DB_TABLE_ROOT."cms_content
									WHERE page_id=".StdSqlSafety($_GET["id"])."
									ORDER BY timestamp DESC LIMIT 1", DB_CMS)
									OR FatalError(FATAL_ERROR_MYSQL);
							if ($line = mysql_fetch_array($result)) {
								$content_html = StdWysiwymPrepare($content->getValue());
								if ($line['html'] != $content_html) {
									/* Inhalt wurde geaendert oder wurde nicht gefunden */
									$sql = "INSERT INTO ".DB_TABLE_ROOT."cms_content
											(page_id, html, writer, timestamp)
											VALUES(".StdSqlSafety($_GET["id"]).",
											'".StdSqlSafety($content_html)."',
											".$_SESSION["admin_id"].",
											".TIME_STAMP.")";
								}
								else {
									$sql = "";
								}
							}
							else {
								$sql = "INSERT INTO ".DB_TABLE_ROOT."cms_content
										(page_id, html, writer, timestamp)
										VALUES(".StdSqlSafety($_GET["id"]).",
										'".StdSqlSafety(StdWysiwymPrepare($content->getValue()))."',
										".$_SESSION["admin_id"].", ".TIME_STAMP.")";
							}
						}
						else {
							/* Inhalt Speichern */
							$sql = "INSERT INTO ".DB_TABLE_ROOT."cms_content
									(page_id, html, writer, timestamp)
									VALUES(LAST_INSERT_ID(),
									'".StdSqlSafety(StdWysiwymPrepare($content->getValue()))."', ".$_SESSION["admin_id"].",
									".TIME_STAMP.")";
						}

						if ($sql == "" || mysql_query($sql, DB_CMS)) {
							/* Alles OK */
							if (isset($_GET['id']))
								echo ActionReport(REPORT_OK, "Seite gespeichert",
										"Die Änderungen wurden erfolgreich übernommen!");
							else
								echo ActionReport(REPORT_OK, "Seite erstellt",
										"Die Seite wurde erfolgreich erstellt!");
						}
						else {
							echo ActionReport(REPORT_ERROR, "Fehler",
									"Beim Abspeichern des Inhaltes trat ein Fehler auf!
									<br />MySQL Fehler: ".mysql_error());
						}
					}
					else {
						if (isset($_GET['id']))
							echo ActionReport(REPORT_OK, "Kategorie gespeichert",
									"Die Änderungen wurden erfolgreich übernommen.");
						else
							echo ActionReport(REPORT_OK, "Kategorie erstellt",
									"Die Kategorie wurde erfolgreich erstellt.");
					}
				}
				else {
					echo ActionReport(REPORT_ERROR, "Fehler beim abspeichern", mysql_error());
				}
			}
		}
	}
}
else {
	if (!(isset($form_hide) && $form_hide == true)) {
		/* Gruppenwahl verstecken */
		if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
			$access_groups->setCssClass('select_groups show');
		}
		/* Formular ausgeben */
		echo $form->getForm();
	}
}

?>