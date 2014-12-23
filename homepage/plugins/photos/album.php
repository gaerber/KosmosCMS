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
 |2.0     | 14.11.2014 | Programm erstellt
 |2.1     | 07.12.2014 | Pruefung Zugriffsrechte
 -----------------------------------------------------
 Beschreibung :
 Plugin: Anzeigesoftware fuer saemtliche Fotos und 
 Alben.
 
 TODO:
 - Thumbnails nach generierung mit FTP auf Server
   laden.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_PHOTOS_EN) {
	
	/* FTP Verbindung aufbauen */
	$ftp = new ftp();
	
	/* Album selektieren */
	$current_path = $FileSystem_ModulePahts['photos'];
	if (isset($_GET[MODULE_PHOTOS_GETP_ALBUM]) && $_GET[MODULE_PHOTOS_GETP_ALBUM] != '') {
		$album = str_replace('_', '/', $_GET[MODULE_PHOTOS_GETP_ALBUM]);
		$album = ValidateFileSystem($album, '/');
		if (substr($album, strlen($album)-1, 1) != '/') {
			$album .= '/';
		}
		$current_path .= $album;	
	}
	else {
		$album = '';
	}
	
	/* Existiert dieses Album -> Album Informationen einlesen */
	if ($album_info = readAlbumConfig2($ftp, $current_path)) {
		/* Zugriffsrechte pruefen (rekursive) */
		$access = getRecursiveAlbumAccess($album_info['id']);
		if ($access['locked'] == 0) {
			/* Rechte pruefen */
			if (CheckAccess($access['access'])) {
				/* Album Titel */
				if ($album_info['id'] > 0) {
					$tpl = new tpl('plugins/photos/album_title');
					$tpl->assign($album_info);
					$tpl->out();
				}
				
				$element_ctr = 0;
				$release_time = 0;
				if (isset($album_info['timestamp'])) {
					$release_time = $album_info['timestamp'];
				}
				
				/* Anzeige der Sub Alben */
				$result = mysql_query('SELECT id_str FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE menu_sub='.$album_info['id'].' 
						&& locked=0 && '.CheckSQLAccess().' ORDER BY menu_order DESC', DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
						
				while ($row = mysql_fetch_assoc($result)) {
					/* Erst prüfen ob es ein valides Album ist */
					if ($album_info_sub = readAlbumConfig2($ftp, $current_path.$row['id_str'].'/')) {
						$element_ctr++;
						if ($release_time < $album_info_sub['timestamp']) {
							$release_time = $album_info_sub['timestamp'];
						}
						
						/* CSS Class first */
						if ($element_ctr == 1 && $album_info_sub['menu_sub'] == 0) {
							$album_info_sub['class_first'] = ' first';
						}
						else {
							$album_info_sub['class_first'] = '';
						}
						
						/* Anzahl Sub-Subalben ermitteln */
						$res = mysql_query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE menu_sub='.$album_info_sub['id'], DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);
						if ($l = mysql_fetch_array($res)) {
							$ctr_sub_albums = $l[0];
						}
						else {
							$ctr_sub_albums = 0;
						}
						
						/* Anzahl Sub-Subfotos ermitteln */
						$res = mysql_query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$album_info_sub['id'], DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);
						if ($l = mysql_fetch_array($res)) {
							$ctr_sub_photos = $l[0];
						}
						else {
							$ctr_sub_photos = 0;
						}
						
						/* Zufällige Anzeigebilder wählen */
						$arrayPreviewImg = array();
						if ($ctr_sub_photos > 0) {
							$res = mysql_query('SELECT file_name FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$album_info_sub['id'].'
									ORDER BY RAND() LIMIT '.MODULE_PHOTOS_ALBUMPREVIEWPICS, DB_CMS) OR FatalError(FATAL_ERROR_MYSQL);
							while ($r = mysql_fetch_assoc($res)) {
								$arrayPreviewImg[] = array('folder' => $album_info_sub['id_str'].'/', 'img' => $r['file_name']);
							}
						}
						else if ($ctr_sub_albums > 0) {
							$res = mysql_query('SELECT a.id, a.id_str, p.file_name FROM '.DB_TABLE_PLUGIN.'photoalbum AS a 
									LEFT JOIN '.DB_TABLE_PLUGIN.'photoalbum_photo AS p ON p.album_id=a.id
									WHERE a.menu_sub='.$album_info_sub['id'].' && a.locked=0 && '.CheckSQLAccess('a').'
									ORDER BY RAND() LIMIT '.MODULE_PHOTOS_ALBUMPREVIEWPICS, DB_CMS) OR FatalError(FATAL_ERROR_MYSQL);
							while ($r = mysql_fetch_assoc($res)) {
								$arrayPreviewImg[] = array('folder' => $album_info_sub['id_str'].'/'.$r['id_str'].'/', 'img' => $r['file_name']);
							}
						}
						
						$image_preview = '';
						for ($i = 0; $i < sizeof($arrayPreviewImg); $i++) {
							/* Vorschaubilder mit Templates */
							$tpl = new tpl('plugins/photos/album_imgpreview');
							if ($access['access'] || $album_info_sub['access']) {
								$tpl->assign('img_preview_thumb', '{root_website}download.php?path='
									.$current_path.$arrayPreviewImg[$i]['folder'].$arrayPreviewImg[$i]['img'].'&amp;thumb&amp;inline');
								$tpl->assign('img_preview', '{root_website}download.php?path='
									.$current_path.$arrayPreviewImg[$i]['folder'].$arrayPreviewImg[$i]['img'].'&amp;inline');
							}
							else {
								$tpl->assign('img_preview_thumb', FILESYSTEM_DIR.$current_path.$arrayPreviewImg[$i]['folder']
										.MODULE_PHOTOS_THUMB.$arrayPreviewImg[$i]['img']);
								$tpl->assign('img_preview', FILESYSTEM_DIR.$current_path.$arrayPreviewImg[$i]['folder']
										.$arrayPreviewImg[$i]['img']);
							}
							if ($i == sizeof($arrayPreviewImg)-1) {
								$tpl->assign('pos', ' last');
							}
							else {
								$tpl->assign('pos', '');
							}
							$image_preview .= $tpl->get();
						}
						
						
						/* Ausgabe */
						$tpl = new tpl('plugins/photos/album');
						$tpl->assign($album_info_sub);
						$tpl->assign('img_preview', $image_preview);
						$tpl->assign('url', '{module_path}/'.MODULE_PHOTOS_GETP_ALBUM.'/'
								.str_replace('/', '_', $album.$album_info_sub['id_str']));
						
						$small_nums = "";
						if($ctr_sub_albums==1)
							$small_nums = $ctr_sub_albums." Album ";
						if($ctr_sub_albums>1)
							$small_nums = $ctr_sub_albums." Alben ";
						if($ctr_sub_photos)
							$small_nums .= $ctr_sub_photos." Foto";
						if($ctr_sub_photos>1)
							$small_nums .= "s";
							
						$tpl->assign('num_albums', $ctr_sub_albums);
						$tpl->assign('num_photos', $ctr_sub_photos);
						$tpl->assign('num_infos', $small_nums);
	
						$tpl->out();
					}
				}
				
				/* Anzeige der Fotos */
				$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$album_info['id'].' ORDER BY file_name ASC', DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
		
				while ($row = mysql_fetch_assoc($result)) {
					/* Prüfen ob Datei existiert */
					if ($ftp->fileExists($current_path.$row['file_name'])) {
						$element_ctr++;
						if ($release_time < $row['timestamp']) {
							$release_time = $row['timestamp'];
						}
						
						/* Thumbnail muss existieren */
						if (!$ftp->fileExists($current_path.MODULE_PHOTOS_THUMB.$row['file_name'])) {
							/* Falls kein Thumbnailverzeichnis existiert, muss dieses erstellt werden */
							if (!$ftp->folderExists($current_path.MODULE_PHOTOS_THUMB) || $ftp->mkdir($current_path.MODULE_PHOTOS_THUMB)) {
								/* Thumbnail erstellen */
								ImageResizeFtp($ftp, $current_path.$row['file_name'], 
										$current_path.MODULE_PHOTOS_THUMB.$row['file_name'], $PliginPhotos_imagesSettings['height'], $PliginPhotos_imagesSettings['width'],
										$PliginPhotos_imagesSettings['proportional']);	
							}
						}
						/* Ausgabe der Fotos */
						$tpl = new tpl('plugins/photos/photo');
						$tpl->assign($row);
						
						if ($access['access'] || $album_info['access']) {
							/* Bilder koennen nicht direkt heruntergeladen werden */
							$tpl->assign('img_thumb', '{root_website}download.php?path='
									.$current_path.MODULE_PHOTOS_THUMB.$row['file_name'].'&amp;inline');
							$tpl->assign('img_big', '{root_website}download.php?path='
									.$current_path.$row['file_name'].'&amp;inline');
						}
						else {
							/* Oeffentliche Bilder */
							$tpl->assign('img_thumb', FILESYSTEM_DIR.$current_path.MODULE_PHOTOS_THUMB.$row['file_name']);
							$tpl->assign('img_big', FILESYSTEM_DIR.$current_path.$row['file_name']);
						}
						$tpl->out();
					}
					else {
						/* Bild aus Datenbank löschen */
						mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE id='.$row['id'].' AND album_id='.$album_info['id'], DB_CMS)
								OR FatalError(FATAL_ERROR_MYSQL);
					}
				}
				
				if ($element_ctr == 0) {
					/* Keine Alben und Fotos vorhanden */
					echo ActionReport(REPORT_INFO, 'Keine Fotos vorhanden',
							'In diesem Album sind noch keine Fotos vorhanden.');
				}
	
				/* Dateum der letzten aktualisierung setzten */
				$PluginContent['date'] = printDate($release_time);
	
			}
			else {
				/* Keine Berechtigung */
				echo ActionReport(REPORT_WARNING, 'Keine Berechtigung',
						'Sie müssen sich anmelden um dieses Album zu sehen.');
			}
		}
		else {
			/* Album-Ordner ist gesperrt */
			echo ActionReport(REPORT_EINGABE, 'Album nicht gefunden',
					'Das gewünschte Verzeichnis wurde nicht gefunden.');
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