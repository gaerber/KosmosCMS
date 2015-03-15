<?php

 /*
 =====================================================
 Name ........: Liste aller Administratoren
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
 |1.0     | 12.07.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Erstellt eine Liste aller Administratoren.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_ADMIN, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_setting\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_setting\"";
///////////////////////////////////////////////////////

/* Titel */
echo "<h1 class=\"first\">Administratoren</h1>";

/* Admin loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if ($_GET['delete'] != 1) {
		if ($_SESSION['admin_id'] != $_GET['delete']) {
			if (mysql_query("UPDATE ".DB_TABLE_ROOT."cms_admin SET login='', locked=1
					WHERE admin_id=".StdSqlSafety($_GET['delete']), DB_CMS)) {
				echo ActionReport(REPORT_OK, "Administrator gelöscht", "Der Administrator wurde erfolgreich gelöscht!");
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Der Administrator konnte nicht gelöscht werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, "Nicht möglich", "Es ist nicht möglich sich selbst zu löschen!");
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Nicht möglich", "Der Errichter darf nicht gesperrt werden! Stattdessen können Sie diesen sperren.");
	}
}

/* Errichter bearbeiten */
if (isset($_GET['raiser'])) {
	if ($_SESSION['admin_id'] != 1) {
		if ($_GET['raiser'] == 'unlock') {
			if (mysql_query("UPDATE ".DB_TABLE_ROOT."cms_admin SET locked=0 WHERE admin_id=1", DB_CMS)) {
				echo ActionReport(REPORT_OK, "Errichter freigegeben", "Der Errichter wurde erfolgreich freigegeben! Mit seinem bisherigen Passwort kann er sich nun im ACP anmelden.");
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Der Errichter konnte nicht freigegeben werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
			}
		}
		else if ($_GET['raiser'] == 'lock') {
			if (mysql_query("UPDATE ".DB_TABLE_ROOT."cms_admin SET locked=1 WHERE admin_id=1", DB_CMS)) {
				echo ActionReport(REPORT_OK, "Errichter gesperrt", "Der Errichter wurde erfolgreich gesperrt!");
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Der Errichter konnte nicht gesperrt werden!<br />MySQL Fehler: ".mysql_error(DB_CMS));
			}
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, "Nicht möglich", "Es ist nicht möglich sich selbst zu sperren!");
	}
}

/* Admin hinzufuegen */
echo "<p><img src=\"img/icons/admin/user_add.png\" alt=\"\" />
		<a href=\"?page=admin-edit\">Neuer Administrator</a></p>";

/* Tabelle */
echo "  <table>\r\n";
echo "    <tr class=\"table_title\">
      <td>Name</td>
      <td>Letzter Login</td>
      <td colspan=\"2\"></td>
    </tr>\r\n";

$row_ctr = 1;

$result = mysql_query("SELECT admin_id, name, last_login, locked
		FROM ".DB_TABLE_ROOT."cms_admin
		WHERE locked=0 OR admin_id=1 ORDER BY name ASC", DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($result)) {
	if ($row_ctr++ % 2)
		echo "    <tr class=\"table_odd\">\r\n";
	else
		echo "    <tr class=\"table_even\">\r\n";

	echo "      <td>".$row['name']."</td>\r\n";
	if ($row['last_login'])
		echo "      <td>".date("d.m.Y H:i", $row['last_login'])."</td>\r\n";
	else
		echo "      <td>Noch nie</td>\r\n";


	echo "      <td class=\"icon\"><a href=\"?page=admin-edit&amp;id=".$row['admin_id']."\" onmouseover=\"Tip('Administrator bearbeiten')\" onmouseout=\"UnTip()\"><img src=\"img/icons/admin/user_edit.png\" alt=\"\" /></a></td>\r\n";
	if ($_SESSION['admin_id'] != $row['admin_id']) {
		/* Andere Behandlung beim Errichter */
		if ($row['admin_id'] == 1) {
			if ($row['locked'] == 1) {
				echo "      <td class=\"icon\"><a href=\"?page=admin-list&amp;raiser=unlock\" onmouseover=\"Tip('Errichter freigeben')\" onmouseout=\"UnTip()\"><img src=\"img/icons/admin/raiser_locked.png\" alt=\"\" /></a></td>\r\n";
			}
			else {
				echo "      <td class=\"icon\"><a href=\"?page=admin-list&amp;raiser=lock\" onmouseover=\"Tip('Errichter sperren')\" onmouseout=\"UnTip()\"><img src=\"img/icons/admin/raiser_unlocked.png\" alt=\"\" /></a></td>\r\n";
			}
		}
		else {
			echo "      <td class=\"icon\"><a href=\"javascript:confirmDeletion('?page=admin-list&amp;delete=".$row['admin_id']."', 'Wollen Sie wirklich diesen Administrator löschen?')\" onmouseover=\"Tip('Administrator löschen')\" onmouseout=\"UnTip()\"><img src=\"img/icons/admin/user_delete.png\" alt=\"\" /></a></td>\r\n";
		}
	}
	else {
		echo "      <td class=\"icon\"></td>\r\n";
	}

	echo "    </tr>\r\n";
}

echo "  </table>";

?>