<?php

 /*
 =====================================================
 Name ........: Fotoalbum Alben bearbeiten
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
 |2.0     | 13.12.2012 | FTP Dateisystem
 -----------------------------------------------------
 Beschreibung :
 Erstellen und bearbeiten von Alben.

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

/* Rekursive Funktion */
function generatePhotoList(&$form_object, $default_folder, $current_album, $level=0) {
	/* Liste aller Unteralben und Fotos erstellen */
	$a_albums = array();
	$a_photos = array();
	readAlbumPhotos($default_folder.$current_album, $a_albums, $a_photos, true);
	
	$a_size = sizeof($a_albums);
	/* Liste aller Unteralben erstellen */
	for ($ctr=0; $ctr<$a_size; $ctr++) {
		if ($album_info_sub = readAlbumConfig($default_folder.$current_album.$a_albums[$ctr])) {
			/* Element der Liste hinzufuegen */
			$form_object->addOption(str_repeat('&nbsp;', 4 * $level).$album_info_sub['title'],
					$current_album.$a_albums[$ctr]);
			
			/* Nach weiteren Unteralben suchen */
			$level++;
			generatePhotoList($form_object, $default_folder, $current_album.$a_albums[$ctr], $level);
			$level--;	
		}
	}
}
function PhotoAlbumCallback($folder_name, $level, $path) {
	global $ftp;
	global $folder_list;
	global $FileSystem_ModulePahts;
	
	if ($album_info = readAlbumConfigFtp($ftp, $path.$folder_name.'/')) {
		/* Element der Liste hinzufuegen */
		$folder_list->addOption(str_repeat('&nbsp;', 4 * $level).$album_info['title'],
				substr($path, strlen($FileSystem_ModulePahts['photos'])).$folder_name.'/');
		return true;
	}
	
	return false;
}


$error = false;
$album_info = NULL;

/* FTP Verbindung aufbauen */
$ftp = new ftp();

if (isset($_GET['folder'], $_GET['album'])) {
	echo '<h1 class="first">Fotoalbum bearbeiten</h1>';
	$album_info = readAlbumConfigFtp($ftp, $FileSystem_ModulePahts['photos'].$_GET['folder'].$_GET['album']);
	if (!$album_info) {
		/* Kein gueltiges Album */
		echo ActionReport(REPORT_EINGABE, 'Album existiert nicht',
				'Das zu bearbeitende Album existiert nicht mehr.');
		$error = true;
	}
}
else {
	echo '<h1 class="first">Neues Fotoalbum</h1>';
}

/* Formular */
$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
if (!$album_info) {
	$folder_list = $form->addElement('select', 'folder', 'Unteralbum');
	$folder_list->addOption('Kein Unteralbum', '');
	$ftp->folderListCallback($FileSystem_ModulePahts['photos'], 'PhotoAlbumCallback', true);
	echo 'Liste eins';
}
$caption = $form->addElement('text', 'caption', 'Titel', null, true);
$description = $form->addElement('textarea', 'description', 'Beschreibung', NULL, true);
$description->setRowsCols(4, 20);

if (ACP_ACCESS_SYSTEM_EN) {
	$access_all = $form->addElement('radio', 'access', 'Berechtigung', '0');
	$access_log = $form->addElement('radio', 'access', NULL, '1');
	$access_grp = $form->addElement('radio', 'access', NULL, '2');
	$access_groups = $form->addElement('select', 'access_group', 'Gruppen');

	$access_groups->setCssClass('select_groups');
	$access_all->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'none\';"');
	$access_log->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'none\';"');
	$access_grp->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'block\';"');

	$access_all->setSubLabel("Alle Besucher");
	$access_log->setSubLabel("Nur angemeldete Besucher");
	$access_grp->setSubLabel("Nur Besucher aus bestimmten Gruppen");

	$access_groups->setMultiple(true);
	$access_groups->setSize(7);
}

$locked = $form->addElement('checkbox', 'locked', 'Sperren', 1);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && $album_info) {
	$caption->setValue($album_info['title']);
	$description->setValue(StdContentEdit($album_info['description']));
	if ($album_info['locked'])
		$locked->setChecked(true);
	/* Berechtigungen */
	if (ACP_ACCESS_SYSTEM_EN) {
		if ($album_info['access'] == 0)
			$access_all->setChecked(true);
		else if ($album_info['access'] == 1)
			$access_log->setChecked(true);
		else {
			$access_grp->setChecked(true);
		}
		/* Gruppen */
		$result = mysql_query('SELECT id, name FROM '.DB_TABLE_ROOT.'cms_access_group
				ORDER BY name ASC', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$access_groups->addOption($row['name'], 1<<$row['id'],
					(bool)($album_info['access'] & (1<<$row['id'])));
		}
	}
}
else {
	/* Benutzer Gruppen */
	if (ACP_ACCESS_SYSTEM_EN) {
		if (!$form->checkSubmit())
			$access_all->setChecked(true);
		$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
				ORDER BY name ASC", DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_array($result)) {
			$access_groups->addOption($row['name'], 1<<$row['id']);
		}
	}
}

