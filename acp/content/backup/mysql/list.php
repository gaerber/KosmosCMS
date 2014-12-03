<?php

 /*
 =====================================================
 Name ........: MySQL Backup
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
 |1.0     | 31.03.2013 | Programm erstellt.
 |1.1     | 17.07.2013 | Auftrennung in Module.
 -----------------------------------------------------
 Beschreibung :
 Liste aller gemachten Backups.

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
echo "<h1 class=\"first\">Datenbank Sicherungskopien</h1>";

echo "<p><img src=\"img/icons/backup/mysql/save.png\" alt=\"\" />
		<a href=\"?page=backup-mysql-build\">Sicherungskopie erstellen</a></p>";


/* Liste mit den gespeicherten MySQL Backups erstelen */
$ftp = new ftp();
if ($folder_pointer = $ftp->openDir($FileSystem_ModulePahts['mysqlbackups'])) {
	/* Tabellemit Backups */
	echo '<table><tr class="table_title">
			<td colspan="2">Sicherungskopie</td>
			<td>Grösse</td>
			<td class="icon"></td><td class="icon"></td></tr>';

	$data_ctr = 1;
	
	/* Alle Dateien druchgehen */
	while($file = $folder_pointer->readDir()) {
		if ($data_ctr++ % 2)
			$table_hover = 'odd';
		else
			$table_hover = 'even';

		echo '<tr class="table_'.$table_hover.'">
		  <td class="icon"><img src="img/icons/backup/mysql/database.png" alt="" /></td>
		  <td>'.$file.'</td>
		  <td>'.BinaryMultiples($folder_pointer->fileSize($file)).'</td>
		  <td class="icon"><a href="../download.php?path='.$FileSystem_ModulePahts['mysqlbackups'].$file.'"
		  		onmouseover="Tip(\'Sicherungskopie herunterladen\')" onmouseout="UnTip()">
		  		<img src="img/icons/backup/mysql/download.png" alt="Umbenennen" /></a></td>
		  <td class="icon">
		  		<a href="?page=backup-mysql-restore&amp;backupfile='.$file.'"
				  onmouseover="Tip(\'Sicherungskopie wiederherstellen\')" onmouseout="UnTip()">
		  		<img src="img/icons/backup/mysql/restore.png" alt="" /></a></td>
		</tr>';
	}
	$ftp->closeDir($folder_pointer);
	echo '</table>';
}
else {
	echo ActionReport(REPORT_ERROR, 'Verzeichnis existiert nicht',
			'Es existiert kein Verzeichnis mit Datenbank Sicherungskopien!');
}

$ftp->close();

?>