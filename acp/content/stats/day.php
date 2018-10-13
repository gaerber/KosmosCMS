<?php

 /*
 =====================================================
 Name ........: Statistik Uebersicht/Monat
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: statmonth.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 16.11.2012 | Program erstellt
 -----------------------------------------------------
 Beschreibung :
 Uebersicht der Statistik seit zaehlbeginn und 
 Monatsansicht.

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

if (isset($_GET['month']) && preg_match('/^(19|20)[\d]{2}[\-](0?[1-9]|1[012])$/', $_GET['month'])) {
	$month = explode('-', $_GET['month']);
	
	echo '<h1 class="first">Statistik vom '.$GlobalMonthsLAN[$month[1]-1].' '.$month[0].'</h1>';
	
	/* Maximum ermitteln */
	$result = Database::instance()->query('SELECT MAX(visitors) AS max, SUM(visitors) AS visitors, SUM(views) AS views,
			DATEDIFF(MAX(day), MIN(day)) AS days
			FROM '.DB_TABLE_PLUGIN.'stats_day
			WHERE YEAR(day)='.$month[0].' AND MONTH(day)='.$month[1])
			OR FatalError(FATAL_ERROR_MYSQL);
	if (($line = $result->fetch_assoc()) && $line['max'] > 0) {
		$max = $line['max'];
		$line['days'] += 1;
		
		/* Ausgabe Monatsuebersicht */
		echo printBoxStart();
		echo printBox('Monatsübersicht',
				'<span class="boxnumber">'.number_format($line['visitors'], 0, '.', '\'').'</span>
				Besucher<br />
				<span class="boxnumber">'.number_format($line['views'], 0, '.', '\'').'</span>
				Seitenansichten<br />
				<span class="boxnumber">'.$line['max'].'</span> Tagesmaximum<br />');
		echo printBox('Durchschnitte',
				'<span class="boxnumber">'.round($line['visitors'] / $line['days'], 2).'</span>
				Besucher pro Tag<br />
				<span class="boxnumber">'.round($line['views'] / $line['visitors'],2).'</span>
				Ansichten pro Besucher');
		echo printBoxEnd();
		
		/* Tabellen mit allen Tagen des Monats */
		echo '<p>&nbsp;</p>';
		echo '<table>';
		echo '<tr class="table_title">
				<td colspan="3">Datum</td>
				<td class="stats-number">Ansichten</td>
				<td class="stats-number">Besucher</td>
				<td></td>
				</tr>';
	
		$result = Database::instance()->query('SELECT DATE_FORMAT(day, "%w") AS weekday, DAY(day) AS day, visitors, views
				FROM '.DB_TABLE_PLUGIN.'stats_day WHERE YEAR(day)='.$month[0].' AND MONTH(day)='.$month[1].'
				ORDER BY day ASC')
				OR FatalError(FATAL_ERROR_MYSQL);
		
		$row_ctr = 1;
		while ($row = $result->fetch_assoc()) {
			if ($row_ctr++ % 2)
				echo '<tr class="table_odd">';
			else
				echo '<tr class="table_even">';
		
			$bar_length = $bar_length = $row['visitors']!=0 ? round(100 * $row['visitors'] / $max, 0) : 1;
		
			echo '<td class="stats-weekday">'.$GlobalWeekdaysLAN[$row['weekday']].'</td>
					<td class="stats-day">'.$row['day'].'</td>
					<td class="stats-month">'.$GlobalMonthsLAN[$month[1]-1].' '.$month[0].'</td>
					<td class="stats-number">'.$row['views'].'</td>
					<td class="stats-number">'.$row['visitors'].'</td>
					<td><span class="stats-bar" style=width:'.$bar_length.'%;"></span></td>';
			
			echo '</tr>';
		}
		
		echo '</table>';
	}
	else {
		/* Deine Daten gefunden fuer diesen Monat */
		echo ActionReport(REPORT_EINGABE, 'Nicht verfügbar',
				'Es sind noch keine Daten für diesen Monat in der Datenbank verfügbar!');
	}
}
else {
	echo '<h1 class="first">Monatsübersicht</h1>';
	echo ActionReport(REPORT_EINGABE, 'Fehler', 'Es wurde ein falscher Parameter übergeben!');
}




?>