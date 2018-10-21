<?php

 /*
 =====================================================
 Name ........: Menutree
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: tree.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 | 0.1    | 10.05.2011 | Programm erstellt.
 | 1.0    | 02.07.2011 | Fehlerpruefung Max. Levels
 | 1.0.1  | 05.07.2011 | Ueberarbeitung Sicherheit
 | 1.0.2  | 25.07.2011 | Error Seiten speziell
 -----------------------------------------------------
 Beschreibung :
 Bearbeiten der Menuestruktur.

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

/* Titel */
echo "<h1 class=\"first\">Menü Stammbaum</h1>\r\n";

/*
 * Bearbeitungsfunktionen
 */

/* Eine Ebene hinein */
if (isset($_GET['in'])) {
	$res_element = Database::instance()->query("SELECT id, id_str, menu_is_categorie, menu_sub, menu_order
			FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".StdSqlSafety($_GET["in"]))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line_element = $res_element->fetch_assoc()) {
		$res_change = Database::instance()->query("SELECT id, menu_sub, menu_order
				FROM ".DB_TABLE_ROOT."cms_menu WHERE menu_sub=".$line_element["menu_sub"]."
				&& menu_order<".$line_element["menu_order"]."
				ORDER BY menu_order DESC LIMIT 1")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line_change = $res_change->fetch_assoc()) {
			/* Pruefen, dass max. Levels eingehalten werden */
			if (($line_element['menu_is_categorie'] && (countLevels($line_element['id']) < MENU_MAX_LEVEL_CATEGORIE))
					|| (!$line_element['menu_is_categorie']
						&& ((countLevels($line_element['id']) + countSubLevels($line_element['id'])) < MENU_MAX_LEVEL))) {
				/* Pruefen, dass id_str nicht doppelt existiert */
				$res_check = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_ROOT."cms_menu
						WHERE id_str='".$line_element["id_str"]."'
						&& menu_sub=".$line_change["id"])
						OR FatalError(FATAL_ERROR_MYSQL);
				if (($line_check = $res_check->fetch_assoc()) && (!$line_check[0])) {
					/* Alte Reihenfolge korrigieren */
					Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_order=(menu_order-1)
							WHERE menu_sub=".$line_element["menu_sub"]."
							&& menu_order>".$line_element["menu_order"])
							OR FatalError(FATAL_ERROR_MYSQL);
					/* Neue menu_order berechnen */
					$res_order = Database::instance()->query("SELECT menu_order
							FROM ".DB_TABLE_ROOT."cms_menu WHERE menu_sub=".$line_change["id"]."
							ORDER BY menu_order DESC LIMIT 1")
							OR FatalError(FATAL_ERROR_MYSQL);
					if ($order = $res_order->fetch_assoc())
						$order = $order['menu_order'] + 1;
					else
						$order = 1;

					/* Verschieben */
					Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_sub=".$line_change["id"].",
							menu_order=".$order." WHERE id=".$line_element["id"])
							OR FatalError(FATAL_ERROR_MYSQL);
				}
				else {
					echo ActionReport(REPORT_ERROR, "Name existiert bereits",
							"Auf der Zielebene existiert bereits eine Kategorie oder eine Seite mit gleichem Namen! Sie müssen einer von beiden ändern, bevor Sie diese Aktion duchführen können.");
				}
			}
			else {
				echo ActionReport(REPORT_ERROR, "Maximale Anzahl Ebene erreicht",
						"Sie dürfen die maximal erlaubte Anzahl von Ebenen nicht überschreiten!");

			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, "Nicht möglich",
					"Diese Kategorie / Seite kann keiner Unterkategorie zugewiesen werden!");
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Nicht gefunden",
				"Die Kategorie / Seite wurde in der Datenbank nicht gefunden!");
	}
}

/* Eine Ebene hinaus */
if (isset($_GET['out'])) {
	$res_element = Database::instance()->query("SELECT id, id_str, menu_sub, menu_order
			FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".StdSqlSafety($_GET["out"]))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line_element = $res_element->fetch_assoc()) {
		if ($line_element['menu_sub']) {
			$res_change = Database::instance()->query("SELECT id, menu_sub, menu_order
					FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".$line_element["menu_sub"])
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line_change = $res_change->fetch_assoc()) {
				/* Pruefen, dass id_str nicht doppelt existiert */
				$res_check = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_ROOT."cms_menu
						WHERE id_str='".$line_element["id_str"]."'
						&& menu_sub=".$line_change["menu_sub"])
						OR FatalError(FATAL_ERROR_MYSQL);
				if (($line_check = $res_check->fetch_assoc()) && (!$line_check[0])) {
					/* Menue Reihenfolge manipulieren */
					Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_order=(menu_order+1)
							WHERE menu_sub=".$line_change["menu_sub"]."
							&& menu_order>".$line_change["menu_order"])
							OR FatalError(FATAL_ERROR_MYSQL);
					/* Alte Reihenfolge korrigieren */
					Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_order=(menu_order-1)
							WHERE menu_sub=".$line_element["menu_sub"]."
							&& menu_order>".$line_element["menu_order"])
							OR FatalError(FATAL_ERROR_MYSQL);
					/* Verschieben */
					Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET
							menu_sub=".$line_change["menu_sub"].",
							menu_order=".($line_change["menu_order"] + 1)."
							WHERE id=".$line_element["id"])
							OR FatalError(FATAL_ERROR_MYSQL);
				}
				else {
					echo ActionReport(REPORT_ERROR, "Name existiert bereits",
							"Auf der Zielebene existiert bereits eine Kategorie oder eine Seite mit gleichem Namen! Sie müssen einer von beiden ändern, bevor Sie diese Aktion duchführen können.");
				}
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Es wurde keine Unterkategorie / Unterseite gefunden!");
			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, "Nicht möglich",
					"Die Kategorie / Seite befindet sich bereits auf der äussersten Ebene!");
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Nicht gefunden",
				"Die Kategorie / Seite wurde in der Datenbank nicht gefunden!");
	}
}

/* Ein Element hoch oder runter */
if (isset($_GET['up']) || isset($_GET['down'])) {
	if (isset($_GET['up']))
		$res_element = Database::instance()->query("SELECT id, menu_sub, menu_order
				FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".StdSqlSafety($_GET["up"]))
				OR FatalError(FATAL_ERROR_MYSQL);
	else
		$res_element = Database::instance()->query("SELECT id, menu_sub, menu_order
				FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".StdSqlSafety($_GET["down"]))
				OR FatalError(FATAL_ERROR_MYSQL);
	if ($line_element = $res_element->fetch_assoc()) {
		if (isset($_GET['up']))
			$res_change = Database::instance()->query("SELECT id, menu_order FROM ".DB_TABLE_ROOT."cms_menu
					WHERE menu_sub=".$line_element["menu_sub"]."
					&& menu_order<".$line_element["menu_order"]."
					ORDER BY menu_order DESC LIMIT 1")
					OR FatalError(FATAL_ERROR_MYSQL);
		else
			$res_change = Database::instance()->query("SELECT id, menu_order FROM ".DB_TABLE_ROOT."cms_menu
					WHERE menu_sub=".$line_element["menu_sub"]."
					&& menu_order>".$line_element["menu_order"]."
					ORDER BY menu_order ASC LIMIT 1")
					OR FatalError(FATAL_ERROR_MYSQL);
		if ($line_change = $res_change->fetch_assoc()) {
			/* Tauschen von menu_order */
			if (!(Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_order=".$line_change["menu_order"]."
					WHERE id=".$line_element["id"])
					&& Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_order=".$line_element["menu_order"]."
					WHERE id=".$line_change["id"]))) {
				echo ActionReport(REPORT_ERROR, "Interner Fehler", Database::instance()->getErrorMessage());
			}
		}
		else {
			if (isset($_GET['up']))
				echo ActionReport(REPORT_EINGABE, "Fehler",
						"Die Kategorie / Seite befindet sich bereits an oberster Stelle!");
			else
				echo ActionReport(REPORT_EINGABE, "Fehler",
						"Die Kategorie / Seite befindet sich bereits an unterster Stelle!");
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Nicht gefunden",
				"Die Kategorie / Seite wurde in der Datenbank nicht gefunden!");
	}
}

/* Anzeigen im Menu */
if (isset($_GET['menu_view'])) {
	/* Spezialseiten duefen nicht angezeigt werden */
	if (in_array($_GET['menu_view'], $DefaultErrorPages)) {
		echo ActionReport(REPORT_ERROR, "Nicht möglich", "Die HTTP Fehlerseite darf nicht im Menü angezeigt werden!");
	}
	else {
		if (!(Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET menu_view=(!menu_view)
				WHERE id=".StdSqlSafety($_GET["menu_view"])))) {
			echo ActionReport(REPORT_EINGABE, "Nicht gefunden",
					"Die Kategorie / Seite wurde in der Datenbank nicht gefunden!");
		}
	}
}

/* Sperren */
if (isset($_GET['locked'])) {
	/* Spezialseiten duefen nicht gesperrt werden */
	if (in_array($_GET['locked'], $DefaultErrorPages)) {
		echo ActionReport(REPORT_ERROR, "Nicht möglich", "Die HTTP Fehlerseite darf nicht gesperrt werden!");
	}
	else {
		if (!(Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu SET locked=(!locked)
				WHERE id=".StdSqlSafety($_GET["locked"])))) {
			echo ActionReport(REPORT_EINGABE, "Nicht gefunden",
					"Die Kategorie / Seite wurde in der Datenbank nicht gefunden!");
		}
	}
}

/* Loeschen */
if (isset($_GET['delete'])) {
	/* Spezialseiten duefen nicht geloescht werden */
	if (in_array($_GET['delete'], $DefaultErrorPages)) {
		echo ActionReport(REPORT_ERROR, "Nicht möglich", "Die HTTP Fehlerseite darf nicht gelöscht werden!");
	}
	else {
		/* Die untermenus eine Ebene nach vorne nehmen */
		$res_element = Database::instance()->query("SELECT id, menu_sub, menu_order FROM ".DB_TABLE_ROOT."cms_menu
				WHERE id=".StdSqlSafety($_GET["delete"]))
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line_element = $res_element->fetch_assoc()) {
			/* Menue Reihenfolge */
			$res_change = Database::instance()->query("SELECT id FROM ".DB_TABLE_ROOT."cms_menu
					WHERE menu_sub=".$line_element["id"]." ORDER BY menu_order ASC")
					OR FatalError(FATAL_ERROR_MYSQL);
			/* Platz machen */
			Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu
					SET menu_order=(menu_order + ".($res_change->num_rows - 1).")
					WHERE menu_sub=".$line_element["menu_sub"]." && menu_order>".$line_element["menu_order"])
					OR FatalError(FATAL_ERROR_MYSQL);
			/* Submenus nach vorne nehmen */
			for ($i=0; $row=$res_change->fetch_assoc(); $i++) {
				Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_menu
						SET menu_sub=".$line_element["menu_sub"].", menu_order=".($line_element["menu_order"] + $i)."
						WHERE id=".$row["id"])
						OR FatalError(FATAL_ERROR_MYSQL);
			}
			/* Alle Inhalte Loeschen und dann die Seite */
			if (!(Database::instance()->query("DELETE FROM ".DB_TABLE_ROOT."cms_content
					WHERE page_id=".$line_element["id"])
					&& Database::instance()->query("DELETE FROM ".DB_TABLE_ROOT."cms_menu
					WHERE id=".$line_element["id"]))) {
				echo ActionReport(REPORT_ERROR, "Nicht gelöscht", "Die Kategorie / Seite konnte nicht gelöscht werden!");
			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, "Nicht gefunden",
					"Die Kategorie / Seite wurde in der Datenbank nicht gefunden!");
		}
	}
}

/* Ausgabe Menustammbaum */
$o_menutree = new buildMenuTree(Database::instance());
$menutree_txt = $o_menutree->getMenuTree(0, 99, 1, true, "menu/tree/{pos}");

$a_options = explode("|", $menutree_txt);
/* Letzter (leerer) Eintrag entfernen (Verursacht von Template) */
array_pop($a_options);

$html = "  <table>\r\n";
$html .= "    <tr class=\"table_title\">
      <td colspan=\"5\"></td>
      <td>Kategorien und Seiten</td>
      <td colspan=\"3\"></td>
    </tr>\r\n";

/* Schlaufe fuer alle Elemente */
for ($i=0; $i < sizeof($a_options); $i++) {
	$a_options_data = explode("$", $a_options[$i]);

	/* Datensatz holen */
	$result = Database::instance()->query("SELECT id, label, menu_is_categorie, menu_view, locked
			FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".$a_options_data[0])
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		if ($i % 2)
			$html .= "    <tr class=\"table_even\">\r\n";
		else
			$html .= "    <tr class=\"table_odd\">\r\n";

		/* Hoch */
		if ($a_options_data[2] == "n" || $a_options_data[2] == "l") {
			$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;up=".$line['id']."\" onmouseover=\"Tip('Ein Element nach oben')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/up.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		/* Runter */
		if ($a_options_data[2] == "f" || $a_options_data[2] == "n") {
			$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;down=".$line['id']."\" onmouseover=\"Tip('Ein Element nach unten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/down.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		/* Hinein (Anzahl Ebene) */
		if ($a_options_data[2] != "f" && $a_options_data[2] != "s"
				&& (($line['menu_is_categorie'] && ($a_options_data[1] < MENU_MAX_LEVEL_CATEGORIE))
					|| (!$line['menu_is_categorie'] && ($a_options_data[1] < MENU_MAX_LEVEL)))
				&& ((countSubLevels($line['id']) + $a_options_data[1]) < MENU_MAX_LEVEL)) {
			$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;in=".$line['id']."\" onmouseover=\"Tip('Als Unterebene')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/in.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		/* Heraus */
		if ($a_options_data[1] != 1) {
			$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;out=".$line['id']."\" onmouseover=\"Tip('Aus Unterebene entfernen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/out.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		/* Anzeige im Menu */
		if ($a_options_data[1] <= MENU_MAX_LEVEL_VIEW
				&& !in_array($line['id'], $DefaultErrorPages)) {
			if ($line['menu_view'])
				$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;menu_view=".$line['id']."\" onmouseover=\"Tip('Nicht mehr im Menü anzeigen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/view.png\" alt=\"\" /></a></td>\r\n";
			else
				$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;menu_view=".$line['id']."\" onmouseover=\"Tip('Im Menü anzeigen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/view_not.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		/* Bearbeiten, Seitennamen, Backups */
		if ($line['menu_is_categorie']) {
			$html .= "      <td>".str_repeat("&nbsp;", 4 * ($a_options_data[1]-1))."<a href=\"?page=website-edit&amp;mode=categorie&amp;id=".$line['id']."\" onmouseover=\"Tip('Kategorie bearbeiten')\" onmouseout=\"UnTip()\"><b>".$line['label']."</b></a></td>\r\n";
			$html .= "      <td class=\"icon\"></td>\r\n";
		}
		else {
			$html .= "      <td>".str_repeat("&nbsp;", 4 * ($a_options_data[1]-1))."<a href=\"?page=website-edit&amp;mode=page&amp;id=".$line['id']."\" onmouseover=\"Tip('Seite bearbeiten')\" onmouseout=\"UnTip()\">".$line['label']."</a></td>\r\n";
			$html .= "      <td class=\"icon\"><a href=\"?page=website-backups&amp;id=".$line['id']."\" onmouseover=\"Tip('Backups')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/backup.png\" alt=\"\" /></a></td>\r\n";
		}

		/* Sperrung */
		if (!in_array($line['id'], $DefaultErrorPages)) {
			if ($line['locked'])
				$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;locked=".$line['id']."\" onmouseover=\"Tip('Entsperren')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/locked.png\" alt=\"\" /></a></td>\r\n";
			else
				$html .= "      <td class=\"icon\"><a href=\"?page=website-tree&amp;locked=".$line['id']."\" onmouseover=\"Tip('Sperren')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/locked_not.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		/* Loeschen */
		if (!in_array($line['id'], $DefaultErrorPages)) {
			if ($line['menu_is_categorie'])
				$html .= "      <td class=\"icon\"><a href=\"javascript:confirmDeletion('?page=website-tree&amp;delete=".$line['id']."', 'Wollen Sie wirklich diese Kategorie unwiderruflich löschen?')\" onmouseover=\"Tip('Kategorie löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/delete.png\" alt=\"\" /></a></td>\r\n";
			else
				$html .= "      <td class=\"icon\"><a href=\"javascript:confirmDeletion('?page=website-tree&amp;delete=".$line['id']."', 'Wollen Sie diese Seite wirklich unwiderruflich löschen?')\" onmouseover=\"Tip('Seite löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/menu/delete.png\" alt=\"\" /></a></td>\r\n";
		}
		else {
			$html .= "      <td class=\"icon\"></td>\r\n";
		}

		$html .= "    </tr>\r\n";
	}
	else {
		FatalError(FATAL_ERROR_MENU);
	}
}

$html .= "  </table>";

/* Ausgabe */
echo $html;


?>
