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
 |1.0.1   | 30.03.2013 | Bugfix Datum-Sortierung
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

echo '<h1 class="first">Besucherstatistik</h1>';

$stats_ctr = array();

/* Statistik starten ab dem ersten Tag, der in der Datenbank ist */
$result = Database::instance()->query('SELECT MIN(day) AS day_start, DATEDIFF(CURRENT_DATE, MIN(day)) AS days_online
		FROM '.DB_TABLE_PLUGIN.'stats_day')
		OR FatalError(FATAL_ERROR_MYSQL);
if ($line = $result->fetch_assoc()) {
	$stats_ctr['StatOnlineDays'] = $line['days_online'] + 1;
}
else {
	/* Statistik erst starten */
}

/* Besucher gesamt */
$result = Database::instance()->query('SELECT name, number FROM '.DB_TABLE_ROOT.'cms_register 
		WHERE name="Stats_CtrVisitors" OR name="Stats_CtrBots" OR name="Stats_CtrSpamBlock"')
		OR FatalError(FATAL_ERROR_MYSQL);
while ($row = $result->fetch_assoc()) {
	$stats_ctr[$row['name']] = $row['number'];
}

/* Seitenansichten gesamt */
$result = Database::instance()->query('SELECT SUM(views) FROM '.DB_TABLE_PLUGIN.'stats_views')
		OR FatalError(FATAL_ERROR_MYSQL);
$line = $result->fetch_row();
$stats_ctr['Stats_CtrViews'] = $line[0];

/* Besucher Online */
$result = Database::instance()->query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'stats_ip
		WHERE timestamp>='.(TIME_STAMP - 300))
		OR FatalError(FATAL_ERROR_MYSQL);
$line = $result->fetch_row();
$stats_ctr['Stats_CtrOnline'] = $line[0];

/* Ausgabe der Uebersicht */
$message1 = '<span class="boxnumber">'.number_format($stats_ctr['Stats_CtrVisitors'], 0, '.', '\'').'</span>
		Besucher gesamt<br />
		<span class="boxnumber">'.number_format($stats_ctr['Stats_CtrViews'], 0, '.', '\'').'</span>
		Seitenansichten gesamt<br />
		<span class="boxnumber">'.$stats_ctr['Stats_CtrOnline'].'</span> Besucher Online<br />
		<span class="boxnumber">'.number_format($stats_ctr['Stats_CtrBots'], 0, '.', '\'').'</span>
		Suchroboter';
if ($stats_ctr['Stats_CtrSpamBlock'])
	$message1 .= '<br />
		<span class="boxnumber">'.number_format($stats_ctr['Stats_CtrSpamBlock'], 0, '.', '\'').'</span>
		Abgewehrte Spamversuche';
$message2 = '<span class="boxnumber">'.round($stats_ctr['Stats_CtrVisitors'] / $stats_ctr['StatOnlineDays'], 2)
		.'</span> Besucher pro Tag<br />
		<span class="boxnumber">';
if ($stats_ctr['Stats_CtrVisitors'] != 0)
	$message2 .= round($stats_ctr['Stats_CtrViews'] / $stats_ctr['Stats_CtrVisitors'],2);
else
	$message2 .= '0';
$message2 .= '</span> Ansichten pro Besucher';

echo printBoxStart();
echo printBox('Übersicht', $message1);
echo printBox('Durchschnitte', $message2);
echo printBoxEnd();

/*** Tabelle Monatsstatistik *************************/

/* Maximum ermitteln */
$result = Database::instance()->query('SELECT SUM(visitors) FROM '.DB_TABLE_PLUGIN.'stats_day
		GROUP BY YEAR(day), MONTH(day)
		ORDER BY SUM(visitors) DESC LIMIT 0,1')
		OR FatalError(FATAL_ERROR_MYSQL);
if (($line = $result->fetch_row()) && $line[0] > 0) {
	$max = $line[0];
	
	echo '<p>&nbsp;</p>';
	echo '<table>';
	echo '<tr class="table_title">
			<td class="stats-month">Monat</td>
			<td class="stats-number">Ansichten</td>
			<td class="stats-number">Besucher</td>
			<td></td>
			</tr>';

	$result = Database::instance()->query('SELECT MONTH(day) AS month, YEAR(day) AS year, 
			SUM(visitors) AS visitors, SUM(views) AS views 
			FROM '.DB_TABLE_PLUGIN.'stats_day GROUP BY YEAR(day), MONTH(day)
			ORDER BY day DESC')
			OR FatalError(FATAL_ERROR_MYSQL);
	
	$row_ctr = 1;
	while ($row = $result->fetch_assoc()) {
		if ($row_ctr++ % 2)
			echo '<tr class="table_odd">';
		else
			echo '<tr class="table_even">';
	
		$bar_length = $bar_length = $row['visitors']!=0 ? round(100 * $row['visitors'] / $max, 0) : 1;
	
		echo '<td><a href="?page=stats-day&month='.$row['year'].'-'.$row['month'].'">
				'.$GlobalMonthsLAN[$row['month']-1].' '.$row['year'].'</a></td>
				<td class="stats-number">'.number_format($row['views'], 0, '.', '\'').'</td>
				<td class="stats-number">'.number_format($row['visitors'], 0, '.', '\'').'</td>
				<td><span class="stats-bar" style=width:'.$bar_length.'%;"></span></td>';
		
		echo '</tr>';
	}
	
	echo '</table>';
}

?>