<?php

 /*
 =====================================================
 Name ........: Frame: Liste mit eigenen Bildern
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
 Bilder in einem Ordner werden angezeigt. WYMeditor
 Erweiterung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Eigene Bilder";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

if (ACP_FILE_SYSTEM_EN) {

	/* Titel Optionen */
	echo '<div id="pos-dummy-form"></div>';
	echo '<div id="pos-fixed">';
	echo '<div id="options"><a href="?page=wymeditor-images-default"><img src="img/icons/wysiwym/image.png" alt="" /></a>'
			.'<a href="?page=wymeditor-images-list"><img src="img/icons/wysiwym/image_list.png" alt="" /></a>';
	if (ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_IMAGES))
		echo '<a href="?page=wymeditor-images-upload"><img src="img/icons/wysiwym/image_upload.png" alt="" /></a>';
	echo '</div>';
	echo "<h1>Eigene Bilder</h1>";
	
	/* FTP Verbindung aufbauen */
	$ftp = new ftp();
	
	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	$folderList = $form->addElement('select', 'folderlist', 'Verzeichnis', NULL, true);
	$folderList->setJavaScript('onchange="window.location.href=\'?page=wymeditor-images-list&amp;folder=\'+this.value";');
	
	/* Saemtliche Verzeichnise einlesen */
	$folderList->addOption(FILESYSTEM_DIR, '/');
	$ftp->folderListCallback('/', 'FileSystemFolders', true);
	
	/* Ausgabe Formular */
	echo $form->getForm();
	echo '</div>';
	
	/* Dateien aus dem Ordner anzeigen */
	if (isset($_GET['folder']))
		$current_folder = $_GET['folder'];
	else
		$current_folder = '/';
	
	/* Ordner einlesen */
	if ($folder_pointer = $ftp->openDir($current_folder)) {
		/* Alle Dateine des Ordners einzeln verarbeiten */
		echo '<div class="data-list-img">';
		while($file = $folder_pointer->readDir()) {
			/* Nur Bilder sollen verarbeitet werden */
			if (!$folder_pointer->isDir($file)) {
				if (in_array(mb_strtolower(array_pop(explode(".", $file))),
						$FileSystem_AllowedImageTypes)) {
					echo '<a href="#" class="image" onclick="parent.document.getElementById(\'wym_src\').value = \''
					.FILESYSTEM_DIR_V21.$current_folder.$file
					.'\';parent.document.getElementById(\'wym_submit\').click();"><img src="'
					.FILESYSTEM_DIR_V21.$current_folder.$file.'" alt="" /></a>';
				}
			}
		}
		echo '</div>';
	}
	else {
		echo ActionReport(REPORT_EINGABE, 'Verzeichnis existiert nicht', 'Das gewünschte Verzeichnis existiert nicht!');
	}
	
	/* FTP Verbindung schliessen */
	$ftp->close();
}
else {
	echo ActionReport(REPORT_EINGABE, 'Kein Dateisystem', 'Ihre CMS Installation unterstützt kein Dateisystem!');
}

?>