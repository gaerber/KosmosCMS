<?php

 /*
 =====================================================
 Name ........: Plugin: Kommentare Neuigkeiten
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
 |1.0     | 20.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Liste aller Kommentare einer Neuigkeit.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_NEWS_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_NEWS_COM, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 2;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_news\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_news\"";
///////////////////////////////////////////////////////

echo "<h1 class=\"first\">Neuigkeiten</h1>";

/* Kommaentare loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if (mysql_query("DELETE FROM ".DB_TABLE_PLUGIN."news_comment
			WHERE id=".StdSqlSafety($_GET['delete']), DB_CMS)) {
		echo ActionReport(REPORT_OK, "Kommentar gelöscht", "Der Kommentar wurde erfolgreich gelöscht!");
	}
	else {
		echo ActionReport(REPORT_ERROR, "Fehler beim löschen",
				"Der Kommentar konnte nicht gelöscht werden!<br />MySQL Fehler:".mysql_error(DB_CMS));
	}
}

/* ID pruefen */
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$news_id = (int) $_GET['id'];
	
	/* Neuigkeit Anzeigen */
	$result = mysql_query("SELECT id, caption, news_short, writer, timestamp
			FROM ".DB_TABLE_PLUGIN."news WHERE id=".$news_id, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	
	if ($line = mysql_fetch_array($result)) {
		/* Administrator Infos */
		$admin_info_name = "";
		$admin_info_email = "";
		getWriterInfo($line['writer'], $admin_info_name, $admin_info_email);
		
		/* Ausgabe */
		echo printBoxStart();
		echo printBox($line['caption'], $line['news_short'], 
				"<a href=\"?page=news-edit&amp;id=".$line['id']."\" onmouseover=\"Tip('Neuigkeit bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/edit.png\" alt=\"\" /></a>
				<a href=\"javascript:loeschen('?page=news-news&amp;delete=".$line['id']."', 'Wollen Sie diese Neuigkeit wirklich löschen?')\" onmouseover=\"Tip('Neuigkeit löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/delete.png\" alt=\"\" /></a>",
				printDate($line['timestamp'])." - ".$admin_info_name);
		echo printBoxEnd();
		
		/* Liste mit allen Kommentaren */
		echo "<h2>Kommentare</h2>";
		
		/* Anzahl Kommentare ermitteln*/
		$result = mysql_query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news_comment
				WHERE news_id=".$news_id, DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		$line = mysql_fetch_row($result);
		
		if ($line[0] > 0) {
			/* Kommentare vorhanden */
			$classPagination = new pagination($line[0], isset($_GET[PAGE_POINTER])
					? $_GET[PAGE_POINTER] : 1, PAGINATION_PER_PAGE);
			
			$result = mysql_query("SELECT * FROM ".DB_TABLE_PLUGIN."news_comment
					WHERE news_id=".$news_id." ORDER BY timestamp DESC
					LIMIT ".$classPagination->Offset().",".PAGINATION_PER_PAGE, DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			
			echo printBoxStart();
			
			while ($row = mysql_fetch_array($result)) {
				/* Ausgaben der Kommentare */
				/* Benutzerinfos bei registrierten Benutzer */
				if ($row['writer_id']) {
					$res = mysql_query("SELECT user_name, user_email, user_website
							FROM ".DB_TABLE_ROOT."cms_access_user
							WHERE user_id=".$row['writer_id'], DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);
					if ($line = mysql_fetch_array($res)) {
						/* Daten ueberschreiben */
						$row['writer_name'] = $line['user_name'];
						$row['writer_email'] = $line['user_email'];
						$row['writer_website'] = $line['user_website'];
					}
				}
				
				/* Benutzerinformationen */
				$user_infos = array();
				if ($row['writer_email'])
					$user_infos[] = $row['writer_email'];
				if ($row['writer_website'])
					$user_infos[] = $row['writer_website'];
				$user_infos[] = printDate($row['timestamp'])." ".date(FORMAT_TIME, $row['timestamp']);
				
				/* Ausgabe */
				echo printBox($row['writer_name'], $row['comment'], 
						"<a href=\"?page=news-comment-edit&amp;id=".$row['id']."\" onmouseover=\"Tip('Kommentar bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/comment/edit.png\" alt=\"\" /></a>
						<a href=\"javascript:loeschen('?page=news-comment-list&amp;id=".$news_id."&amp;delete=".$row['id']."', 'Wollen Sie diesen Kommentar wirklich löschen?')\" onmouseover=\"Tip('Kommentar löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/news/comment/delete.png\" alt=\"\" /></a>",
						$user_infos, NULL, ($_SESSION['admin_lastlogin'] < $row['timestamp']));
			}
			
			echo printBoxEnd();
			
			/* Seitenzahlen */
			echo "<p class=\"center\">";
			echo $classPagination->PaginationLinks("?page=news-comment-list&amp;id=".$news_id.
					"&amp;".PAGE_POINTER."=", PAGINATION_NUM);
			echo "</p>\r\n";
		}
		else {
			/* Keine Kommentare vorhanden */
			echo ActionReport(REPORT_INFO, "Keine Kommentare vorhanden",
					"Es sind noch keine Kommentare vorhanden!");

		}
	}
	else {
		/* Newsbeitrag existiert nicht */
		echo ActionReport(REPORT_EINGABE, "Neuigkeit existiert nicht",
				"Diese Neuigkeit existiert in der Datenbank nicht!");
	}
}
else {
	/* Eingabefehler */
	echo ActionReport(REPORT_EINGABE, "Eingabefehler",
			"Es wurde keine Identifikationsnummer übertragen!");
}

?>