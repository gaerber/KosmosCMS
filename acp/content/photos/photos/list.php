<?php

 /*
 =====================================================
 Name ........: Fotoalbum Fotouebersicht
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
 |1.0     | 12.09.2012 | Program erstellt
 |2.0     | 13.12.2012 | FTP Dateisystem
 -----------------------------------------------------
 Beschreibung :
 Liste aller Fotos in einem bestimmten Album.

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

echo "<h1 class=\"first\">Fotoalbum</h1>";

/* FTP Verbindung aufbauen */
$ftp = new ftp();

if (isset($_GET['album']) 
		&& $album_info = readAlbumConfigFtp($ftp, $FileSystem_ModulePahts['photos'].$_GET['album'])) {

	$current_album = $FileSystem_ModulePahts['photos'].$_GET['album'];

	/* Foto loeschen */
	if (isset($_GET['delete'])) {
		if ($ftp->fileExists($current_album.$_GET['delete'])) {
			/* Foto und Thumbnail loeschen */
			$ftp->ChangeDir($current_album);
			if ($ftp->Delete($_GET['delete'])) {
				$ftp->ChangeDir($current_album.MODULE_PHOTOS_THUMB);
				if ($ftp->Delete($_GET['delete'])) {
					echo ActionReport(REPORT_OK, 'Foto gelöscht', 'Das Foto wurde erfolgreich gelöscht.');
				}
				else {
					/* Konnte Foto nicht loeschen */
					echo ActionReport(REPORT_ERROR, 'Fehler', 'Das Thumbnail konnte nicht gelöscht werden.');
				}
			}
			else {
				/* Konnte Foto nicht loeschen */
				echo ActionReport(REPORT_ERROR, 'Fehler', 'Das Foto konnte nicht gelöscht werden.');
			}
		}
		else {
			/* Foto existiert nicht hehr */
			echo ActionReport(REPORT_EINGABE, 'Foto existiert nicht', 'Das Foto existiert nicht mehr.');
		}
	}

	/* Liste alle Fotos erstellen */
	$a_albums = array();
	$a_photos = array();
	readAlbumPhotosFtp($ftp, $current_album, $a_albums, $a_photos, true);
	
	$ctr_albums = sizeof($a_albums);
	$ctr_photos = sizeof($a_photos);
	
	$small_nums = "";
	if($ctr_albums==1)
		$small_nums = $ctr_albums." Album ";
	if($ctr_albums>1)
		$small_nums = $ctr_albums." Alben ";
	if($ctr_photos)
		$small_nums .= $ctr_photos." Foto";
	if($ctr_photos>1)
		$small_nums .= "s";
	
	/* Informationen zum Album */
	echo ActionReport(REPORT_INFO, 'Album: '.$album_info['title'], 
			$album_info['description'].'<p>'.$small_nums.'</p>');
	
	/* Links */
	echo '<p><img src="img/icons/plugins/photos/image_add.png" alt="" />
			<a href="?page=photos-photos-upload&amp;album='.$_GET['album'].'">Foto hochladen</a></p>';
	
	for ($ctr=0; $ctr < $ctr_photos; $ctr++) {
		/* Thumbnail muss existieren */
		if (!$ftp->fileExists($current_album.MODULE_PHOTOS_THUMB.$a_photos[$ctr])) {
			/* Existiert der Ordner */
			if (!$ftp->folderExists($current_album.MODULE_PHOTOS_THUMB)) {
				$ftp->ChangeDir($current_album);
				if (!$ftp->mkdir(MODULE_PHOTOS_THUMB))
					FatalError(FATAL_ERROR_FILE);
				$ftp->chmod(MODULE_PHOTOS_THUMB, 0777);	//Bugfix Thumb erstellen
			}
			
			/* Thumbnail erstellen */
			ImageResize($current_album.$a_photos[$ctr], 
					$current_album.MODULE_PHOTOS_THUMB.$a_photos[$ctr],
					100, 100, false);
		}
		
		/* Bild-Titel auslesen (Nur JPG moeglich!) */
/* Kommentar ausgeschaltet*/		if (0 && exif_imagetype($current_album.$a_photos[$ctr]) == IMAGETYPE_JPEG) {
			$image_header = exif_read_data($current_album.$a_photos[$ctr]);
			if ($image_header && isset($image_header['ImageDescription']))
				$image_description = 'onmouseover="Tip(\''.$image_header['ImageDescription'].'\')"
						onmouseout="UnTip()" ';
			else
				$image_description = 'onmouseover="Tip(\'Keine Beschreibung\')"
						onmouseout="UnTip()" ';
		}
		else {
			$image_description = '';
		}
		
		/* Bildausgabe */
		echo '<div class="photo"><p>';
/* Fotokommentar ausschalten */		if (0 && $image_description != '') {
			echo '<a href="?page=photos-photos-edit&amp;album='.$_GET['album'].'&amp;photo='.$a_photos[$ctr].'"
					onmouseover="Tip(\'Foto kommentieren\')" onmouseout="UnTip()">
					<img src="img/icons/plugins/photos/image_edit.png" alt="" /></a>';
		}
		echo '<a href="?page=photos-photos-list&amp;album='.$_GET['album'].'&amp;delete='.$a_photos[$ctr].'"
				onmouseover="Tip(\'Foto löschen\')" onmouseout="UnTip()">'
				.'<img src="img/icons/plugins/photos/image_delete.png" alt="" /></a></p>';
		if ($album_info['access'] > 0 || $album_info['locked']) {
			/* Geschuetzte Bider ausgeben */
			echo '<img src="../download.php?module=photos&amp;path='.$_GET['album'].$a_photos[$ctr]
					.'&amp;thumb&amp;inline" 
					alt="" '.$image_description.'/>';
		}
		else {
			/* Normale Bilderausgabe */
			echo '<img src="'.FILESYSTEM_DIR_V21.$current_album.MODULE_PHOTOS_THUMB
					.$a_photos[$ctr].'" alt="" '.$image_description.'/>';
		}
		echo '</div>';
	}
	echo '<p class="photo-clear"></p>';
}
else {
	/* Album-Ordner existiert nicht */
	echo ActionReport(REPORT_EINGABE, 'Album nicht gefunden',
			'Das gewünschte Album wurde nicht gefunden.');
}

/* FTP Verbindung schliessen */
$ftp->close();

?>