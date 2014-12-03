<?php

 /*
 =====================================================
 Name ........: Fotoalbum
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: albums.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 11.09.2012 | Program erstellt
 -----------------------------------------------------
 Beschreibung :
 Tabellarische Liste aller Alben.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_PHOTOS_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_PHOTOS, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 5;
///////////////////////////////////////////////////////

echo '<h1 class="first">Fotoalbum</h1>';

/* Rekursive Funktion */
function generatePhotoList($ftp, $default_folder, $current_album, $level=0, $line_ctr=1) {

	/* Liste aller Unteralben und Fotos erstellen */
	$a_albums = array();
	$a_photos = array();
	//readAlbumPhotos('../upload'.$default_folder.$current_album, $a_albums, $a_photos, true);
	readAlbumPhotosFtp($ftp, $default_folder.$current_album, $a_albums, $a_photos, true);
	$a_size = sizeof($a_albums);
	
	/* Liste aller Unteralben erstellen */
	for ($ctr=0; $ctr<$a_size; $ctr++) {
		if ($album_info_sub = readAlbumConfigFtp($ftp, $default_folder.$current_album.$a_albums[$ctr])) {
			/* Tabellenzeile */
			if ($line_ctr++ % 2)
				echo '<tr class="table_odd">';
			else
				echo '<tr class="table_even">';
			
			/* Sortierung */
			if ($ctr > 0)
				echo '<td class="icon"><a href="?page=photos-albums&amp;do=up&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'" onmouseover="Tip(\'Ein Element nach oben\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/up.png" alt="" /></a></td>';
			else
				echo '<td class="icon"></td>';
			if ($ctr < ($a_size-1))
				echo '<td class="icon"><a href="?page=photos-albums&amp;do=down&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'" onmouseover="Tip(\'Ein Element nach unten\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/down.png" alt="" /></a></td>';
			else
				echo '<td class="icon"></td>';
			
			/* Albumnamen */
			echo '<td>'.str_repeat('&nbsp;', 4 * $level).'<a href="?page=photos-photos-list&amp;album='.$current_album.$a_albums[$ctr].'">'.$album_info_sub['title'].'</a></td>';
			
			/* Anzahl Fotos */
			$a_albums_sub = array();
			$a_photos_sub = array();
			readAlbumPhotosFtp($ftp, $default_folder.$current_album.$a_albums[$ctr], $a_albums_sub, $a_photos_sub);
			
			echo '<td>';
			echo sizeof($a_photos_sub) == 1 ? '1 Foto' : sizeof($a_photos_sub).' Fotos';
			echo '</td>';
			
			/* Icons */
			echo '<td class="icon"><a href="?page=photos-album-edit&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'" onmouseover="Tip(\'Album bearbeiten\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/album_edit.png" alt="" /></a></td>';
			echo '<td class="icon"><a href="?page=photos-albums&amp;do=thumb&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'" onmouseover="Tip(\'Thumbnails neu generieren\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/thumb.png" alt="" /></a></td>';
			/* Album Sperren */
			if ($album_info_sub['locked'])
				echo '<td class="icon"><a href="?page=photos-albums&amp;do=locked&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'" onmouseover="Tip(\'Album entsperren\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/locked.png" alt="" /></a></td>';
			else
				echo '<td class="icon"><a href="?page=photos-albums&amp;do=locked&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'" onmouseover="Tip(\'Album sperren\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/locked_not.png" alt="" /></a></td>';
			/* Album loeschen */
			echo '<td class="icon"><a href="javascript:loeschen(\'?page=photos-albums&amp;do=delete&amp;folder='.$current_album.'&amp;album='.$a_albums[$ctr].'\', \'Wollen Sie dieses Album mit sämtlichen Fotos und Unteralben wirklich unwiderruflich löschen?\')" onmouseover="Tip(\'Album löschen\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/delete.png" alt="" /></a></td>';
				
			echo "</tr>\r\n";				
				
			/* Nach weiteren Unteralben suchen */
			$level++;
			$line_ctr = generatePhotoList($ftp, $default_folder, $current_album.$a_albums[$ctr], $level, $line_ctr);
			$level--;	
		}
	}
	
	return $line_ctr;
}

/*** Links *******************************************/
echo '<p><img src="img/icons/plugins/photos/album_add.png" alt="" />
		<a href="?page=photos-album-edit">Neues Album</a></p>';

/* FTP Verbindung aufbauen */
$ftp = new ftp();
	
/*** Manipulationen **********************************/
if (isset($_GET['do'], $_GET['folder'], $_GET['album'])
		&& $ftp->folderExists($FileSystem_ModulePahts['photos'].$_GET['folder'])) {
	/* Pruefen von Paramterfehlern */
	$change_folder_ftp = $FileSystem_ModulePahts['photos'].$_GET['folder'];
	
	/* Aktionen bei den Alben */
	switch ($_GET['do']) {
		/* Sortierung aendern */
		case 'up':
		case 'down':
			/* Liste aller Unteralben und Fotos erstellen */
			$a_albums = array();
			$a_photos = array();
			readAlbumPhotosFtp($ftp, $change_folder_ftp, $a_albums, $a_photos, true);
	
			$key_select = -1;
			$key = array_search($_GET['album'], $a_albums);
			if ($key !== NULL) {
				$album_change = readAlbumConfigFtp($ftp, $change_folder_ftp.$a_albums[$key]);
				$album_select = NULL;
				if ($_GET['do'] == 'up') {
					if ($key > 0)
						$key_select = $key - 1;
					else
						/* Ist schon zu oberst */
						echo ActionReport(REPORT_EINGABE, 'Nicht möglich',
								'Dieses Album befindet sich schon an erster Stelle.');
				}
				else {
					if ($key < sizeof($a_albums)-1)
						$key_select = $key + 1;
					else
						/* Ist schon zu unterst */
						echo ActionReport(REPORT_EINGABE, 'Nicht möglich',
								'Dieses Album befindet sich schon an letzter Stelle.');
				}
				
				if ($key_select >= 0)
					$album_select = readAlbumConfigFtp($ftp, $change_folder_ftp.$a_albums[$key_select]);
	
				if ($album_change && $album_select) {
					/* Sortierung tauschen */
					$temp = $album_change['sort'];
					$album_change['sort'] = $album_select['sort'];
					$album_select['sort'] = $temp;
					/* Neue Configs abspeichern */
					if (!(writeAlbumConfigFtp($ftp, $change_folder_ftp.$a_albums[$key], $album_change)
							&& writeAlbumConfigFtp($ftp, $change_folder_ftp.$a_albums[$key_select], $album_select))) {
						/* Fehler beim vertauschen */
						echo ActionReport(REPORT_ERROR,' Fehler', 'Die Änderung konnte nicht gespeichert werden.');
					}
	 			}
			}
			else {
				/* Parameterfehler: Album existiert nicht mehr. */
				echo ActionReport(REPORT_EINGABE, 'Album existiert nicht', 'Dieses Album existiert nicht mehr!');
			}
			break;
	
		
		/* Thumbnails neu Generieren */
		case 'thumb':
			if (readAlbumConfigFtp($ftp, $change_folder_ftp.$_GET['album'])) {
				$ftp->ChangeDir($change_folder_ftp.$_GET['album']);
				if ((!$ftp->fileExists($change_folder_ftp.$_GET['album'].MODULE_PHOTOS_THUMB) 
						|| $ftp->rmdir(MODULE_PHOTOS_THUMB))
							&& $ftp->mkdir(MODULE_PHOTOS_THUMB)
							&& $ftp->chmod(MODULE_PHOTOS_THUMB, 0777)) {
					$a_albums = array();
					$a_photos = array();
					readAlbumPhotosFtp($ftp, $change_folder_ftp.$_GET['album'], $a_albums, $a_photos);
					
					$bool = true;
					$a_size = sizeof($a_photos);
					for ($ctr=0; $ctr<$a_size; $ctr++) {
						/* Thumbnail erstellen */
						$bool &= ImageResizeFtp($ftp, $change_folder_ftp.$_GET['album'].$a_photos[$ctr], 
								$change_folder_ftp.$_GET['album'].MODULE_PHOTOS_THUMB.$a_photos[$ctr],
								100, 100, false);
					}
					if ($bool)
						echo ActionReport(REPORT_OK, 'Thumbnails generiert',
								'Es wurden '.$ctr.' Thumbnails generiert.');
					else
						echo ActionReport(REPORT_ERROR, 'Fehler',
								'Es konnten nicht alle Thumbnails erstellt werden.');
				}
				else {
					/* Tumb Order nicht bereinigt */
					echo ActionReport(REPORT_ERROR, 'Fehler', 'Der Thumbnail Ordner konnte nicht bereinigt werden.');
				}
			}
			else {
				/* Kein gueltiges Album */
				echo ActionReport(REPORT_EINGABE, 'Ungültiges Album', 'Dieses Album ist nicht gültig!');
			}
			break;
			
		/* Album sperren */
		case 'locked':
			if ($album_change = readAlbumConfigFtp($ftp, $change_folder_ftp.$_GET['album'])) {
				$album_change['locked'] = $album_change['locked'] ? '0' : '1';
				/* Neue Configs abspeichern */
				if (!writeAlbumConfigFtp($ftp, $change_folder_ftp.$_GET['album'], $album_change)) {
					/* Nicht moeglich */
					echo ActionReport(REPORT_ERROR,' Fehler', 'Die Änderung konnte nicht gespeichert werden.');
				}
			}
			else {
				/* Kein gueltiges Album */
				echo ActionReport(REPORT_EINGABE, 'Ungültiges Album', 'Dieses Album ist nicht gültig!');
			}
			break;
	
		/* Album loeschen */
		case 'delete':
			if (readAlbumConfigFtp($ftp, $change_folder_ftp.$_GET['album'])) {
				$ftp->ChangeDir($change_folder_ftp);
				if ($ftp->rmdir($_GET['album'])) {
					echo ActionReport(REPORT_OK, 'Album gelöscht', 'Das Album wurde erfolgreich gelöscht.');
				}
				else {
					/* Konnte Ordner nicht loeschen */
					echo ActionReport(REPORT_ERROR, 'Fehler', 'Album konnte nicht gelöscht werden.');
				}
			}
			else {
				/* Kein gueltiges Album */
				echo ActionReport(REPORT_EINGABE, 'Ungültiges Album', 'Dieses Album ist nicht gültig!');
			}
			break;
	}
}


/*** Tabelle *****************************************/
echo '  <table>
		<tr class="table_title">
			<td colspan="2"></td>
			<td>Albumtitel</td>
			<td>Anz. Fotos</td>
			<td colspan="4"></td>
		</tr>';

/* Lister aller Alben */
$Anfangszeit = getMicrotime();
generatePhotoList($ftp, $FileSystem_ModulePahts['photos'], '');

echo '</table>';

echo number_format(getMicrotime()-$Anfangszeit, 4, ",", ".");

/* FTP Verbindung schliessen */
$ftp->close();

?>