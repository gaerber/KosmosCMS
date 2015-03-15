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
	$result_num_news = mysql_query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news
			WHERE categorie_id=".$_GET['delete'], DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_row($result_num_news);
	
	if ($line[0] == 0) {
		if (mysql_query("DELETE FROM ".DB_TABLE_PLUGIN."news_categorie
				WHERE id=".StdSqlSafety($_GET['delete']), DB_CMS)) {
			echo ActionReport(REPORT_OK, "Kategorie gelöscht", "Die Kategorie wurde erfolgreich gelöscht!");
		}
		else {
			echo ActionReport(REPORT_ERROR, "Fehler",
					"Die Kategorie konnte nicht gelöscht werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
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

$result = mysql_query("SELECT id, name
		FROM ".DB_TABLE_PLUGIN."news_categorie
		ORDER BY name ASC", DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($result)) {
	if ($row_ctr++ % 2)
		echo "    <tr class=\"table_odd\">\r\n";
	else
		echo "    <tr class=\"table_even\">\r\n";

	echo "      <td>".$row['name']."</td>\r\n";
	
	/* Berechnung anzahl Neuigkeiten */
	$result_num_news = mysql_query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news
			WHERE categorie_id=".$row['id'], DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_row($result_num_news);
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