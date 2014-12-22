<?php

 /*
 =====================================================
 Name ........: Frame: Links auf eigene Dateien
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
 |1.0     | 10.04.2013 | Ueberarbeitung volle Frames.
 -----------------------------------------------------
 Beschreibung :
 Dateien in einem Ordner werden angezeigt. WYMeditor
 Erweiterung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Eigene Dateien";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

if (ACP_FILE_SYSTEM_EN) {

	/* Titel Optionen */
	echo '<div id="pos-dummy-form"></div>';
	echo '<div id="pos-fixed">';
	echo '<div id="options"><a href="?page=wymeditor-links-default"><img src="img/icons/wysiwym/link.png" alt="" /></a>'
			.'<a href="?page=wymeditor-links-page"><img src="img/icons/wysiwym/link_cms.png" alt="" /></a>'
			.'<a href="?page=wymeditor-links-file"><img src="img/icons/wysiwym/file_list.png" alt="" /></a>';
	if (ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_DATA))
		echo '<a href="?page=wymeditor-links-upload"><img src="img/icons/wysiwym/file_upload.png" alt="" /></a>';
	echo '</div>';
	echo "<h1>Eigene Dateien</h1>";
	
	/* FTP Verbindung aufbauen */
	$ftp = new ftp();
	
	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	$folderList = $form->addElement('select', 'folderlist', 'Verzeichnis', NULL, true);
	$folderList->setJavaScript('onchange="window.location.href=\'?page=wymeditor-links-file&amp;folder=\'+this.value";');
	
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
		echo '<div class="data-list"><ol>';
		while($file = $folder_pointer->readDir()) {
			/* Verzeichnisse werden nicht verarbeitet */
			if (!$folder_pointer->isDir($file)) {
				echo '<li><a href="#" onclick="parent.document.getElementById(\'wym_href\').value = \''
				.FILESYSTEM_DIR_V21.$current_folder.$file
				.'\';parent.document.getElementById(\'wym_submit\').click();">'.$file.'</a></li>';
			}
		}
		echo '</ol></div>';
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