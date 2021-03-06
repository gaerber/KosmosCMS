<?php

 /*
 =====================================================
 Name ........: Frame: Bild hochladen
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: file.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 16.04.2013 | Ueberarbeitung volle Frames.
 |1.1     | 07.10.2007 | Bildskalierung 
 -----------------------------------------------------
 Beschreibung :
 Ein Bild kann hochgeladen und eingefuegt werden.
 WYMeditor Erweiterung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Bild hochladen";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

if (ACP_FILE_SYSTEM_EN && ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_IMAGES)) {
	/* Titel Optionen */
	echo '<div id="options"><a href="?page=wymeditor-images-default"><img src="img/icons/wysiwym/image.png" alt="" /></a>'
			.'<a href="?page=wymeditor-images-list"><img src="img/icons/wysiwym/image_list.png" alt="" /></a>'
			.'<a href="?page=wymeditor-images-upload"><img src="img/icons/wysiwym/image_upload.png" alt="" /></a></div>';
	echo "<h1>Bild hochladen</h1>";
	
	/* FTP Verbindung aufbauen */
	$ftp = new ftp();

	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	$file = $form->addElement('file', 'file', 'Datei', NULL, true);
	$folderList = $form->addElement('select', 'folder', 'Verzeichnis');
	$option = $form->addElement('select', 'image_option', 'Bildoptionen');
	$option_width = $form->addElement('text', 'image_option_width', 'Bild-Breite', CONTENT_IMAGE_DEFAULT_WIDTH, true);
	$option_height = $form->addElement('text', 'image_option_height', 'Bild-Höhe', CONTENT_IMAGE_DEFAULT_HEIGHT, true);
	$submit = $form->addElement('submit', 'button', NULL, 'Hochladen');

	/* Skalierungswerte prüfen */
	$option_width->setCustomValidation('/^[1-9][0-9]*$/i', ActionReport(REPORT_EINGABE, 'Eingabefehler', 'Bild-Breite in Pixel ist fehlerhaft'));
	$option_height->setCustomValidation('/^[1-9][0-9]*$/i', ActionReport(REPORT_EINGABE, 'Eingabefehler', 'Bild-Höhe in Pixel ist fehlerhaft'));

	/* Saemtliche Verzeichnise einlesen */
	$folderList->addOption(preg_replace('/^\/+/', '', FILESYSTEM_DIR), '/');
	$ftp->folderListCallback('/', 'FileSystemFolders', true);
	
	/* Bildbearbeitungs-Optionen */
	$option->addOption('Originalgrösse beibehalten', 'none', true);
	$option->addOption('Auf feste Grösse zuschneiden', 'fix');
	$option->addOption('Bildgrösse automatisch limitieren', 'dynamic');
	$option->setJavaScript('onchange="if(this.value==\'none\') $(\'.image_option_size\').hide(); else $(\'.image_option_size\').show();"');
	$option_width->setCssClass('image_option_size hide');
	$option_height->setCssClass('image_option_size hide');
	
	/* Auswertung */
	if ($form->checkSubmit() && $form->checkForm()) {
		$file_data = $file->getValue();
		$file_data['name'] = ValidateFileSystem(basename($file_data['name']), '.');
		/* Speicherort pruefen */
		if (!in_array($folderList->getValue(), $FileSystem_ModulePahts)) {
			/* Groesse pruefen */
			if ($file_data['size'] <= FILE_SIZE_LIMIT) {
				/* Erlaubte Dateityp */
				if (isDatatypeAllowed($file_data['name'], $FileSystem_AllowedImageTypes)) {
					/* Existiert dieser Dateinamen bereits */
					if (!$ftp->fileExists($folderList->getValue().$file_data['name'])) {
						/* Datei auf FPT Server kopieren */
						$isUploaded = false;
						$ftp->ChangeDir($folderList->getValue());
						if ($option->getValue() == 'none') {
							$isUploaded = $ftp->FilePut($file_data['name'], $file_data['tmp_name']);
						}
						else {
							$isUploaded = ImageResizeFtp($ftp, $file_data['tmp_name'], $folderList->getValue().$file_data['name'], 
									$option_height->getValue(), $option_width->getValue(), $option->getValue()=='dynamic');
						}

						if ($isUploaded) {
							echo ActionReport(REPORT_OK, 'Bild hochgeladen',
									'Das Bild wurde erfolgreich hochgeladen!');
							/* Javascript um originalfelder zu fuellen und Bild direkt einzufuegen */
							$ACP_ApplicationInfo['body_onload'] = "parent.document.getElementById('wym_src').value = '".FILESYSTEM_DIR.$folderList->getValue().$file_data['name']."';"
									//."parent.document.getElementById('wym_alt').value = '".$alt_text->getValue()."';"
									."parent.document.getElementById('wym_submit').click();";
						}
						else {
							echo ActionReport(REPORT_ERROR, 'FTP Fehler',
									'Das Bild konnte nicht auf den FTP Server kopiert werden!');
						}
					}
					else {
						/* Dateinamen existiert bereits */
						echo ActionReport(REPORT_EINGABE, 'Dateinamen existiert bereits',
								'Ein anderes Bild mit gleichem Namen existiert bereits in diesem Verzeichnis!');
						if ($option->getValue() == 'fix' || $option->getValue() == 'dynamic') {
							$option_width->setCssClass('image_option_size show');
							$option_height->setCssClass('image_option_size show');
						}
						echo $form->getForm();
					}
				}
				else {
					/* Unerlaubter Dateityp */
					$last = array_pop($FileSystem_AllowedImageTypes);
					echo ActionReport(REPORT_EINGABE, 'Dateityp nicht erlaubt',
							'Sie wollten einen nicht erlaupten Dateityp hochladen!
							<br />Erlaubten Bildformate: '.implode(', ', $FileSystem_AllowedImageTypes).' und '.$last);
					if ($option->getValue() == 'fix' || $option->getValue() == 'dynamic') {
						$option_width->setCssClass('image_option_size show');
						$option_height->setCssClass('image_option_size show');
					}
					echo $form->getForm();
				}
			}
			else {
				/* Datei zu gross */
				echo ActionReport(REPORT_EINGABE, 'Bild ist zu gross',
						'Das ausgewählte Bild ist zu gross!
							Die maximal erlaubte Dateigrösse ist '.BinaryMultiples(FILE_SIZE_LIMIT).'.');
				if ($option->getValue() == 'fix' || $option->getValue() == 'dynamic') {
					$option_width->setCssClass('image_option_size show');
					$option_height->setCssClass('image_option_size show');
				}
				echo $form->getForm();
			}
		}
		else {
			/* Module Ordner */
			echo ActionReport(REPORT_EINGABE, 'Keine Berechtigung',
					'Die Modulverzeichnisse dürfen nicht bearbeitet werden!');
		}
	}
	else {
		/* Ausgabe Formular */
		if ($option->getValue() == 'fix' || $option->getValue() == 'dynamic') {
			$option_width->setCssClass('image_option_size show');
			$option_height->setCssClass('image_option_size show');
		}
		echo $form->getForm();
	}
	/* FTP Verbindung schliessen */
	$ftp->close();
	
}
else {
	echo ActionReport(REPORT_EINGABE, 'Kein Dateisystem', 'Ihre CMS Installation unterstützt kein Dateisystem!');
}

?>