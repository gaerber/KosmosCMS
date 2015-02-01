<?php

 /*
 =====================================================
 Name ........: Gesetzesartikel Kategorien
 Projekt .....: Linkverzeichnis
 Datiename ...: list.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 13.08.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :

 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_LAWDB_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_LAWDB, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 20;
///////////////////////////////////////////////////////

/* Ueberschrift */
echo "<h1 class=\"first\">Kategorien</h1>";
echo '<p><img src="img/icons/plugins/lawdb/new-categorie.png" alt="" />
		<a href="?page=lawdb-categorie-edit">Neue Kategorie</a></p>';

/* Eintrag loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if (mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'lawdb_categorie
			WHERE id='.StdSqlSafety($_GET['delete']), DB_CMS)) {
		echo ActionReport(REPORT_OK, 'Kategorie gelöscht', 'Die Kategorie wurde erfolgreich gelöscht!');
	}
	else {
		echo ActionReport(REPORT_ERROR, 'Fehler beim löschen',
				'Die Kategorie konnte nicht gelöscht werden!<br />MySQL Fehler:'.mysql_error(DB_CMS));
	}
}

/* Datenbank lesen */
$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie ORDER BY name ASC', DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);

if (mysql_num_rows($result)) {
	/* Ausgabe Tabellenkopf */
	echo '<table>';
	echo '<tr class="table_title"><td>Kategorienamen</td><td></td><td></td></tr>';

	$i = 1;
	while ($row = mysql_fetch_array($result)) {
		if ($i++ & 0x01)
			echo '<tr class="table_odd">';
		else
			echo '<tr class="table_even">';
		
		/* Ausgabe */
		echo'<td>'.$row['name'].'</td>'
				.'<td class="icon"><a href="?page=lawdb-categorie-edit&amp;id='.$row['id'].'" onmouseover="Tip(\'Kategorie bearbeiten\')" onmouseout="UnTip()"><img src="img/icons/plugins/lawdb/edit.png" alt="" /></a></td>'
				.'<td class="icon"><a href="javascript:confirmDeletion(\'?page=lawdb-categorie-list&amp;delete='.$row['id'].'\', \'Wollen Sie wirklich diese Kategorie unwiderruflich löschen?\')" onmouseover="Tip(\'Kategorie löschen\')" onmouseout="UnTip()"><img src="img/icons/plugins/lawdb/delete.png" alt="" /></a></td>'
				.'</tr>';
	}
	
	/* Tabellenende */
	echo '</table>';
}
else {
	echo ActionReport(REPORT_INFO, 'Keine Kategorien vorhanden', 'Es sind noch keine Kategorien vorhanden.');
}

?>