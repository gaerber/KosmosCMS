<?php

 /*
 =====================================================
 Name ........: Statistik Uebersicht/Monat
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: page.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 18.11.2012 | Program erstellt
 -----------------------------------------------------
 Beschreibung :
 Statistik ueber allen Seiten.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_STATISTIC)		die();
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = 'style="display:none" id="secondmenu_stats"';
$ACP_ApplicationInfo['menu_replace'] = 'id="secondmenu_stats"';
///////////////////////////////////////////////////////

echo '<h1 class="first">Top Seiten</h1>';

/* Maximum ermitteln */
$result = mysql_query('SELECT MAX(views) FROM '.DB_TABLE_PLUGIN.'stats_page', DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
if (($line = mysql_fetch_row($result)) && $line[0] > 0) {
	$max = $line[0];

	echo '<table>';
	echo '<tr class="table_title">
			<td class="stats-day">#</td>
			<td style="width:220px;">Seitennamen</td>
			<td class="stats-number">Ansichten</td>
			<td></td>
			</tr>';
	
	$result = mysql_query('SELECT label, visitors, views
			FROM '.DB_TABLE_PLUGIN.'stats_page AS db_page
			LEFT JOIN '.DB_TABLE_ROOT.'cms_menu AS db_menu ON db_menu.id = db_page.page_id
			ORDER BY views DESC LIMIT 50', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	
	$row_ctr = 0;
	while ($row = mysql_fetch_assoc($result)) {
		if ($row_ctr++ % 2)
			echo '<tr class="table_even">';
		else
			echo '<tr class="table_odd">';
	
		echo '<td class="stats-day">'.$row_ctr.'</td>
				<td>'.$row['label'].'</td>
				<td class="stats-number">'.number_format($row['views'], 0, '.', '\'').'</td>
				<td><span class="stats-bar" style=width:'.(round(100 * $row['views'] / $max, 0)).'%;"></span></td>';
		
		echo '</tr>';
	}
	
	echo '</table>';
}
else {
	echo ActionReport(REPORT_INFO, 'Nicht verfügbar', 'Die Statistik wird erst mit dem ersten Besucher gestartet.');
}

?>