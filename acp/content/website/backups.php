<?php

 /*
 =====================================================
 Name ........: Backupsystem
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: backups.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 12.07.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Auflisten von allen Backups und Wiederherstellung.

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

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$result = Database::instance()->query("SELECT id, label FROM ".DB_TABLE_ROOT."cms_menu
			WHERE menu_is_categorie=0 && id=".StdSqlSafety($_GET['id']))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		/* Titel */
		echo "<h1 class=\"first\">Backups der Seite ".$line['label']."</h1>\r\n";

		/* Bakups wiederherstellen */
		if (isset($_GET['backup']) && is_numeric($_GET['backup'])) {
			$result = Database::instance()->query("SELECT html FROM ".DB_TABLE_ROOT."cms_content
					WHERE page_id=".$line['id']." && id=".StdSqlSafety($_GET['backup']))
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line_backup = $result->fetch_assoc()) {
				/* Wiederherstellen */
				if (Database::instance()->query("INSERT INTO ".DB_TABLE_ROOT."cms_content(page_id, html, writer, timestamp)
						VALUES(".$line['id'].", '".$line_backup['html']."', ".$_SESSION['admin_id'].",
						".TIME_STAMP.")")) {
					echo ActionReport(REPORT_OK, "Backup wiederhergestellt",
							"Das Backup wurde erfolgreich wiederhergestellt!");
				}
				else {
					echo ActionReport(REPORT_ERROR, "Wiederherstellungsfehler",
							"Das Backup konnte nicht wiederhergestellt werden!<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
				}
			}
			else {
				/* Backup nicht gefunden */
				echo ActionReport(REPORT_EINGABE, "Backup nicht gefunden",
						"Das Bakup wurde in der Datenbank nicht gefunden!");
			}
		}

		/* Backups holen mit Seitenzahlen */
		$result = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_ROOT."cms_content
				WHERE page_id=".$line['id'])
				OR FatalError(FATAL_ERROR_MYSQL);
		$count = $result->fetch_row();
		if ($count[0]) {
			$c_pages = new pagination($count[0], isset($_GET['subpage']) ? $_GET['subpage'] : 1, 20);
			$result = Database::instance()->query("SELECT id, writer, timestamp FROM ".DB_TABLE_ROOT."cms_content
					WHERE page_id=".$line['id']."
					ORDER BY timestamp DESC LIMIT ".$c_pages->Offset().",".PAGINATION_PER_PAGE_LINE)
					OR FatalError(FATAL_ERROR_MYSQL);

			/* Tabellenkopf */
			echo "<table>";
			echo "<tr class=\"table_title\"><td>Backup Nr.</td><td>Datum</td><td>Autor</td><td colspan=\"2\"></td></tr>\r\n";

			$row_ctr = 1;
			$backup_nr = $c_pages->FirstNumber('down');
			$writer_name = "";
			$writer_email = "";

			while ($row = $result->fetch_assoc()) {
				if ($row_ctr++ % 2)
					echo "<tr class=\"table_odd\">";
				else
					echo "<tr class=\"table_even\">";
				getWriterInfo($row['writer'], $writer_name, $writer_email);
				echo "<td>".$backup_nr--."</td><td>".date("d.m.Y H:i", $row['timestamp'])."</td><td>".$writer_name."</td>
						<td class=\"icon\"><a href=\"#\" onclick=\"javascript:MyWindow=window.open('frame.php?page=backup-preview&amp;id=".$row['id']."','','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=400'); return false;\" onmouseover=\"Tip('Backupvorschau')\" onmouseout=\"UnTip()\"><img src=\"img/icons/backup/content/preview.png\" alt=\"\" /></a></td>";
				if (($row_ctr > 2) || ($c_pages->Offset() != 0))
					echo "<td class=\"icon\"><a href=\"?page=website-backups&amp;id=".$line['id']."&amp;backup=".$row['id']."\" onmouseover=\"Tip('Backup wiederherstellen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/backup/content/restore.png\" alt=\"\" /></a></td>";
				else
					echo "<td class=\"icon\"></td>";
				echo "</tr>\r\n";
			}

			echo "</table>";

			if ($c_pages->NumberOfPage()) {
				echo "<p>&nbsp;</p><div class=\"pagination\">";
				echo $c_pages->PaginationLinks("?page=website-backups&amp;id=".$line['id']."&amp;subpage=",
						PAGINATION_NUM);
				echo "</div>";
			}
		}
		else {
			/* Keine Backups gefunden */
			echo ActionReport(REPORT_ERROR, "Keine Backups", "Es wurden keine Backups gefunden!");
		}
	}
	else {
		/* Seite nicht gefunden */
		echo "<h1 class=\"first\">Backups</h1>\r\n";
		echo ActionReport(REPORT_EINGABE, "Seite nicht gefunden", "Die Seite wurde nicht gefunden!");
	}
}
else {
	/* Keine Seiten-ID uebertragen */
	echo "<h1 class=\"first\">Backups</h1>\r\n";
	echo ActionReport(REPORT_EINGABE, "Eingabefehler", "Es wurde keine Identifikationsnummer einer Seite übertragen!");
}

?>
