<?php

 /*
 =====================================================
 Name ........: Fotoalbum
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: show.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 04.10.2014 | Program erstellt
 |2.1     | 07.12.2014 | Bugfix Thumbnail Anzeige
 |2.1.1   | 07.01.2015 | Bugfix Fotokommentar
 -----------------------------------------------------
 Beschreibung :
 Anzeige der Alben und Fotos eines bestimmten
 Verzeichnisses.

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

/* FTP Verbindung aufbauen */
$ftp = new ftp();

/* Album selektieren */
$current_path = $FileSystem_ModulePahts['photos'];
if (isset($_GET['album']) && $_GET['album'] != '') {
	$album = ValidateFileSystem($_GET['album'], '/');
	if (substr($album, strlen($album)-1, 1) != '/') {
		$album .= '/';
	}
	$current_path .= $album;	
}
else {
	$album = '';
}


/* Existiert dieses Album */
if ($current_album = readAlbumConfig2($ftp, $current_path)) {
	/*** Informationen des aktuellen Albums **************/
	if ($current_album['id'] > 0) {
		$a = explode('/', $album);
		array_pop($a);array_pop($a);

		$icons = array(
				array('icon' => 'img/icons/plugins/photos/return.png', 'url' => '?page=photos-show&album='.implode('/', $a), 'comment' => 'Übergeordnetes Fotoalbum'),
				array('icon' => 'img/icons/plugins/photos/thumb.png', 'url' => '?page=photos-show&amp;do=thumb-album&amp;album='.implode('/', $a).'&amp;id='.$current_album['id'], 'comment' => 'Thumbnails neu generieren'),
				array('icon' => 'img/icons/plugins/photos/album_edit.png', 'url' => '?page=photos-album-edit&amp;album='.$album, 'comment' => 'Album '.$current_album['caption'].' bearbeiten')
		);
		echo printInfoBox('Album: '.$current_album['caption'], $current_album['description'], $icons);
	}
	
	/*** Links *******************************************/
	echo '<p><img src="img/icons/plugins/photos/album_add.png" alt="" />
			<a href="?page=photos-album-edit&amp;album='.$album.'&amp;new"">Neues Album</a>';
	if ($current_album['id'] > 0) {
		echo ' &nbsp; | &nbsp; ';
		echo '<img src="img/icons/plugins/photos/image_add.png" alt="" />
				<a href="?page=photos-photo-upload&amp;album='.$album.'">Fotos hochladen</a></p>';
	}
	
	/*** Aktionen ****************************************/
	if (isset($_GET['do']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
		switch ($_GET['do']) {
			case 'up-album':
			case 'down-album':
				$res_element = mysql_query('SELECT id, menu_sub, menu_order
						FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id='.StdSqlSafety($_GET['id']).' && menu_sub='.$current_album['id'], DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
							
				if ($line_element = mysql_fetch_assoc($res_element)) {
					if ($_GET['do'] == 'up-album')
						$res_change = mysql_query('SELECT id, menu_order FROM '.DB_TABLE_PLUGIN.'photoalbum
								WHERE menu_sub='.$line_element['menu_sub'].'
								&& menu_order>'.$line_element['menu_order'].'
								ORDER BY menu_order ASC LIMIT 1', DB_CMS)
								OR FatalError(FATAL_ERROR_MYSQL);
					else
						$res_change = mysql_query('SELECT id, menu_order FROM '.DB_TABLE_PLUGIN.'photoalbum
								WHERE menu_sub='.$line_element['menu_sub'].'
								&& menu_order<'.$line_element['menu_order'].'
								ORDER BY menu_order DESC LIMIT 1', DB_CMS)
								OR FatalError(FATAL_ERROR_MYSQL);
					if ($line_change = mysql_fetch_assoc($res_change)) {
						/* Tauschen von menu_order */
						if (!(mysql_query('UPDATE '.DB_TABLE_PLUGIN.'photoalbum SET menu_order='.$line_change['menu_order'].'
								WHERE id='.$line_element['id'], DB_CMS)
								&& mysql_query('UPDATE '.DB_TABLE_PLUGIN.'photoalbum SET menu_order='.$line_element['menu_order'].'
								WHERE id='.$line_change['id'], DB_CMS))) {
							echo ActionReport(REPORT_ERROR, 'Datenbank Fehler', mysql_error());
						}
					}
					else {
						if ($_GET['do'] == 'up-album')
							echo ActionReport(REPORT_EINGABE, 'Fehler',
									'Das Album befindet sich bereits an oberster Stelle!');
						else
							echo ActionReport(REPORT_EINGABE, 'Fehler',
									'Das Album befindet sich bereits an unterster Stelle!');
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Nicht gefunden',
							'Das gewählte Album wurde in der Datenbank nicht gefunden!');
				}
				break;
				
			case 'thumb-album':
				$res_element = mysql_query('SELECT id, id_str
						FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id='.StdSqlSafety($_GET['id']).' && menu_sub='.$current_album['id'], DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
							
				if ($line_element = mysql_fetch_assoc($res_element)) {
					/* Verzeichnis des Albums muss existieren */
					if ($ftp->folderExists($current_path.$line_element['id_str'])) {
						/* Alle Thumbnails werden gelöschen */
						$ftp->ChangeDir($current_path.$line_element['id_str']);
						if ((!$ftp->folderExists(MODULE_PHOTOS_THUMB) 
								|| $ftp->rmdir(MODULE_PHOTOS_THUMB))
									&& $ftp->mkdir(MODULE_PHOTOS_THUMB)) {
							$ctr_thumb = 0;
							$ctr_mysql = 0;
							/* Verzeichnis das durchsucht werden soll, wird geoeffnet */
							$folder_pointer = $ftp->openDir($current_path.$line_element['id_str']);
							while($file = $folder_pointer->readDir()) {
								if (!$folder_pointer->isDir($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)),
											$FileSystem_AllowedImageTypes)) {
									/* Foto gefunden => Thumbnail erstellen */
									if (ImageResizeFtp($ftp, $current_path.$line_element['id_str'].'/'.$file, 
											$current_path.$line_element['id_str'].'/'.MODULE_PHOTOS_THUMB.$file,
											$PluginPhotos_imagesSettings['height'], $PluginPhotos_imagesSettings['width'],
											$PluginPhotos_imagesSettings['proportional'])) {
										$ctr_thumb++;
									}
									/* Prüfen ob das Foto in der Datenbank ist -> Sonst wird es eingefügt */
									$res_change = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'photoalbum_photo 
											WHERE album_id='.$line_element['id'].' && file_name="'.$file.'"', DB_CMS)
											OR FatalError(FATAL_ERROR_MYSQL);
									if (mysql_num_rows($res_change) == 0) {
										/* Fotokommentar und Zeitpunkt der Aufnahme */
										$comment = '';
										$filetime = 0;
										/* Bild-Titel auslesen (Nur JPG moeglich!) */
										/* Daten in eine temporaere Datei packen (auf dem lokalen Server) */
										$image_temp = tempnam(FILESYSTEM_TEMP, 'img');
										$image_temp_resource = fopen($image_temp, 'rw+');
										if ($image_temp_resource 
												&& $ftp->FileRead($current_path.$line_element['id_str'].'/'.$file, $image_temp_resource)) {
											fclose($image_temp_resource);
											$image_header = @exif_read_data($image_temp);
											if ($image_header && isset($image_header['ImageDescription'])) {
												$comment = preg_replace("/^ +(.*) +$/", "$1", $image_header['ImageDescription']);
											}
											if ($image_header && isset($image_header['FileDateTime'])) {
											$filetime = $image_header['FileDateTime'];
											}
										}

										/* Bild in Datenbank aufnehmen */
										if (mysql_query('INSERT INTO '.DB_TABLE_PLUGIN.'photoalbum_photo(file_name, album_id, file_timestamp, caption, writer, timestamp)
												VALUES("'.$file.'", "'.$line_element['id'].'", '.$filetime.', "'.StdSqlSafety($comment).'", 
												'.$_SESSION['admin_id'].', '.TIME_STAMP.')', DB_CMS)) {
											$ctr_mysql++;
										}
									}
								}
							}
							$ftp->closeDir($folder_pointer);
							/* Generierung fertig */
							if ($ctr_mysql) {
								echo ActionReport(REPORT_OK, 'Fotos hinzugefügt', 'Es wurden '.$ctr_mysql.' neue Fotos hinzugefügt und '.$ctr_thumb.' Thumbnails neu erstellt.');
							}
							else {
								echo ActionReport(REPORT_OK, 'Thumbnails erstellt', 'Es wurden '.$ctr_thumb.' Thumbnails neu erstellt.');
							}
						}	
						else {
							echo ActionReport(REPORT_ERROR, 'Dateisystem', 'Das Verzeichnis für die Thumbnails konnte nicht bereinigt werden.');
						}	
					}
					else {
						echo ActionReport(REPORT_ERROR, 'Verzeichnis',
								'Das Verzeichnis des Albums existiert nicht mehr!');
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Nicht gefunden',
							'Das gewählte Album wurde in der Datenbank nicht gefunden!');
				}		
				break;
				
			case 'lock-album':
			case 'unlock-album':
				$res_element = mysql_query('SELECT id, id_str, access
						FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id='.StdSqlSafety($_GET['id']).' && menu_sub='.$current_album['id'], DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
							
				if ($line_element = mysql_fetch_assoc($res_element)) {
					$locking = 1;
					if ($_GET['do'] == 'unlock-album') {
						$locking = 0;
					}
					/* Verzeichnis des Albums muss existieren */
					if ($ftp->folderExists($current_path.$line_element['id_str'])) {
						/* Verzeichnisschutz */
						$ftp->ChangeDir($current_path.$line_element['id_str']);
						if ($locking){
							if ($ftemp_htaccess = tmpfile()) {
								/* Verzeichnisschutz erstellen */
								fwrite($ftemp_htaccess, "Order deny,allow\r\nDeny from all");
								fseek($ftemp_htaccess, 0);
								/* Hochladen */
								$ftp->FilePut('.htaccess', $ftemp_htaccess);
								fclose($ftemp_htaccess);
							}
							else {
								echo ActionReport(REPORT_ERROR, 'Verzeichnisschutz nicht aktiv', 'Der Verzeichnisschutz des Albums konnte nicht erstellt werden. Alle Fotos sind öffentlich zugänglich.');
							}
						}
						else if ($ftp->fileExists('.htaccess')) {
							/* Verzeichnisschutz muss entfernt werden, sofern er vohanden ist */
							$ftp->Delete('.htaccess');
						}
						/* Die Config Datei speichern */
						$config = array(
								'module' => 'photos',
								'album_id' => $line_element['id'],
								'access' => $line_element['access'],
								'locked' => $locking
								);
						$ftp->writeFolderConfig($current_path.$line_element['id_str'], $config);
						
						/* Abspeichern in der Datenbank */
						if (!mysql_query('UPDATE '.DB_TABLE_PLUGIN.'photoalbum SET locked='.$locking.'
								WHERE id='.$line_element['id'], DB_CMS)) {
							echo ActionReport(REPORT_ERROR, 'Datenbank Fehler', mysql_error());
						}
					}
					else {
						echo ActionReport(REPORT_ERROR, 'Verzeichnis',
								'Das Verzeichnis des Albums existiert nicht mehr!');
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Nicht gefunden',
							'Das gewählte Album wurde in der Datenbank nicht gefunden!');
				}
				break;

			case 'delete-album':
				/* Album aus Datenbank ermitteln */
				$res_element = mysql_query('SELECT id, id_str FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id='.StdSqlSafety($_GET['id']).' AND menu_sub='.$current_album['id'], DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
						
				if ($line_element = mysql_fetch_assoc($res_element)) {
					if (mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE id='.$line_element['id'].' AND menu_sub='.$current_album['id'], DB_CMS)
							&& mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$line_element['id'], DB_CMS)) {
						if ($ftp->folderExists($current_path.$line_element['id_str'])) {
							$ftp->ChangeDir($current_path);
							if ($ftp->rmdir($line_element['id_str'])) {
								echo ActionReport(REPORT_OK, 'Album gelöscht', 'Das Album wurde erfolgreich gelöscht.');
							}
							else {
								/* Konnte Ordner nicht loeschen */
								echo ActionReport(REPORT_ERROR, 'Fehler', 'Album konnte nicht vom Dateisystem gelöscht werden.');
							}
						}
						else {
							/* Album existiert nicht hehr */
							echo ActionReport(REPORT_EINGABE, 'Album existiert nicht', 'Das Album existiert im Dateisystem nicht mehr.');
						}
					}
					else {
						echo ActionReport(REPORT_ERROR, 'Fehler', 'Datenbankeinträge konnten nicht gelöscht werden! MySQL: '.mysql_error(DB_CMS));
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Album existiert nicht', 'Das Album existiert nicht mehr.');
				}
				break;
			case 'delete-photo':
				/* Foto aus Datenbank ermitteln */
				$res_element = mysql_query('SELECT id, file_name FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE id='.StdSqlSafety($_GET['id'])
						.' AND album_id='.$current_album['id'], DB_CMS)
						OR FatalError(FATAL_ERROR_MYSQL);
						
				if ($line_element = mysql_fetch_assoc($res_element)) {
					if (mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE id='.$line_element['id'].' AND album_id='.$current_album['id'], DB_CMS)) {
						if ($ftp->fileExists($current_path.$line_element['file_name'])) {
							/* Foto und Thumbnail loeschen */
							$ftp->ChangeDir($current_path);
							if ($ftp->Delete($line_element['file_name'])) {
								$ftp->ChangeDir($current_path.MODULE_PHOTOS_THUMB);
								if ($ftp->Delete($line_element['file_name'])) {
									echo ActionReport(REPORT_OK, 'Foto gelöscht', 'Das Foto wurde erfolgreich gelöscht.');
								}
								else {
									/* Konnte Foto nicht loeschen */
									echo ActionReport(REPORT_ERROR, 'Fehler', 'Das Thumbnail konnte nicht vom Dateisystem gelöscht werden.');
								}
							}
							else {
								/* Konnte Foto nicht loeschen */
								echo ActionReport(REPORT_ERROR, 'Fehler', 'Das Foto konnte nicht vom Dateisystem gelöscht werden.');
							}
						}
						else {
							/* Foto existiert nicht hehr */
							echo ActionReport(REPORT_EINGABE, 'Foto existiert nicht', 'Das Foto existiert im Dateisystem nicht mehr.');
						}
					}
					else {
						echo ActionReport(REPORT_ERROR, 'Fehler', 'Datenbankeintrag konnte nicht gelöscht werden! MySQL: '.mysql_error(DB_CMS));
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Foto existiert nicht', 'Das Foto existiert nicht mehr.');
				}
				break;
		}
	}
	
	/*** Anzeige der Sub Alben ***************************/
	$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE menu_sub='.$current_album['id'].' ORDER BY menu_order DESC', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
			
	$line_ctr = 0;
	/* Tabellen darstellung nur wenn auch Unteralben vorhanden sind */
	if (mysql_num_rows($result)) {
		echo '  <table>
				<tr class="table_title">
					<td colspan="2"></td>
					<td>Unteralben</td>
					<td>Anz. Alben</td>
					<td>Anz. Fotos</td>
					<td colspan="4"></td>
				</tr>';
			
		while ($row = mysql_fetch_assoc($result)) {
			/* Erst prüfen ob es ein valides Album ist */
			if (readAlbumConfig2($ftp, $current_path.$row['id_str'].'/')) {
				/* Anzahl Sub-Subalben ermitteln */
				$res = mysql_query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE menu_sub='.$row['id'], DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
				if ($l = mysql_fetch_array($res)) {
					$ctr_sub_albums = $l[0];
				}
				else {
					$ctr_sub_albums = 0;
				}
				
				/* Anzahl Sub-Subfotos ermitteln */
				$res = mysql_query('SELECT count(*) FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$row['id'], DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
				if ($l = mysql_fetch_array($res)) {
					$ctr_sub_photos = $l[0];
				}
				else {
					$ctr_sub_photos = 0;
				}
				
				/* Anzeige Tabellenzeile*/
				if ($line_ctr++ % 2)
					echo '<tr class="table_odd">';
				else
					echo '<tr class="table_even">';
				
				/* Sortierung */
				if ($line_ctr > 1)
					echo '<td class="icon"><a href="?page=photos-show&amp;do=up-album&amp;album='.$album.'&amp;id='.$row['id'].'" onmouseover="Tip(\'Ein Element nach oben\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/up.png" alt="" /></a></td>';
				else
					echo '<td class="icon"></td>';
				if ($line_ctr < mysql_num_rows($result))
					echo '<td class="icon"><a href="?page=photos-show&amp;do=down-album&amp;album='.$album.'&amp;id='.$row['id'].'" onmouseover="Tip(\'Ein Element nach unten\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/down.png" alt="" /></a></td>';
				else
					echo '<td class="icon"></td>';

				/* Albumnamen */
				echo '<td><a href="?page=photos-show&amp;album='.$album.$row['id_str'].'">'.$row['caption'].'</a></td>';
			
				/* Anzahl Unteralben und Fotos */
				echo '<td>'.$ctr_sub_albums.'</td>';
				echo '<td>'.$ctr_sub_photos.'</td>';
				
				/* Icons */
				echo '<td class="icon"><a href="?page=photos-album-edit&amp;album='.$album.$row['id_str'].'" onmouseover="Tip(\'Album bearbeiten\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/album_edit.png" alt="" /></a></td>';
				echo '<td class="icon"><a href="?page=photos-show&amp;do=thumb-album&amp;album='.$album.'&amp;id='.$row['id'].'" onmouseover="Tip(\'Thumbnails neu generieren\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/thumb.png" alt="" /></a></td>';
				/* Album Sperren */
				if ($row['locked'])
					echo '<td class="icon"><a href="?page=photos-show&amp;do=unlock-album&amp;album='.$album.'&amp;id='.$row['id'].'" onmouseover="Tip(\'Album entsperren\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/locked.png" alt="" /></a></td>';
				else
					echo '<td class="icon"><a href="?page=photos-show&amp;do=lock-album&amp;album='.$album.'&amp;id='.$row['id'].'" onmouseover="Tip(\'Album sperren\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/locked_not.png" alt="" /></a></td>';
				/* Album loeschen */
				echo '<td class="icon"><a href="javascript:confirmDeletion(\'?page=photos-show&amp;do=delete-album&amp;album='.$album.'&amp;id='.$row['id'].'\', \'Wollen Sie dieses Album mit sämtlichen Fotos und Unteralben wirklich unwiderruflich löschen?\')" onmouseover="Tip(\'Album löschen\')" onmouseout="UnTip()"><img src="img/icons/plugins/photos/delete.png" alt="" /></a></td>';
					
				echo "</tr>\r\n";
			}
		}
		
		/* Tabellenende */
		echo '</table>';	
	}

	/*** Anzeige der Fotos *******************************/
	if ($current_album['id'] > 0) {
		$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$current_album['id'].' ORDER BY file_name ASC', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
				
		if (mysql_num_rows($result) > 0) {
			
			/* Zugriffsrechte pruefen */
			$access = getRecursiveAlbumAccess($current_album['id']);

			while ($row = mysql_fetch_assoc($result)) {
				/* Prüfen ob Datei existiert */
				if ($ftp->fileExists($current_path.$row['file_name'])) {
					/* Thumbnail muss existieren */
					if (!$ftp->fileExists($current_path.MODULE_PHOTOS_THUMB.$row['file_name'])) {
						/* Thumbnail erstellen */
						ImageResizeFtp($ftp, $current_path.$row['file_name'], 
								$current_path.MODULE_PHOTOS_THUMB.$row['file_name'], $PluginPhotos_imagesSettings['height'], $PluginPhotos_imagesSettings['width'],
								$PluginPhotos_imagesSettings['proportional']);
					}
					/* Jetzt darf es angezeigt werden */
					echo '<div class="photo">';
					echo '<p><a href="?page=photos-photo-edit&amp;album='.$album.'&amp;id='.$row['id'].'"'
							.'onmouseover="Tip(\'Fotokommentar bearbeiten\')" onmouseout="UnTip()">'
							.'<img src="img/icons/plugins/photos/image_edit.png" alt="" /></a> &nbsp; ';
					echo '<a href="?page=photos-show&amp;album='.$album.'&amp;do=delete-photo&amp;id='.$row['id'].'"'
							.'onmouseover="Tip(\'Foto löschen\')" onmouseout="UnTip()">'
							.'<img src="img/icons/plugins/photos/image_delete.png" alt="" /></a></p>';
					if ($access['access'] > 0 || $access['locked']) {
						/* Geschuetzte Bider ausgeben */
						echo '<img src="../download.php?path='.$current_path.MODULE_PHOTOS_THUMB.$row['file_name'].'&amp;inline" 
								alt="'.$row['caption'].'" />';
					}
					else {
						/* Normale Bilderausgabe */
						echo '<img src="'.FILESYSTEM_DIR.$current_path.MODULE_PHOTOS_THUMB
								.$row['file_name'].'" alt="'.$row['caption'].'" />';
					}
					/* Fotokommentar */
					if ($row['caption'] != '') {
						echo '<div class="caption">'.$row['caption'].'</div>';
					}
					echo '</div>';
				}
				else {
					/* Bild aus Datenbank löschen */
					mysql_query('DELETE FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE id='.$row['id'].' AND album_id='.$current_album['id'], DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);
				}
			}
			echo '<p class="photo-clear"></p>';
		}
		else {
			if ($line_ctr == 0) {
				echo ActionReport(REPORT_INFO, 'Album leer', 'Dieses Album ist noch leer.');
			}
		}
	}
	else if ($line_ctr == 0) {
		echo ActionReport(REPORT_INFO, 'Keine Fotoalben', 'Es existieren noch keine Fotoalben.');
	}
}
else {
	echo ActionReport(REPORT_EINGABE, "Ablum existiert nicht",
			"Das gewünschte Album existiert nicht!");
}

/* FTP Verbindung schliessen */
$ftp->close();

?>