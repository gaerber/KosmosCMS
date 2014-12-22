<?php

 /*
 =====================================================
 Name ........: Liste aller Benutzer
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
 |1.0     | 06.09.2011 | Programm erstellt.
 |1.0.1   | 28.10.2011 | Modul Enable, Seitenzahlen.
 |1.1     | 29.04.2013 | Benutzerbilder
 -----------------------------------------------------
 Beschreibung :
 Erstellt eine Liste aller Benutzer.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_USER_SYSTEM_EN)		die();
ACP_AdminAccess(ACP_ACCESS_USER, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_user\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_user\"";
///////////////////////////////////////////////////////

/* Titel */
echo "<h1 class=\"first\">Benutzer</h1>";

/*** Aktionen ****************************************/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	$result = mysql_query('SELECT user_id_str FROM '.DB_TABLE_ROOT.'cms_access_user
			WHERE user_id='.StdSqlSafety($_GET['delete']), DB_CMS);
	if ($line = mysql_fetch_array($result)) {
		if (mysql_query("DELETE FROM ".DB_TABLE_ROOT."cms_access_user
				WHERE user_id=".StdSqlSafety($_GET['delete']), DB_CMS)) {
			/* Benutzerbild loeschen, falls vorhanden */
			if (is_array($UserSystem_imagesSettings)) {
				$ftp = new ftp();
				if ($ftp->fileExists($FileSystem_ModulePahts['user-system-images'].$line['user_id_str'].'.jpg')) {
					$ftp->ChangeDir($FileSystem_ModulePahts['user-system-images']);
					$ftp->Delete($line['user_id_str'].'.jpg');
				}
				$ftp->close();
			}
		
			echo ActionReport(REPORT_OK, "Benutzer gelöscht", "Der Benutzer wurde erfolgreich gelöscht!");
		}
		else {
			echo ActionReport(REPORT_ERROR, "Fehler",
					"Der Benutzer konnte nicht gelöscht werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, 'Benutzer existiert nicht', 'Dieser Benutzer wurde bereits gelöscht!');
	}
}

if (isset($_GET['locked']) && is_numeric($_GET['locked'])) {
	if (!mysql_query("UPDATE ".DB_TABLE_ROOT."cms_access_user SET user_locked=(!user_locked)
			WHERE user_id=".StdSqlSafety($_GET['locked']), DB_CMS)) {
		echo ActionReport(REPORT_ERROR, "Fehler",
				"Der Benutzer konnte nicht gesperrt/entsperrt werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
	}
}

/*** Links *******************************************/
echo "<p><img src=\"img/icons/user/user_add.png\" alt=\"\" />
		<a href=\"?page=user-edit\">Neuer Benutzer</a></p>";

/*** Filter ******************************************/
$filter_sql = "";
$filter_txt = "";
if (isset($_GET['group']) && is_numeric($_GET['group'])) {
	$result = mysql_query("SELECT name FROM ".DB_TABLE_ROOT."cms_access_group
			WHERE id=".(int)$_GET['group'], DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		$filter_sql = " WHERE (user_access & ". (1 << ((int)$_GET['group'])) .") ";
		echo ActionReport(REPORT_INFO, "Filter", "Alle Benutzer aus der Gruppe: ".$line['name']);
	}
}

/*** Tabelle *****************************************/
/* Anzahl Benutzer Ermitteln */
$result = mysql_query("SELECT count(*)
		FROM ".DB_TABLE_ROOT."cms_access_user ".$filter_sql, DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
$line = mysql_fetch_row($result);

if ($line[0] > 0) {
	/* Berechnung Seitenzahlen */
	$classPagination = new pagination($line[0], isset($_GET[PAGE_POINTER])
			? $_GET[PAGE_POINTER] : 1, PAGINATION_PER_PAGE_LINE);
	echo "  <table>
	    <tr class=\"table_title\">
	      <td>Name</td>";
	if (ACP_ACCESS_SYSTEM_EN)
		echo "      <td>Benutzernamen</td>
	      <td>Letzter Login</td>";
	
	echo "      <td colspan=\"3\"></td>
	    </tr>\r\n";
	
	$result = mysql_query("SELECT user_id, user_name, user_login, user_lastlogin, user_locked
			FROM ".DB_TABLE_ROOT."cms_access_user
			".$filter_sql."	ORDER BY user_name ASC
			LIMIT ".$classPagination->Offset().",".PAGINATION_PER_PAGE_LINE, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	
	$row_ctr = 1;
	
	while ($row = mysql_fetch_array($result)) {
		if ($row_ctr++ % 2)
			echo "    <tr class=\"table_odd\">\r\n";
		else
			echo "    <tr class=\"table_even\">\r\n";
	
		echo "      <td>".$row['user_name']."</td>\r\n";
		
		if (ACP_ACCESS_SYSTEM_EN) {
			echo "      <td>".$row['user_login']."</td>\r\n";
			if ($row['user_lastlogin'])
				echo "      <td>".date("d.m.Y H:i", $row['user_lastlogin'])."</td>\r\n";
			else
				echo "      <td>Nie</td>\r\n";
		}
	
	
		echo "      <td class=\"icon\"><a href=\"?page=user-edit&amp;id=".$row['user_id']."\" onmouseover=\"Tip('Benutzer bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/user_edit.png\" alt=\"\" /></a></td>\r\n";
		if ($row['user_locked'])
			echo "      <td class=\"icon\"><a href=\"?page=user-list&amp;".PAGE_POINTER."=".$classPagination->ActivePage()."&amp;locked=".$row['user_id']."\" onmouseover=\"Tip('Benutzer entsperren')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/locked.png\" alt=\"\" /></a></td>\r\n";
		else
			echo "      <td class=\"icon\"><a href=\"?page=user-list&amp;".PAGE_POINTER."=".$classPagination->ActivePage()."&amp;locked=".$row['user_id']."\" onmouseover=\"Tip('Benutzer sperren')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/locked_not.png\" alt=\"\" /></a></td>\r\n";
		echo "      <td class=\"icon\"><a href=\"javascript:loeschen('?page=user-list&amp;delete=".$row['user_id']."', 'Wollen Sie wirklich diesen Benutzer löschen?')\" onmouseover=\"Tip('Benutzer löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/user_delete.png\" alt=\"\" /></a></td>\r\n";
	
		echo "    </tr>\r\n";
	}
	
	echo "  </table>";
	
	/* Seitenzahlen Links */
	echo "<div class=\"pagination\">";
	if (isset($_GET['group']))
		echo $classPagination->PaginationLinks("?page=user-list&amp;group=".$_GET['group']
				."&amp;".PAGE_POINTER."=", PAGINATION_NUM);
	else
		echo $classPagination->PaginationLinks("?page=user-list&amp;".PAGE_POINTER."=", PAGINATION_NUM);
	echo "</div>\r\n";
}
else {
	if (isset($_GET['group']) && is_numeric($_GET['group']))
		echo ActionReport(REPORT_INFO, "Keine Benutzer vorhanden",
				"In dieser Gruppe sind noch keine Benutzer vorhanden!");
	else
 		echo ActionReport(REPORT_INFO, "Keine Benutzer vorhanden", "Es sind noch keine Benutzer vorhanden!");
}

?>