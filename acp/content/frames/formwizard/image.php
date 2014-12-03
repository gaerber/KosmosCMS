<?php

 /*
 =====================================================
 Name ........: Frame: Bildbearbeitung von formWizard
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: image.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 22.04.2013 | Erstellt.
 |1.0.1   | 29.04.2013 | Automatische Bilderskalierung
 -----------------------------------------------------
 Beschreibung :
 Erneuern und loeschen von Bildern mit dem formWizard.
 Beispiel: Benutzerbilder und Seitenbilder.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_USER, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

/* Pruefung aller Parameter */
if (isset($_GET['file'], $_GET['do'], $_GET['ref']) && $_GET['file']!='' && is_numeric($_GET['ref'])) {
	if ($_GET['do'] == 'new') {
		$ACP_ApplicationInfo['title'] = 'Neues Benutzerbild';
		echo "<h1>Neues Benutzerbild</h1>";
		
		/* Formular */
		$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
		$file = $form->addElement('file', 'file', 'Datei', NULL, true);
		$submit = $form->addElement('submit', 'button', NULL, 'Hochladen');
		
		/* Auswertung */
		if ($form->checkSubmit() && $form->checkForm()) {
			$file_data = $file->getValue();
			$file_name = ValidateFileSystem(basename($_GET['file']), '.');
			/* Groesse pruefen */
			if ($file_data['size'] <= FILE_SIZE_LIMIT) {
				/* Dateiformat pruefen */
				if (mb_strtolower(array_pop(explode(".", $file_data['name']))) == 'jpg') {
					/** @todo Groesse anpassen und per FTP hochladen */
					$ftp = new ftp();
					if (ImageResizeFtp($ftp, $file_data['tmp_name'], $FileSystem_ModulePahts['user-system-images'].$file_name,
							$UserSystem_imagesSettings['height'], $UserSystem_imagesSettings['width'], false)){
						echo ActionReport(REPORT_OK, 'Bild hochgeladen',
								'Das Bild wurde erfolgreich hochgeladen!');
						/* Aktualisierung mit JS und anschliessendes Schliessen */
						$ACP_ApplicationInfo['body_onload'] = 'opener.document.'.
								'getElementById(\'form_image_'.$_GET['ref'].'\').src=\''.
								FILESYSTEM_DIR_V21.$FileSystem_ModulePahts['user-system-images'].
								$file_name.'?'.TIME_STAMP.'\';'.
								'opener.document.getElementById(\'form_image_url_'.$_GET['ref'].'\').style.display=\'block\';'.
								'window.close();';
					}
					else {
						echo ActionReport(REPORT_ERROR, "FTP Fehler",
								"Das Bild konnte nicht auf den FTP Server kopiert werden!");
					}
					$ftp->close();
				}
				else {
					/* Unerlaubter Dateityp */
					echo ActionReport(REPORT_EINGABE, 'Falscher Dateityp',
							'Das Benutzerbild muss zwingend im jpg Format sein!');
					echo $form->getForm();
				}
			}
			else {
				/* Datei zu gross */
				echo ActionReport(REPORT_EINGABE, "Bild ist zu gross",
						"Das ausgewählte Bild ist zu gross!
							Die maximal erlaubte Dateigrösse ist ".BinaryMultiples(FILE_SIZE_LIMIT).".");
				echo $form->getForm();
			}
		}
		else {
			/* Ausgabe Formular */
			echo $form->getForm();
		}
	}
	else if ($_GET['do'] == 'delete') {
		$ACP_ApplicationInfo['title'] = 'Benutzerbild löschen';
		echo "<h1>Benutzerbild löschen</h1>";
		/* FTP Verbndung */
		$ftp = new ftp();
		if ($ftp->fileExists($FileSystem_ModulePahts['user-system-images'].$_GET['file'])) {
			$ftp->ChangeDir($FileSystem_ModulePahts['user-system-images']);
			if ($ftp->Delete($_GET['file'])) {
				echo ActionReport(REPORT_OK, 'Datei gelöscht', 'Die Datei wurde erfolgreich gelöscht!');
				/* Aktualisierung mit JS und anschliessendes Schliessen */
				$ACP_ApplicationInfo['body_onload'] = 'opener.document.'.
						'getElementById(\'form_image_'.$_GET['ref'].'\').src=\''.
						FILESYSTEM_DIR_V21.$FileSystem_ModulePahts['user-system-images'].
						$UserSystem_imagesSettings['default'].'\';'.
						'opener.document.getElementById(\'form_image_url_'.$_GET['ref'].'\').style.display=\'none\';'.
						'window.close();';
			}
			else {
				echo ActionReport(REPORT_ERROR, 'FTP Fehler', 'Die Datei konnte nicht gelöscht werden!');
			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, 'Keine Datei', 'Die ausgewählte Datei wurde nicht gefunden!');
		}
		$ftp->close();
	}
	else {
		echo ActionReport(REPORT_EINGABE, 'Keine Aktion', 'Es wurde keine Aktion definiert!');
	}
}
else {
	echo ActionReport(REPORT_EINGABE, 'Verzeichnis existiert nicht',
			'Es existiert kein Verzeichnis für die Benutzerbilder!');
}

?>