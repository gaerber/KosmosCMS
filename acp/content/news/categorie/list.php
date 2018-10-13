<?php

 /*
 =====================================================
 Name ........: Plugin: Neuigkeiten Kategorien
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: list.php
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
 Plugin: Liste mit allen Kategorien der Neuigkeiten.

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

/*** Titel *******************************************/
echo "<h1 class=\"first\">Kategorien</h1>";

/*** Aktionen ****************************************/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	/* Kategorie darf nicht verwendet werden */
	$result_num_news = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news
			WHERE categorie_id=".$_GET['delete'])
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = $result_num_news->fetch_row();

	if ($line[0] == 0) {
		if (Database::instance()->query("DELETE FROM ".DB_TABLE_PLUGIN."news_categorie
				WHERE id=".StdSqlSafety($_GET['delete']))) {
			echo ActionReport(REPORT_OK, "Kategorie gelöscht", "Die Kategorie wurde erfolgreich gelöscht!");
		}
		else {
			echo ActionReport(REPORT_ERROR, "Fehler",
					"Die Kategorie konnte nicht gelöscht werden!<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Nicht möglich",
				"Solange Neuigkeiten in dieser Kategorie existieren, ist es nicht möglich diese zu löschen!");
	}
}

/*** Links *******************************************/
echo "<p><img src=\"img/icons/plugins/news/categorie/add.png\" alt=\"\" />
		<a href=\"?page=news-categorie-edit\">Neue Kategorie</a></p>";

/*** Tabelle *****************************************/
echo "  <table>\r\n";
echo "    <tr class=\"table_title\">
      <td>Name</td>
      <td>Anz. Neuigkeiten</td>
      <td colspan=\"3\"></td>
    </tr>\r\n";

$row_ctr = 1;

$result = Database::instance()->query("SELECT id, name
		FROM ".DB_TABLE_PLUGIN."news_categorie
		ORDER BY name ASC")
		OR FatalError(FATAL_ERROR_MYSQL);
while ($row = $result->fetch_assoc()) {
	if ($row_ctr++ % 2)
		echo "    <tr class=\"table_odd\">\r\n";
	else
		echo "    <tr class=\"table_even\">\r\n";

	echo "      <td>".$row['name']."</td>\r\n";

	/* Berechnung anzahl Neuigkeiten */
	$result_num_news = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news
			WHERE categorie_id=".$row['id'])
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = $result_num_news->fetch_row();
	$num_news = $line[0];
	echo "      <td>".$num_news."</td>\r\n";

	/* Neuigkeiten dieser Kategorie anzeigen */
	if ($num_news && (ACP_AdminAccess(ACP_ACCESS_M_NEWS | ACP_ACCESS_M_NEWS_COM)))
		echo "      <td class=\"icon\"><a href=\"?page=news-list&amp;categorie=".$row['id']."\" onmouseover=\"Tip('Neuigkeiten dieser Kategorie anzeigen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/categorie/news.png\" alt=\"\" /></a></td>\r\n";
	else
		echo "      <td></td>\r\n";

	/* Kategorie umbenennen */
	echo "      <td class=\"icon\"><a href=\"?page=news-categorie-edit&amp;id=".$row['id']."\" onmouseover=\"Tip('Kategorie umbenennen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/categorie/edit.png\" alt=\"\" /></a></td>\r\n";

	/* Kategorie loeschen */
	if ($num_news == 0)
		echo "      <td class=\"icon\"><a href=\"javascript:confirmDeletion('?page=news-categorie-list&amp;delete=".$row['id']."', 'Wollen Sie wirklich diese Kategorie löschen?')\" onmouseover=\"Tip('Kategorie löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/categorie/delete.png\" alt=\"\" /></a></td>\r\n";
	else
		echo "      <td></td>\r\n";

	echo "    </tr>\r\n";
}

echo "  </table>";

?>
