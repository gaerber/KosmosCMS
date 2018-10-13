<?php

 /*
 =====================================================
 Name ........: Fotoalbum
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: upload.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 13.10.2014 | Program erstellt
 |2.0.1   | 07.01.2015 | Bugfix Fotokommentar
 -----------------------------------------------------
 Beschreibung :
 Hochladen eines Fotos.

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

echo '<h1 class="first">Fotos hochladen</h1>';

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
if (($current_album = readAlbumConfig($ftp, $current_path)) && $current_album['id'] > 0) {
	/* Infos des aktuellen Albums anzeigen */
	echo printInfoBox('Album: '.$current_album['caption'],
			'<p>Alle Fotos werden in das Album <a href="?page=photos-show&amp;album='.$album.'">'.$current_album['caption'].'</a> hochgeladen.</p>');

	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	if (ACP_MODULE_DRAG_AND_DROP) {
		$file = $form->addElement('dropfiles', 'foto', 'Fotos', NULL, true);
		$file->setAcceptTypes('image/*');
	}
	else {
		$file = $form->addElement('file', 'foto', 'Foto', NULL, true);
	}
	$photocomment = $form->addElement('checkbox', 'photocomment', 'Fotokommentar übernehmen', '1');
	$photocomment->setChecked(true);
	$overwrite = $form->addElement('checkbox', 'overwrite', 'Überschreibung erzwingen', '1');
	$submit = $form->addElement('submit', 'button', NULL, 'Hochladen');
	
	/* Auswertung */
	if ($form->checkForm()) {
		$file_data = $file->getValue();
		$file_data['name'] = ValidateFileSystem(basename($file_data['name']), '.');
		/* Groesse pruefen */
		if ($file_data['size'] <= FILE_SIZE_LIMIT) {
			/* Erlaubte Dateityp */
			if (isDatatypeAllowed($file_data['name'], $FileSystem_AllowedImageTypes)) {
				/* Existiert dieser Dateinamen bereits */
				if ($overwrite->getValue() || !$ftp->fileExists($current_path.$file_data['name'])) {
					/* Es duerfen keine doppeleintraege in der Datenbank vorhanden sein */
					Database::instance()->query('DELETE FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE file_name="'.$file_data['name'].'" AND album_id='.$current_album['id'])
							OR FatalError(FATAL_ERROR_MYSQL);
					/* Datei auf FTP Server kopieren */
					$ftp->ChangeDir($current_path);
					if ($ftp->FilePut($file_data['name'], $file_data['tmp_name'])) {
						/* Existiert der Ordner */
						if (!$ftp->folderExists($current_path.MODULE_PHOTOS_THUMB)) {
							if (!$ftp->mkdir(MODULE_PHOTOS_THUMB))
								FatalError(FATAL_ERROR_FILE);
						}
						
						/* Thumbnail erstellen */
						if (ImageResizeFtp($ftp, $current_path.$file_data['name'], 
								$current_path.MODULE_PHOTOS_THUMB.$file_data['name'],
								$PluginPhotos_imagesSettings['height'], $PluginPhotos_imagesSettings['width'],
								$PluginPhotos_imagesSettings['proportional'])) {
							/* Fotokommentar und Zeitpunkt der Aufnahme */
							$comment = '';
							$filetime = 0;
							/* Bild-Titel auslesen (Nur JPG moeglich!) */
							$image_header = @exif_read_data($file_data['tmp_name']); // Aus dem Originalbild lesen im lokalen Temp Verzeichnis
							if ($photocomment->getValue() && $image_header && isset($image_header['ImageDescription'])) {
								$comment = preg_replace("/^ +(.*) +$/", "$1", $image_header['ImageDescription']);
							}
							if ($image_header && isset($image_header['FileDateTime'])) {
								$filetime = $image_header['FileDateTime'];
							}

							/* Datenbankeintrag */
							if (Database::instance()->query('INSERT INTO '.DB_TABLE_PLUGIN.'photoalbum_photo(file_name, album_id, file_timestamp, caption, writer, timestamp)
									VALUES("'.$file_data['name'].'", "'.$current_album['id'].'", '.$filetime.', "'.StdSqlSafety($comment).'", 
									'.$_SESSION['admin_id'].', '.TIME_STAMP.')')) {
								echo ActionReport(REPORT_OK, 'Foto hochgeladen',
										'Das Foto wurde erfolgreich hochgeladen!');
							}
							else {
								echo ActionReport(REPORT_ERROR, 'MySQL Fehler',
										'Datenbankeintrag konnte nicht erstellt werden! '.Database::instance()->getErrorMessage());
							}
						}
						else {
							echo ActionReport(REPORT_ERROR, 'Thumbnail nicht erstellt',
									'Das Foto wurde erfolgreich hochgeladen, aber das Thumbnail konnte 
									nicht erstellt werden.');
						}
						/* Weitere Bilder hochladen */
						echo $form->getForm();
					}
					else {
						echo ActionReport(REPORT_ERROR, 'FTP Fehler',
								'Das Foto konnte nicht auf den FTP Server kopiert werden!');
					}
				}
				else {
					/* Dateinamen existiert bereits */
					echo ActionReport(REPORT_EINGABE, 'Dateinamen existiert bereits',
							'Eine anderes Foto mit gleichem Namen existiert bereits in diesem Album!');
					echo $form->getForm();
				}
			}
			else {
				/* Unerlaubter Dateityp */
				echo ActionReport(REPORT_EINGABE, 'Dateityp nicht erlaubt',
						'Sie wollten einen nicht erlaupten Dateityp hochladen!');
				echo $form->getForm();
			}
		}
		else {
			/* Datei zu gross */
			echo ActionReport(REPORT_EINGABE, 'Foto ist zu gross',
					'Das ausgewählte Foto ist zu gross!
						Die maximal erlaubte Dateigrösse ist '.BinaryMultiples(FILE_SIZE_LIMIT).'.');
			echo $form->getForm();
		}
	}
	else {
		echo $form->getForm();
	}
}
else {
	echo ActionReport(REPORT_EINGABE, "Ablum existiert nicht",
			"Das gewünschte Album existiert nicht!");
}

/* FTP Verbindung schliessen */
$ftp->close();

?>