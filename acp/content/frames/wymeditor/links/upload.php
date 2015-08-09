<?php

 /*
 =====================================================
 Name ........: Frame: Links Datei hochladen
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
 -----------------------------------------------------
 Beschreibung :
 Datei kann hochgeladen und verlinkt werden. WYMeditor
 Erweiterung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Datei hochladen";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

if (ACP_FILE_SYSTEM_EN && ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_DATA)) {
	/* Titel Optionen */
	echo '<div id="options"><a href="?page=wymeditor-links-default"><img src="img/icons/wysiwym/link.png" alt="" /></a>'
			.'<a href="?page=wymeditor-links-page"><img src="img/icons/wysiwym/link_cms.png" alt="" /></a>'
			.'<a href="?page=wymeditor-links-file"><img src="img/icons/wysiwym/file_list.png" alt="" /></a>'
			.'<a href="?page=wymeditor-links-upload"><img src="img/icons/wysiwym/file_upload.png" alt="" /></a></div>';
	echo "<h1>Datei hochladen</h1>";
	
	/* FTP Verbindung aufbauen */
	$ftp = new ftp();

	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	$file = $form->addElement('file', 'file', 'Datei', NULL, true);
	$folderList = $form->addElement('select', 'folder', 'Verzeichnis');
	$submit = $form->addElement('submit', 'button', NULL, 'Hochladen');

	/* Saemtliche Verzeichnise einlesen */
	$folderList->addOption(preg_replace('/^\/+/', '', FILESYSTEM_DIR), '/');
	$ftp->folderListCallback('/', 'FileSystemFolders', true);
	
	/* Auswertung */
	if ($form->checkSubmit() && $form->checkForm()) {
		$file_data = $file->getValue();
		$file_data['name'] = ValidateFileSystem(basename($file_data['name']), '.');
		/* Speicherort pruefen */
		if (!in_array($folderList->getValue(), $FileSystem_ModulePahts)) {
			/* Groesse pruefen */
			if ($file_data['size'] <= FILE_SIZE_LIMIT) {
				/* Erlaubte Dateityp */
				if (isDatatypeAllowed($file_data['name'], $FileSystem_AllowedDataTypes)) {
					/* Existiert dieser Dateinamen bereits */
					if (!$ftp->fileExists($folderList->getValue().$file_data['name'])) {
						/* Datei auf FPT Server kopieren */
						$ftp->ChangeDir($folderList->getValue());
						if ($ftp->FilePut($file_data['name'], $file_data['tmp_name'])) {
							echo ActionReport(REPORT_OK, 'Datei hochgeladen',
									'Die Datei wurde erfolgreich hochgeladen!');
							/* Javascript um originalfelder zu fuellen und Bild direkt einzufuegen */
							$ACP_ApplicationInfo['body_onload'] = "parent.document.getElementById('wym_href').value = '".FILESYSTEM_DIR.$folderList->getValue().$file_data['name']."';"
									."parent.document.getElementById('wym_submit').click();";
						}
						else {
							echo ActionReport(REPORT_ERROR, "FTP Fehler",
									"Die Datei konnte nicht auf den FTP Server kopiert werden!");
						}
					}
					else {
						/* Dateinamen existiert bereits */
						echo ActionReport(REPORT_EINGABE, "Dateinamen existiert bereits",
								"Eine andere Datei mit gleichem Namen existiert bereits in diesem Verzeichnis!");
						echo $form->getForm();
					}
				}
				else {
					/* Unerlaubter Dateityp */
					$last = array_pop($FileSystem_AllowedDataTypes);
					echo ActionReport(REPORT_EINGABE, "Dateityp nicht erlaubt",
							"Sie wollten einen nicht erlaupten Dateityp hochladen!
							<br />Erlaubten Dateiformate: ".implode(", ", $FileSystem_AllowedDataTypes)." und ".$last);
					echo $form->getForm();
				}
			}
			else {
				/* Datei zu gross */
				echo ActionReport(REPORT_EINGABE, "Datei ist zu gross",
						"Die ausgewählte Datei ist zu gross!
							Die maximal erlaubte Dateigrösse ist ".BinaryMultiples(FILE_SIZE_LIMIT).".");
				echo $form->getForm();
			}
		}
		else {
			/* Module Ordner */
			echo ActionReport(REPORT_EINGABE, "Keine berechtigung",
					"Die Moduleordner duerfen nicht bearbeitet werden!");
		}
	}
	else {
		/* Ausgabe Formular */
		echo $form->getForm();
	}
	/* FTP Verbindung schliessen */
	$ftp->close();
	
}
else {
	echo ActionReport(REPORT_EINGABE, 'Kein Dateisystem', 'Ihre CMS Installation unterstützt kein Dateisystem!');
}

?>