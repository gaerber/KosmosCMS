<?php

 /*
 =====================================================
 Name ........: Liste aller Gruppen
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
 |1.0.1   | 28.10.2011 | Liste der Benutzer pro Gruppe
 -----------------------------------------------------
 Beschreibung :
 Erstellt eine Liste aller Gruppen der Benutzer.

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
echo "<h1 class=\"first\">Gruppen</h1>";

/*** Aktionen ****************************************/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	/* Zuweisung bei allen Benutzern loeschen */
	if (mysql_query("UPDATE ".DB_TABLE_ROOT."cms_access_user 
			SET user_access=(user_access & (~(1<<".StdSqlSafety($_GET['delete']).")))")) {
		/* Gruppe loeschen */
		if (mysql_query("DELETE FROM ".DB_TABLE_ROOT."cms_access_group
				WHERE id=".StdSqlSafety($_GET['delete']), DB_CMS)) {
			echo ActionReport(REPORT_OK, "Gruppe gelöscht", "Die Gruppe wurde erfolgreich gelöscht!");
		}
		else {
			echo ActionReport(REPORT_ERROR, "Fehler",
					"Die Gruppe konnte nicht gelöscht werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
		}
	}
	else {
		echo ActionReport(REPORT_ERROR, "Fehler",
				"Die Gruppe konnte nicht gelöscht werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
	}
}

/*** Links *******************************************/
echo "<p><img src=\"img/icons/user/group_add.png\" alt=\"\" />
		<a href=\"?page=user-group-edit\">Neue Gruppe</a></p>";

/*** Tabelle *****************************************/
$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
		ORDER BY name ASC", DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);

if (mysql_num_rows($result)) {
	echo "  <table>\r\n";
	echo "    <tr class=\"table_title\">
	      <td>Name</td>
	      <td>Mitglieder</td>
	      <td colspan=\"3\"></td>
	    </tr>\r\n";
	
	$row_ctr = 1;
	
	while ($row = mysql_fetch_array($result)) {
		if ($row_ctr++ % 2)
			echo "    <tr class=\"table_odd\">\r\n";
		else
			echo "    <tr class=\"table_even\">\r\n";
	
		echo "      <td>".$row['name']."</td>\r\n";
		/* Anzahl Mitglieder */
		$res = mysql_query("SELECT count(*) FROM ".DB_TABLE_ROOT."cms_access_user 
				WHERE (user_access & ".(1<<$row['id']).")");
		$line = mysql_fetch_array($res);
		echo "      <td>".$line[0]."</td>\r\n";
	
		/* Liste der Mitglieder */
		if ($line[0])
			echo "      <td class=\"icon\"><a href=\"?page=user-list&amp;group=".$row['id']."\" onmouseover=\"Tip('Benutzer dieser Gruppe anzeigen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/user.png\" alt=\"\" /></a></td>\r\n";
		else
			echo "      <td class=\"icon\"></td>\r\n";
	
		echo "      <td class=\"icon\"><a href=\"?page=user-group-edit&amp;id=".$row['id']."\" onmouseover=\"Tip('Gruppe bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/group_edit.png\" alt=\"\" /></a></td>\r\n";
	
		echo "      <td class=\"icon\"><a href=\"javascript:confirmDeletion('?page=user-group-list&amp;delete=".$row['id']."', 'Wollen Sie wirklich diese Gruppe löschen?')\" onmouseover=\"Tip('Gruppe löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/user/group_delete.png\" alt=\"\" /></a></td>\r\n";
	
		echo "    </tr>\r\n";
	}
	
	echo "  </table>";
}
else {
	echo ActionReport(REPORT_INFO, "Keine Gruppen vorhanden", "Es sind noch keine Gruppen vorhanden!");
}

?>