/* Formular pruefen */
if ($form->checkForm()) {
	if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue() && !sizeof($access_groups->getValue())) {
		/* Es muss nim. eine Gruppe ausgewaehlt werden */
		$access_groups->setError(true);
		$access_groups->setCssClass('select_groups_view');
		/* Ausgabe des Formulars */
		echo $form->getForm();
	}
	else {
        /* ID Str ermitteln */
		$validate_id_str = ValidateFileSystem($caption->getValue());
		$validate_id_str = str_replace('_', '', $validate_id_str);
		$validate_id_str .= '/';
		
		if (!$album_info) {
			$folder = $folder_list->getValue();
		}
		else {
			$folder = $_GET['folder'];
		}
		
		
		
		/* Ordner bearbeiten */
		if ($album_info && $_GET['album'] != $validate_id_str) {
			if (!$ftp->folderExists($FileSystem_ModulePahts['photos']
					.$folder.$validate_id_str)) {
				/* Ordner umbenennen */
				if (!$ftp->ChangeDir($FileSystem_ModulePahts['photos'].$folder)
						|| !$ftp->Rename($_GET['album'], $validate_id_str)) {
					/* Konnte Ordner nicht umbenennen */
					echo ActionReport(REPORT_ERROR, 'Fehler', 'Der Ordner konnte nicht umbenannt werden.');
					$error = true;
				}
			}
			else {
				/* Orndernamen existiert bereits */
				echo ActionReport(REPORT_ERROR, 'Albumnamen existiert bereits',
						'Der angegebene Albumnamen existiert bereits.');
				$caption->setError(true);
				if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
					$access_groups->setCssClass('select_groups_view');
				}
				echo $form->getForm();
				$error = true;
			}
		}
		
		/* Neuer Ordner */
		if (!$album_info) {
			if (!$ftp->folderExists($FileSystem_ModulePahts['photos'].$folder.$validate_id_str)) {
				/* Ordner erstellen */
				if (!$ftp->ChangeDir($FileSystem_ModulePahts['photos'].$folder)
						|| !$ftp->mkdir($validate_id_str)
						|| !$ftp->ChangeDir($FileSystem_ModulePahts['photos'].$folder.$validate_id_str)
						|| !$ftp->mkdir(MODULE_PHOTOS_THUMB)
						|| !$ftp->chmod(MODULE_PHOTOS_THUMB, 0777)) {
					/* Konnte Ordner nicht umbenennen */
					echo ActionReport(REPORT_ERROR, 'Fehler', 'Der Ordner konnte nicht erstellt werden.');
					$error = true;
				}
			}
			else {
				/* Orndernamen existiert bereits */
				echo ActionReport(REPORT_ERROR, 'Albumnamen existiert bereits',
						'Der angegebene Albumnamen existiert bereits.');
				$caption->setError(true);
				if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
					$access_groups->setCssClass('select_groups_view');
				}
				echo $form->getForm();
				$error = true;
			}
		}
		
		if (ACP_ACCESS_SYSTEM_EN) {
			/* Berechtigungen berechnen */
			if ($access_grp->getValue()) {
				$access = 0;
				foreach ($access_groups->getValue() as $group) {
					$access |= $group;
				}
			}
			else if ($access_log->getValue())
				$access = 1;
			else
				$access = 0;
		}
		else {
			$access = 0;
		}
		
		/* Aenderungen vorbereiten */
		if (!is_array($album_info)) {
			/* Sortierung ermitteln */
			$a_albums = array();
			$a_photos = array();
			readAlbumPhotosFtp($ftp, $FileSystem_ModulePahts['photos']
					.$folder, $a_albums, $a_photos, true);
			if (sizeof($a_albums) > 0) {
				$album_info = readAlbumConfigFtp($ftp, $FileSystem_ModulePahts['photos'].$folder.$a_albums[0]);
				$album_info['sort']++;
			}
			else {
				$album_info = array('sort' => 1);
			}
		}
			
		$album_info['title'] = str_replace('|', '', StdString($caption->getValue()));
		$album_info['description'] = str_replace('|', '', StdContent($description->getValue()));
		$album_info['access'] = $access;
		$album_info['locked'] = (int)$locked->getValue();
		
		/* Aenderung abspeichern */
		if ($error == false) {
			/* Neue Configs abspeichern */
			if (writeAlbumConfigFtp($ftp, $FileSystem_ModulePahts['photos'].$folder.$validate_id_str, $album_info)) {
				if (isset($a_photos))
					echo ActionReport(REPORT_OK, 'Album erstellt',
							'Das neue Album wurde erfolgreich erstellt.');
				else
					echo ActionReport(REPORT_OK, 'Album bearbeitet',
							'Die Änderungen wurden erfolgreich übernommen.');
			}
			else {
				/* Nicht moeglich */
				echo ActionReport(REPORT_ERROR,' Fehler', 'Die Änderung konnte nicht gespeichert werden.');
			}
		}
	}
}
else {
	/* Ausgabe des Formulars */
	if ($error == false) {
		if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
			$access_groups->setCssClass('select_groups_view');
		}
		echo $form->getForm();
	}
}

/* FTP Verbindung schliessen */
$ftp->close();

?>