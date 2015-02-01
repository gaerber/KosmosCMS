<?php

 /*
 =====================================================
 Name ........: Liste Gesetzesartikel
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
 |1.0     | 08.07.2012 | Programm erstellt
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
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_lawdb\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_lawdb\"";
///////////////////////////////////////////////////////

/* Filtereinstellungen */
if (!isset($sql_filter) || !isset($sql_order)) {
	echo '<h1 class="first">Gesetzesartikel</h1>';
	//echo '<p><img src="img/icons/plugins/lawdb/filter.png" alt="" />
	//		<a href="?page=lawdb-filter">Filter</a></p>';
	$sql_filter = '';
	$sql_order = 'ORDER BY abbr ASC';
}

/* Eintrag loeschen */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	if (mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'lawdb_list
			WHERE id='.StdSqlSafety($_GET['delete']), DB_CMS)) {
		echo ActionReport(REPORT_OK, 'Eintrag gelöscht', 'Der Gesetzesartikel wurde erfolgreich gelöscht!');
	}
	else {
		echo ActionReport(REPORT_ERROR, 'Fehler beim löschen',
				'Der Gesetzesartikel konnte nicht gelöscht werden!<br />MySQL Fehler:'.mysql_error(DB_CMS));
	}
}

/* Elemente zaehlen */
$result = mysql_query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'lawdb_list '.$sql_filter, DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
$line = mysql_fetch_row($result);

if ($line[0] > 0) {
	/* Eintraege vorhanden */
	$classPagination = new pagination($line[0], isset($_GET[PAGE_POINTER])
			? $_GET[PAGE_POINTER] : 1, PAGINATION_PER_PAGE_LINE);
	
	$result = mysql_query('SELECT id, abbr, caption, url FROM '.DB_TABLE_PLUGIN.'lawdb_list '
			.$sql_filter.' '.$sql_order.'
			LIMIT '.$classPagination->Offset().','.PAGINATION_PER_PAGE_LINE, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
			
	/* Ausgabe Tabellenkopf */
	echo '<table>';
	echo '<tr class="table_title"><td>Abkürzung</td><td>Titel</td><td></td><td></td></tr>';
	
	$i = 1;
	while ($row = mysql_fetch_array($result)) {
		if ($i++ & 0x01)
			echo '<tr class="table_odd">';
		else
			echo '<tr class="table_even">';
		
		/* Ausgabe */
		echo'<td><a href="'.$row['url'].'" target="_blank" onmouseover="Tip(\'Gesetzesartikel öffnen\')" onmouseout="UnTip()">'.$row['abbr'].'</a></td><td>'.$row['caption'].'</td>'
				.'<td class="icon"><a href="?page=lawdb-edit&amp;id='.$row['id'].'" onmouseover="Tip(\'Gesetzesartikel bearbeiten\')" onmouseout="UnTip()"><img src="img/icons/plugins/lawdb/edit.png" alt="" /></a></td>'
				.'<td class="icon"><a href="javascript:confirmDeletion(\'?page=lawdb-list&amp;delete='.$row['id'].'\', \'Wollen Sie wirklich diesen Gesetzesartikel unwiderruflich löschen?\')" onmouseover="Tip(\'Gesetzesartikel löschen\')" onmouseout="UnTip()"><img src="img/icons/plugins/lawdb/delete.png" alt="" /></a></td>'
				.'</tr>';
	}
	
	/* Tabellenende */
	echo '</table>';
	
	/* Seitenzahlen */
	echo '<div class="pagination">';
	echo $classPagination->PaginationLinks('?page=lawdb-list&amp;'.PAGE_POINTER.'=', PAGINATION_NUM);
	echo '</div>';
}
else {
	/* Es sind keine Gesetzesartikel vorhanden */
	echo ActionReport(REPORT_INFO, 'Keine Einträge vorhanden', 'Es sind keine Gesetzesartikel
			in der Datenbank vorhanden!');
}

?>