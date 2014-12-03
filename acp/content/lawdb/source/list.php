<?php

 /*
 =====================================================
 Name ........: Gesetzesartikel Unternehmensbereiche
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
echo "<h1 class=\"first\">Quellen</h1>";
echo '<p><img src="img/icons/plugins/lawdb/new-source.png" alt="" />
		<a href="?page=lawdb-source-edit">Neue Quelle</a></p>';

/* Eintrag loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if (mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'lawdb_source
			WHERE id='.StdSqlSafety($_GET['delete']), DB_CMS)) {
		echo ActionReport(REPORT_OK, 'Quelle gelöscht',
				'Die Quelle wurde erfolgreich gelöscht!');
	}
	else {
		echo ActionReport(REPORT_ERROR, 'Fehler beim löschen',
				'Die Quelle konnte nicht gelöscht werden!<br />MySQL Fehler:'.mysql_error(DB_CMS));
	}
}

/* Datenbank lesen */
$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_source ORDER BY name ASC', DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);

if (mysql_num_rows($result)) {
	/* Ausgabe Tabellenkopf */
	echo '<table>';
	echo '<tr class="table_title"><td>Quelle</td><td></td><td></td></tr>';

	$i = 1;
	while ($row = mysql_fetch_array($result)) {
		if ($i++ & 0x01)
			echo '<tr class="table_odd">';
		else
			echo '<tr class="table_even">';
		
		/* Ausgabe */
		echo'<td><a href="'.$row['url'].'" target="_blank" onmouseover="Tip(\'Quelle öffnen\')" onmouseout="UnTip()">'.$row['name'].'</a></td>'
				.'<td class="icon"><a href="?page=lawdb-source-edit&amp;id='.$row['id'].'" onmouseover="Tip(\'Quelle bearbeiten\')" onmouseout="UnTip()"><img src="img/icons/plugins/lawdb/edit.png" alt="" /></a></td>'
				.'<td class="icon"><a href="javascript:loeschen(\'?page=lawdb-source-list&amp;delete='.$row['id'].'\', \'Wollen Sie wirklich diese Quelle unwiderruflich löschen?\')" onmouseover="Tip(\'Quelle löschen\')" onmouseout="UnTip()"><img src="img/icons/plugins/lawdb/delete.png" alt="" /></a></td>'
				.'</tr>';
	}
	
	/* Tabellenende */
	echo '</table>';
}
else {
	echo ActionReport(REPORT_INFO, 'Keine Quellen vorhanden',
			'Es sind noch keine Unternehmensbereiche vorhanden.');
}

?>