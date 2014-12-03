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
 |1.0     | 12.09.2012 | Program erstellt
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

if (isset($_GET['album']) 
		&& $album_info = readAlbumConfig('../'.FILESYSTEM_DIR.$FileSystem_ModulePahts['photos']
			.$_GET['album'])) {
	if (isset($_GET['photo']) && file_exists('../'.FILESYSTEM_DIR.$FileSystem_ModulePahts['photos']
			.$_GET['album'].$_GET['photo'])) {
		/* Kommentare sind nur bei JPEG Fotos moeglich */
		if (exif_imagetype('../'.FILESYSTEM_DIR.$FileSystem_ModulePahts['photos']
				.$_GET['album'].$_GET['photo']) == IMAGETYPE_JPEG) {
			/* Informationen zum Album */
			echo ActionReport(REPORT_INFO, 'Album: '.$album_info['title'], $album_info['description']
					.'<p><a href="?page=photos-photos-list&album='.$_GET['album'].'">
					Zurück zur Albumübersicht</a></p>');

			if ($album_info['access'] > 0 || $album_info['locked']) {
				/* Geschuetzte Bider ausgeben */
				echo '<div class="photo"><img src="../photo.php?path='.FILESYSTEM_DIR
						.$FileSystem_ModulePahts['photos']
						.$_GET['album'].'&amp;image='.$_GET['photo'].'&amp;thumb=1" 
						alt="" /></div>';
			}
			else {
				/* Normale Bilderausgabe */
				echo '<div class="photo"><img src="../'.FILESYSTEM_DIR.$FileSystem_ModulePahts['photos']
						.$_GET['album'].MODULE_PHOTOS_THUMB
						.$_GET['photo'].'" alt="" /></div>';
			}
			echo '<p class="photo-clear"></p>';
			
			/* Formular */
			$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
			$caption = $form->addElement('text', 'caption', 'Kommentar');
			$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');
			
			/* Defaultwerte Setzen */
			if (!$form->checkSubmit()) {
				/* Bild-Titel auslesen (Nur JPG moeglich!) */
				$image_header = exif_read_data('../'.FILESYSTEM_DIR.$FileSystem_ModulePahts['photos']
						.$_GET['album'].$_GET['photo']);
				if ($image_header && isset($image_header['ImageDescription']))
					$caption->setValue($image_header['ImageDescription']);
			}
			
			/* Formular pruefen */
			if ($form->checkForm()) {
				$title = str_replace('|', '', StdString($caption->getValue()));
				/* Kommentar abspeichern */
				
			}
			else {
				echo $form->getForm();
			}
		}
		else {
			echo ActionReport(REPORT_EINGABE, 'Nicht möglich',
					'Kommentare sind nur bei Fotos im JPEG Format möglich.');
		}
	}
	else {
		/* Album-Ordner existiert nicht */
		echo ActionReport(REPORT_EINGABE, 'Foto nicht gefunden',
				'Das gewünschte Foto existiert nicht mehr.');
	}
}
else {
	/* Album-Ordner existiert nicht */
	echo ActionReport(REPORT_EINGABE, 'Album nicht gefunden',
			'Das gewünschte Album wurde nicht gefunden.');
}

?>