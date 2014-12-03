<?php

 /*
 =====================================================
 Name ........: Plugin: Fotoalbum
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: album.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 22.02.2012 | Programm erstellt
 |1.1     | 26.02.2012 | Geschuetzte Alben
 |1.2     | 14.04.2012 | Unteralben mit '_' trennen
 |1.3     | 02.05.2012 | Vorschaubild sucht 2 Ebene
 -----------------------------------------------------
 Beschreibung :
 Plugin: Anzeigesoftware fuer saemtliche Fotos und 
 Alben.
 
 Bilder aus geschuetzten Alben muessen vom Benutzer
 mit dem Program photos.php geholt werden.
 
 TODO:
 - Thumbnails nach generierung mit FTP auf Server
   laden.
 - Unterstuetzen von Kommenatren bei .gif und .png

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_PHOTOS_EN) {
	
	/* Album selektieren */
	$current_album = $FileSystem_ModulePahts['photos'];
	if (isset($_GET[MODULE_PHOTOS_GETP_ALBUM])) {
		$album = ValidateFileSystem($_GET[MODULE_PHOTOS_GETP_ALBUM]).'_';
		$current_album .= str_replace('_', '/', $album);
	}
	else {
		$album = '';
	}
	
	/* FTP Verbindung aufbauen */
	$ftp = new ftp();
	
	/* Existiert diese Album */
	if ($ftp->folderExists($current_album)) {
		/* Album Informationen einlesen */
		$album_info = readAlbumConfigFtp($ftp, $current_album);
		/* Albumtitel muss beim Grundordner nicht vorhanden sein */
		if (($album_info && $album_info['locked'] == 0) || $album == '') {
			/* Benutzer rechte pruefen */
			if (!$album_info || CheckAccess($album_info['access'])) {
				/* Album Titel */
				if ($album_info) {
					$tpl = new tpl('plugins/photos/album_title');
					$tpl->assign($album_info);
					$tpl->out();
				}
				
				/* Liste aller Unteralben und Fotos erstellen */
				$a_albums = array();
				$a_photos = array();
				$release = readAlbumPhotosFtp($ftp, $current_album, $a_albums, $a_photos, true);
				
				/* Unteralben ausgeben */
				$a_size = sizeof($a_albums);
				for ($ctr=0; $ctr<$a_size; $ctr++) {
					if (($album_info_sub = readAlbumConfigFtp($ftp, $current_album.$a_albums[$ctr]))
							&& CheckAccess($album_info_sub['access'])
							&& $album_info_sub['locked'] == 0) {
						/* Berechnung der anzahl Fotos */
						$a_albums_sub = array();
						$a_photos_sub = array();
						$rel = readAlbumPhotosFtp($ftp, $current_album.$a_albums[$ctr], $a_albums_sub, $a_photos_sub);
						if ($release < $rel)
							$release = $rel;
						/* Ausgabe */
						$tpl = new tpl('plugins/photos/album');
						$tpl->assign($album_info_sub);
						$tpl->assign('url', '{module_path}/'.MODULE_PHOTOS_GETP_ALBUM.'/'
								.$album.substr($a_albums[$ctr], 0, -1));
						
						$ctr_albums = sizeof($a_albums_sub);
						$ctr_photos = sizeof($a_photos_sub);
						
						$small_nums = "";
						if($ctr_albums==1)
							$small_nums = $ctr_albums." Album ";
						if($ctr_albums>1)
							$small_nums = $ctr_albums." Alben ";
						if($ctr_photos)
							$small_nums .= $ctr_photos." Foto";
						if($ctr_photos>1)
							$small_nums .= "s";
							
						$tpl->assign('num_albums', $ctr_albums);
						$tpl->assign('num_photos', $ctr_photos);
						$tpl->assign('num_infos', $small_nums);
						
						/* Vorschaubild */
						$album_preview = '';
						if ($ctr_photos)
							$preview = $a_photos_sub[rand(0, $ctr_photos-1)];
						else if ($ctr_albums) {
							$a_albums_prev = array();
							$a_photos_prev = array();
							$album_preview = $a_albums_sub[rand(0, $ctr_albums-1)];
							readAlbumPhotosFtp($ftp, $current_album.$a_albums[$ctr].$album_preview,
									$a_albums_prev, $a_photos_prev);
							if (sizeof($a_photos_prev))
								$preview = $a_photos_prev[rand(0, sizeof($a_photos_prev)-1)];
							else
								$preview = 'none.jpg';
						}
						else {
							$preview = 'none.jpg';
						}
						
						if ($album_info_sub['access']) {
							$tpl->assign('img_preview_thumb', '{root_website}download.php?module=photos&amp;path='
								.$current_album.$a_albums[$ctr].$album_preview.$preview.'&amp;thumb&amp;inline');
							$tpl->assign('img_preview', '{root_website}download.php?module=photos&amp;path='
								.$current_album.$a_albums[$ctr].$album_preview.$preview.'&amp;inline');
						}
						else {
							$tpl->assign('img_preview_thumb', FILESYSTEM_DIR_V21.$current_album.$a_albums[$ctr]
									.$album_preview.MODULE_PHOTOS_THUMB.$preview);
							$tpl->assign('img_preview', FILESYSTEM_DIR_V21.$current_album.$a_albums[$ctr]
									.$album_preview.$preview);
						}
						$tpl->out();
					}
				}
				
				/* Fotos ausgeben */
				$a_size = sizeof($a_photos);
				for ($ctr=0; $ctr<$a_size; $ctr++) {
					/* Bild-Titel auslesen (Nur JPG moeglich!) */
					if (exif_imagetype('upload'.$current_album.$a_photos[$ctr]) == IMAGETYPE_JPEG) {
						$image_header = exif_read_data('upload'.$current_album.$a_photos[$ctr]);
						if ($image_header && isset($image_header['ImageDescription']))
							$image_description = $image_header['ImageDescription'];
						else
							$image_description = '';
					}
					else {
						$image_description = '';
					}
					
					/* Thumbnail muss existieren */
					if (false && !file_exists($current_album.MODULE_PHOTOS_THUMB.$a_photos[$ctr])) {
						/* Existiert der Ordner */
						if (!file_exists($current_album.MODULE_PHOTOS_THUMB)) {
							$ftp = new ftp();
							$ftp->ChangeDir(substr($current_album, strlen(FILESYSTEM_DIR)));
							if (!$ftp->mkdir(MODULE_PHOTOS_THUMB))
								FatalError(FATAL_ERROR_FILE);
							$ftp->chmod(MODULE_PHOTOS_THUMB, 0777);	//Bugfix Thumb erstellen
							$ftp->close();
						}
						
						/* Thumbnail erstellen */
						ImageResize($current_album.$a_photos[$ctr], 
								$current_album.MODULE_PHOTOS_THUMB.$a_photos[$ctr],
								100, 100, false);
					}
					
					$tpl = new tpl('plugins/photos/photo');
					$tpl->assign('description', $image_description);
					
					if ($album_info['access']) {
						/* Bilder koennen nicht direkt heruntergeladen werden */
						$tpl->assign('img_thumb', '{root_website}download.php?module=photos&amp;path='
								.$current_album.$a_photos[$ctr].'&amp;thumb&amp;inline');
						$tpl->assign('img_big', '{root_website}download.php?module=photos&amp;path='
								.$current_album.$a_photos[$ctr].'&amp;inline');
					}
					else {
						/* Oeffentliche Bilder */
						$tpl->assign('img_thumb', FILESYSTEM_DIR_V21.$current_album.MODULE_PHOTOS_THUMB
								.$a_photos[$ctr]);
						$tpl->assign('img_big', FILESYSTEM_DIR_V21.$current_album.$a_photos[$ctr]);
					}
					$tpl->out();
				}
				
				if (!sizeof($a_albums) && !sizeof($a_photos)) {
					/* Keine Alben und Fotos vorhanden */
					echo ActionReport(REPORT_INFO, "Keine Fotos vorhanden",
							"In diesem Album sind noch keine Fotos vorhanden.");
				}
				else {
					$PluginContent['date'] = printDate($release);
				}
			}
			else {
				/* Keine Berechtigung */
				echo ActionReport(REPORT_WARNING, "Keine Berechtigung",
						"Sie müssen sich anmelden um dieses Album zu sehen.");
			}
		}
		else {
			/* Ordner ist kein gueltiges Album */
			echo ActionReport(REPORT_EINGABE, 'Album nicht gefunden',
					'Das gewünschte Album wurde nicht gefunden.');
		}
	}
	else {
		/* Album-Ordner existiert nicht */
		echo ActionReport(REPORT_EINGABE, 'Album nicht gefunden',
				'Das gewünschte Verzeichnis wurde nicht gefunden.');
	}
	
	/* FTP Verbindung beenden */
	$ftp->close();
}
?>