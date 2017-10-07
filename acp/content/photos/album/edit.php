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
 |3.0     | 04.10.2014 | Program erstellt
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

/* Titel */
if (isset($_GET['new'])) {
	echo '<h1 class="first">Fotoalbum erstellen</h1>';
}
else {
	echo '<h1 class="first">Fotoalbum bearbeiten</h1>';
}

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
if (($current_album = readAlbumConfig($ftp, $current_path)) && !($current_album['id'] == 0 && !isset($_GET['new']))) {
	/* Falls in einem Subalbum -> Infos des aktuellen Albums anzeigen */
	if ($current_album['id'] > 0 && isset($_GET['new'])) {
		echo ActionReport(REPORT_INFO, 'Unteralbum',
				'Das neue Album wird im besethenden Album <a href="?page=photos-show&album='.$album.'"><b>'.$current_album['caption'].'</b></a> angelegt.');
	}
	
	/* Formular */
	$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
	$caption = $form->addElement('text', 'caption', 'Titel', null, true);
	$description = $form->addElement('textarea', 'description', 'Beschreibung');
	$description->setRowsCols(4, 20);
	
	if (ACP_ACCESS_SYSTEM_EN) {
		$access_all = $form->addElement('radio', 'access', 'Berechtigung', '0');
		$access_log = $form->addElement('radio', 'access', NULL, '1');
		$access_grp = $form->addElement('radio', 'access', NULL, '2');
		$access_groups = $form->addElement('select', 'access_group', 'Gruppen');
	
		$access_groups->setCssClass('select_groups hide');
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
	if (!$form->checkSubmit() && !isset($_GET['new'])) {
		$caption->setValue($current_album['caption']);
		$description->setValue(StdContentEdit($current_album['description']));
		if ($current_album['locked'])
			$locked->setChecked(true);
		/* Berechtigungen */
		if (ACP_ACCESS_SYSTEM_EN) {
			if ($current_album['access'] == 0)
				$access_all->setChecked(true);
			else if ($current_album['access'] == 1)
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
						(bool)($current_album['access'] & (1<<$row['id'])));
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
			$access_groups->setCssClass('select_groups show');
			/* Ausgabe des Formulars */
			echo $form->getForm();
		}
		else {
			$error = false;
			
	        /* ID Str ermitteln */
			$validate_id_str = ValidateFileSystem($caption->getValue());
			$validate_id_str = str_replace('_', '', $validate_id_str);

			/* Anpassungen im Dateisystem */
			if (isset($_GET['new'])) {
				/* Erstellen eines neuen Albums */
				if (!$ftp->folderExists($current_path.$validate_id_str)) {
					/* Ordner erstellen */
					if (!$ftp->ChangeDir($current_path)
							|| !$ftp->mkdir($validate_id_str)
							|| !$ftp->ChangeDir($current_path.$validate_id_str)
							|| !$ftp->mkdir(MODULE_PHOTOS_THUMB)) {
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
						$access_groups->setCssClass('select_groups show');
					}
					echo $form->getForm();
					$error = true;
				}
			}
			else {
				/* Bearbeiten eines Albums */
				if ($validate_id_str != $current_album['id_str']) {
					$upper_path = substr($current_path, 0, -1*(strlen($current_album['id_str'])+1));
					/* Der Ordner im Dateisystem muss umbenennt werden */
					if (!$ftp->folderExists($upper_path.$validate_id_str)) {
						/* Ordner umbenennen */
						if (!$ftp->ChangeDir($upper_path)
								|| !$ftp->Rename($current_album['id_str'], $validate_id_str)) {
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
							$access_groups->setCssClass('select_groups show');
						}
						echo $form->getForm();
						$error = true;
					}
				}
			}
			
			if ($error == false) {
				/* Benutzerberechtigungen */
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
				
				/* Verzeichnisschutz */
				if (isset($_GET['new'])) {
					$ftp->ChangeDir($current_path.$validate_id_str);
				}
				else {
					$upper_path = substr($current_path, 0, -1*(strlen($current_album['id_str'])+1));
					$ftp->ChangeDir($upper_path.$validate_id_str);
				}
				if ((int)$locked->getValue() == 1 || $access != 0){
					if ($ftemp_htaccess = tmpfile()) {
						/* Verzeichnisschutz erstellen */
						fwrite($ftemp_htaccess, "Order deny,allow\r\nDeny from all");
						fseek($ftemp_htaccess, 0);
						/* Hochladen */
						$ftp->FilePut('.htaccess', $ftemp_htaccess);
						fclose($ftemp_htaccess);
					}
					else {
						echo ActionReport(REPORT_ERROR, 'Verzeichnisschutz nicht aktiv', 'Der Verzeichnisschutz des Albums konnte nicht erstellt werden. Alle Fotos sind öffentlich zugänglich.');
					}
				}
				else if ($ftp->fileExists('.htaccess')) {
					/* Verzeichnisschutz muss entfernt werden, sofern er vohanden ist */
					$ftp->Delete('.htaccess');
				}
			
				/* Abspeichern */
				if (isset($_GET['new'])) {
					/* Das neue Album wird hinten angehaengt (Sortierung) */
					$result = mysql_query('SELECT menu_order FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE menu_sub='.$current_album['id'].'
							ORDER BY menu_order DESC LIMIT 0,1', DB_CMS)
							OR FatalError(FATAL_ERROR_MYSQL);
					if ($line = mysql_fetch_assoc($result)) {
						$menu_order = $line['menu_order'] + 1;
					}
					else {
						$menu_order = 1;
					}
					
					/* Datenbankeintrag erstellen */
					if (mysql_query('INSERT INTO '.DB_TABLE_PLUGIN.'photoalbum(id_str, menu_sub, menu_order, caption, description, writer, timestamp, access, locked)
							VALUES("'.$validate_id_str.'", "'.$current_album['id'].'", "'.$menu_order.'", "'.StdSqlSafety(StdString($caption->getValue())).'",
							"'.StdSqlSafety(StdContent($description->getValue())).'", '.$_SESSION['admin_id'].', '.TIME_STAMP.', '.$access.', 
							'.(int)$locked->getValue().')', DB_CMS)) {
						/* Die Config Datei speichern */
						$config = array(
								'module' => 'photos',
								'album_id' => mysql_insert_id(DB_CMS),
								'access' => $access,
								'locked' => (int) $locked->getValue()
								);
						if ($ftp->writeFolderConfig($current_path.$validate_id_str, $config)) {
							echo ActionReport(REPORT_OK, 'Album erstellt', 'Das neue Album wurde erfolgreich erstellt.');
						}
						else {
							echo ActionReport(REPORT_ERROR, 'Fehler', 'Die Konfigurationsdatei konnte nicht gespeichert werden.');
						}
					}
					else {
						echo ActionReport(REPORT_ERROR,' Fehler', 'Das neue Album konnte nicht gespeichert werden.');
					}
				}
				else {
					/* Datenbankeintrag aktualisieren */
					if (mysql_query('UPDATE '.DB_TABLE_PLUGIN.'photoalbum SET id_str="'.$validate_id_str.'",
							caption="'.StdSqlSafety(StdString($caption->getValue())).'",
							description="'.StdSqlSafety(StdContent($description->getValue())).'",
							writer='.$_SESSION['admin_id'].', timestamp='.TIME_STAMP.',
							access='.$access.', locked='.(int)$locked->getValue().'
							WHERE id='.$current_album['id'], DB_CMS)) {
						/* Die Config Datei speichern */
						$config = array(
								'module' => 'photos',
								'album_id' => $current_album['id'],
								'access' => $access,
								'locked' => (int) $locked->getValue()
								);
						$upper_path = substr($current_path, 0, -1*(strlen($current_album['id_str'])+1));
						if ($ftp->writeFolderConfig($upper_path.$validate_id_str, $config)) {
							echo ActionReport(REPORT_OK, 'Album bearbeitet', 'Die Änderungen wurden erfolgreich übernommen.');
						}
						else {
							echo ActionReport(REPORT_ERROR, 'Fehler', 'Die Konfigurationsdatei konnte nicht gespeichert werden.');
						}
					}
					else {
						echo ActionReport(REPORT_ERROR,' Fehler', 'Die Änderung konnte nicht gespeichert werden.');
					}
				}
			}
		}
	}
	else {
		/* Ausgabe des Formulars */
		if (ACP_ACCESS_SYSTEM_EN && $access_grp->getValue()) {
			$access_groups->setCssClass('select_groups show');
		}
		echo $form->getForm();
	}
}
else {
	if (isset($_GET['new'])) {
		echo ActionReport(REPORT_EINGABE, 'Fehler', 'Es wurde kein Sub-Album ausgewählt!');
	}
	else {
		echo ActionReport(REPORT_EINGABE, 'Album existiert nicht', 'Das gewählte Album existiert nicht mehr!');
	}
}

/* FTP Verbindung schliessen */
$ftp->close();

?>