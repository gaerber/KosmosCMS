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

echo "<h1 class=\"first\">Foto hochladen</h1>";

/* FTP Verbindung aufbauen */
$ftp = new ftp();

if (isset($_GET['album']) 
		&& $album_info = readAlbumConfigFtp($ftp, $FileSystem_ModulePahts['photos'].$_GET['album'])) {
	/* Albumverzeichnis */
	$current_album = $FileSystem_ModulePahts['photos'].$_GET['album'];
	
	/* Informationen zum Album */
	echo ActionReport(REPORT_INFO, 'Album: '.$album_info['title'], $album_info['description']
			.'<p><a href="?page=photos-photos-list&album='.$_GET['album'].'">Zurück zur Albumübersicht</a></p>');
	
	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	$file = $form->addElement('file', 'file', 'Foto', NULL, true);
	$overwrite = $form->addElement('checkbox', 'overwrite', 'Überschreibung erzwingen', '1');
	$submit = $form->addElement('submit', 'button', NULL, 'Hochladen');
	
	/* Auswertung */
	if ($form->checkForm()) {
		$file_data = $file->getValue();
		$file_data['name'] = ValidateFileSystem(basename($file_data['name']), '.');
		/* Groesse pruefen */
		if ($file_data['size'] <= FILE_SIZE_LIMIT) {
			/* Erlaubte Dateityp */
			if (in_array(mb_strtolower(array_pop(explode('.', $file_data['name']))),
					$FileSystem_AllowedImageTypes)) {
				/* Existiert dieser Dateinamen bereits */
				if ($overwrite->getValue() || !$ftp->fileExists($current_album.$file_data['name'])) {
					/* Datei auf FTP Server kopieren */
					$ftp->ChangeDir($current_album);
					if ($ftp->FilePut($file_data['name'], $file_data['tmp_name'])) {
						/* Existiert der Ordner */
						if (!$ftp->folderExists($current_album.MODULE_PHOTOS_THUMB)) {
							if (!$ftp->mkdir(MODULE_PHOTOS_THUMB))
								FatalError(FATAL_ERROR_FILE);
							$ftp->chmod(MODULE_PHOTOS_THUMB, 0777);	//Bugfix Thumb erstellen
						}
						
						/* Thumbnail erstellen */
						if (ImageResizeFtp($ftp, $current_album.$file_data['name'], 
								$current_album.MODULE_PHOTOS_THUMB.$file_data['name'],
								100, 100, false)) {
							echo ActionReport(REPORT_OK, 'Foto hochgeladen',
									'Das Foto wurde erfolgreich hochgeladen!');
						}
						else {
							echo ActionReport(REPORT_ERROR, 'Foto hochgeladen',
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
	/* Album-Ordner existiert nicht */
	echo ActionReport(REPORT_EINGABE, 'Album nicht gefunden',
			'Das gewünschte Album wurde nicht gefunden.');
}

/* FTP Verbindung schliessen */
$ftp->close();

?>