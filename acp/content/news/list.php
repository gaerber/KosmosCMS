<?php

 /*
 =====================================================
 Name ........: Plugin: Neuigkeiten Liste
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
 Plugin: Liste mit allen Neuigkeiten.

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

/* Filter */
$filter_sql = "1 ";
$filter_txt = "Beiträge";
if (!ACP_AdminAccess(ACP_ACCESS_M_NEWS_COM)) {
	$filter_sql .= "&& writer=".$_SESSION['admin_id'];
	$filter_txt .= " von ".$_SESSION['admin_name'];
}
if (isset($_GET['categorie']) && is_numeric($_GET['categorie'])) {
	$result = Database::instance()->query("SELECT name FROM ".DB_TABLE_PLUGIN."news_categorie
			WHERE id=".(int)$_GET['categorie'])
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		$filter_sql .= "&& categorie_id=".(int)$_GET['categorie'];
		$filter_txt .= " aus der Kategorie: ".$line['name'];
	}
}

echo "<h1 class=\"first\">Neuigkeiten</h1>";

if ($filter_txt != "Beiträge")
	echo ActionReport(REPORT_INFO, "Filter", $filter_txt);

/* Neuigkeit loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if (Database::instance()->query("DELETE FROM ".DB_TABLE_PLUGIN."news
			WHERE id=".StdSqlSafety($_GET['delete'])."
			&& (".ACP_AdminAccess(ACP_ACCESS_M_NEWS_COM)." || (writer=".$_SESSION['admin_id']."))")) {
		/* Alle Kommentare loeschen */
		if (Database::instance()->query("DELETE FROM ".DB_TABLE_PLUGIN."news_comment
				WHERE news_id=".StdSqlSafety($_GET['delete'])))
			echo ActionReport(REPORT_OK, "Neuigkeit gelöscht", "Der Beitrag wurde erfolgreich gelöscht!");
		else
			echo ActionReport(REPORT_INFO, "Neuigkeit gelöscht",
					"Der Beitrag wurde erfolgreich gelöscht, aber die dazugehörigen Kommentare konnten nicht gelöscht werden!");
	}
	else {
		echo ActionReport(REPORT_ERROR, "Fehler beim löschen",
				"Der Beitrag konnte nicht gelöscht werden!<br />MySQL Fehler:".Database::instance()->getErrorMessage());
	}
}

/* Anzahl Neuigkeiten ermitteln*/
$result = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news WHERE ".$filter_sql)
		OR FatalError(FATAL_ERROR_MYSQL);
$line = $result->fetch_row();

if ($line[0] > 0) {
	/* Eintraege vorhanden */
	$classPagination = new pagination($line[0], isset($_GET[PAGE_POINTER])
			? $_GET[PAGE_POINTER] : 1, PAGINATION_PER_PAGE);

	$result = Database::instance()->query("SELECT id, caption, news_short, writer, timestamp
			FROM ".DB_TABLE_PLUGIN."news WHERE ".$filter_sql." ORDER BY timestamp DESC
			LIMIT ".$classPagination->Offset().",".PAGINATION_PER_PAGE)
			OR FatalError(FATAL_ERROR_MYSQL);

	/* Ausgabe Liste */
	echo printBoxStart();

	while ($row = $result->fetch_assoc()) {
		/* Administrator Infos */
		$admin_info_name = "";
		$admin_info_email = "";
		getWriterInfo($row['writer'], $admin_info_name, $admin_info_email);

		/* Anzahl Kommentare */
		$res_com = Database::instance()->query("SELECT count(*), timestamp FROM ".DB_TABLE_PLUGIN."news_comment
				WHERE news_id=".$row['id']." ORDER BY timestamp DESC")
				OR FatalError(FATAL_ERROR_MYSQL);
		if (($line_com = $res_com->fetch_row()) && $line_com[0]) {
			if ($line_com[0] == 1)
				$comment = "<a href=\"?page=news-comment-list&amp;id=".$row['id']."\">1 Kommentar</a>";
			else
				$comment = "<a href=\"?page=news-comment-list&amp;id=".$row['id']."\">".$line_com[0]
						." Kommentar</a>";
			$watchme = ($_SESSION['admin_lastlogin'] < $line_com[1]);
		}
		else {
			/* Keine Kommentare */
			$comment = "Keine Kommentare";
			$watchme = false;
		}

		if ($_SESSION['admin_lastlogin'] < $row['timestamp'])
			$watchme = true;

		/* Informationen */
		if (PLUGIN_NEWS_COMMENT_EN && ACP_AdminAccess(ACP_ACCESS_M_NEWS_COM))
			$infos = array($comment, printDate($row['timestamp'])." - ".$admin_info_name);
		else
			$infos = printDate($row['timestamp'])." - ".$admin_info_name;
		/*$infos = array($comment, $admin_name." &lt;".$admin_email."&gt;",
				printDate($row['timestamp'])." ".date(FORMAT_TIME, $row['timestamp']));*/

		/* Ausgabe */
		echo printBox($row['caption'], $row['news_short'],
				"<a href=\"?page=news-edit&amp;id=".$row['id']."\" onmouseover=\"Tip('Neuigkeit bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/edit.png\" alt=\"\" /></a>
				<a href=\"javascript:confirmDeletion('?page=news-list&amp;delete=".$row['id']."', 'Wollen Sie diese Neuigkeit wirklich löschen?')\" onmouseover=\"Tip('Neuigkeit löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/delete.png\" alt=\"\" /></a>",
				$infos, NULL, $watchme);
	}

	/* Ende der Ausgabe */
	echo printBoxEnd();

	echo "<div class=\"pagination\">";
	if (isset($_GET['categorie']))
		echo $classPagination->PaginationLinks("?page=news-list&amp;categorie=".$_GET['categorie']
				."&amp;".PAGE_POINTER."=", PAGINATION_NUM);
	else
		echo $classPagination->PaginationLinks("?page=news-list&amp;".PAGE_POINTER."=", PAGINATION_NUM);
	echo "</div>\r\n";
}
else {
	/* Keine Eintraege vorhanden */
	if (isset($_GET['categorie']) && is_numeric($_GET['categorie']))
		echo ActionReport(REPORT_INFO, "Keine Beiträge vorhanden",
				"In dieser Gruppe sind noch keine Neuigkeiten vorhanden!");
	else
 		echo ActionReport(REPORT_INFO, "Keine Beiträge vorhanden", "Es sind noch keine Neuigkeiten vorhanden!");
}

?>
