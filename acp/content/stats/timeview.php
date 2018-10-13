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

echo '<h1 class="first">Top Zeiten</h1>';

/* Maximum ermitteln */
$result = Database::instance()->query('SELECT MAX(views) FROM '.DB_TABLE_PLUGIN.'stats_views')
		OR FatalError(FATAL_ERROR_MYSQL);
if (($line = $result->fetch_row()) && $line[0] > 0) {
	$max = $line[0];

	echo '<table>';
	echo '<tr class="table_title">
			<td style="width:220px;">Zeitraum</td>
			<td class="stats-number">Ansichten</td>
			<td></td>
			</tr>';
	
	$result = Database::instance()->query('SELECT hour, views
			FROM '.DB_TABLE_PLUGIN.'stats_views
			WHERE views != 0
			ORDER BY hour ASC')
			OR FatalError(FATAL_ERROR_MYSQL);
	
	$row_ctr = 0;
	while ($row = $result->fetch_assoc()) {
		if ($row_ctr++ % 2)
			echo '<tr class="table_even">';
		else
			echo '<tr class="table_odd">';
	
		$bar_length = $row['views']!=0 ? round(100 * $row['views'] / $max, 0) : 1;
		
		if ($row['hour'] < 10)
			$row['hour'] = '0'.$row['hour'];
	
		echo '<td>'.$row['hour'].':00 - '.$row['hour'].':59</td>
				<td class="stats-number">'.number_format($row['views'], 0, '.', '\'').'</td>
				<td><span class="stats-bar" style=width:'.$bar_length.'%;"></span></td>';
		
		echo '</tr>';
	}
	
	echo '</table>';
}
else {
	echo ActionReport(REPORT_INFO, 'Nicht verfügbar', 'Die Statistik wird erst mit dem ersten Besucher gestartet.');
}

?>