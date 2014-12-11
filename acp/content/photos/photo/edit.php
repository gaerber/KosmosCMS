<?php

 /*
 =====================================================
 Name ........: Fotoalbum Foto kommentieren
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: edit.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 04.10.2014 | Program erstellt
 -----------------------------------------------------
 Beschreibung :
 Ein bestimmtes Foto Kommentieren.

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

echo "<h1 class=\"first\">Foto kommentieren</h1>";

/* FTP Verbindung aufbauen */
$ftp = new ftp();

/* Album selektieren */
$current_path = $FileSystem_ModulePahts['photos2'];
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
	/* Existiert das Foto in der Datenbank und im Filesystem? */
	$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'photoalbum_photo WHERE album_id='.$current_album['id'].' AND id='.StdSqlSafety($_GET['id']), DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_assoc($result);
	if ($line && $ftp->fileExists($current_path.$line['file_name'])) {
		
		$access = getRecursiveAlbumAccess($current_album['id']);
		if ($access['access'] > 0 || $access['locked']) {
			/* Geschuetzte Bider ausgeben */
			$image = '<div class="photo"><img src="../download.php?path='.$current_path.MODULE_PHOTOS_THUMB.$line['file_name']
					.'&amp;inline" alt="'.$line['caption'].'" /></div>';
		}
		else {
			/* Normale Bilderausgabe */
			$image =  '<div class="photo"><img src="'.FILESYSTEM_DIR_V21.'/'.$current_path.MODULE_PHOTOS_THUMB
					.$line['file_name'].'" alt="'.$line['caption'].'" /></div>';
		}
		
		/* Informationen über das Album und das Foto */
		$icons = array(
				array('icon' => 'img/icons/plugins/photos/return.png', 'url' => '?page=photos-show&album='.$album, 'comment' => 'Zurück zur Albumübersicht'),
				array('icon' => 'img/icons/plugins/photos/album_edit.png', 'url' => '?page=photos-album-edit&amp;album='.$album, 'comment' => 'Album '.$current_album['caption'].' bearbeiten')
		);
		echo printInfoBox('Album: '.$current_album['caption'], $current_album['description'].$image.'<p class="photo-clear"></p>', $icons);
		
		/* Formular */
		$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
		$caption = $form->addElement('text', 'caption', 'Kommentar');
		$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');
		
		/* Defaultwerte Setzen */
		if (!$form->checkSubmit()) {
			$caption->setValue($line['caption']);
		}
		
		/* Formular pruefen */
		if ($form->checkForm()) {
			/* Kommentar abspeichern */
			if (mysql_query('UPDATE '.DB_TABLE_PLUGIN.'photoalbum_photo SET caption="'.StdSqlSafety($caption->getValue()).'" WHERE album_id='.$current_album['id'].' AND id='.StdSqlSafety($_GET['id']), DB_CMS)) {
				echo ActionReport(REPORT_OK, 'Kommentar gespeichert',
						'Der Fotokommentar wurde erfolgreich gespeichert.');
			}
			else {
				echo ActionReport(REPORT_ERROR, 'MySQL Error',
						'Der Fotokommentar konnte nicht gespeichert werden. '.mysql_error(DB_CMS));
			}
		}
		else {
			echo $form->getForm();
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, 'Foto nicht gefunden',
				'Das gewünschte Foto existiert nicht mehr.');
	}
}
else {
	echo ActionReport(REPORT_EINGABE, "Ablum existiert nicht",
			"Das gewünschte Album existiert nicht!");
}

/* FTP Verbindung schliessen */
$ftp->close();

?>