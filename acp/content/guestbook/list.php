<?php

 /*
 =====================================================
 Name ........: Plugin: Gaestebuch
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
 |1.0     | 15.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Liste mit allen Gaestebucheintraegen.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_GUESTBOOK_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_GUESTBOOK, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 4;
///////////////////////////////////////////////////////

echo "<h1 class=\"first\">Gästebucheinträge</h1>";

/* Eintrag loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if (mysql_query("DELETE FROM ".DB_TABLE_PLUGIN."guestbook
			WHERE id=".StdSqlSafety($_GET['delete']), DB_CMS)) {
		echo ActionReport(REPORT_OK, "Eintrag gelöscht", "Der Eintrag wurde erfolgreich gelöscht!");
	}
	else {
		echo ActionReport(REPORT_ERROR, "Fehler beim löschen",
				"Der Eintrag konnte nicht gelöscht werden!<br />MySQL Fehler:".mysql_error(DB_CMS));
	}
}

$result = mysql_query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."guestbook", DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
$line = mysql_fetch_row($result);


if ($line[0] > 0) {
	/* Eintraege vorhanden */
	$classPagination = new pagination($line[0], isset($_GET[PAGE_POINTER])
			? $_GET[PAGE_POINTER] : 1, PAGINATION_PER_PAGE);
	
	$result = mysql_query("SELECT * FROM ".DB_TABLE_PLUGIN."guestbook ORDER BY timestamp DESC
			LIMIT ".$classPagination->Offset().",".PAGINATION_PER_PAGE, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	
	/* Ausgabe Liste */
	echo printBoxStart();
	
	while ($row = mysql_fetch_array($result)) {
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
		
		if ($row['admin_comment']) {
			$admin_info_name = "";
			$admin_info_email = "";
			getWriterInfo($row['admin_id'], $admin_info_name, $admin_info_email);
			$row['admin_comment'] = preg_replace("/^\<p\>/i", "<p>".$admin_info_name.": ",
					$row['admin_comment']);
		}
		
		echo printBox($row['writer_name'], $row['comment'], 
				"<a href=\"?page=guestbook-comment&amp;id=".$row['id']."\" onmouseover=\"Tip('Kommentieren')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/guestbook/comment.png\" alt=\"\" /></a>
				<a href=\"?page=guestbook-edit&amp;id=".$row['id']."\" onmouseover=\"Tip('Eintrag bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/guestbook/edit.png\" alt=\"\" /></a>
				<a href=\"javascript:loeschen('?page=guestbook-list&amp;delete=".$row['id']."', 'Wollen Sie diesen Eintrag wirklich löschen?')\" onmouseover=\"Tip('Eintrag löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/plugins/guestbook/delete.png\" alt=\"\" /></a>",
				$user_infos, $row['admin_comment'], ($_SESSION['admin_lastlogin'] < $row['timestamp']));
	}
	
	/* Ende der Ausgabe */
	echo printBoxEnd();
	
	echo "<p class=\"center\">";
	echo $classPagination->PaginationLinks("?page=guestbook-guestbook&amp;".PAGE_POINTER."=", PAGINATION_NUM);
	echo "</p>\r\n";
}
else {
	/* Gaestebunch hat keine Eintraege */
	echo ActionReport(REPORT_INFO, "Keine Einträge vorhanden", "Das Gästebuch enthält noch keine Einträge!");
}

?>