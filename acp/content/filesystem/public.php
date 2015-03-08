<?php

 /*
 =====================================================
 Name ........: File System _public
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: public.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 01.10.2008 | _public erstellt
 |1.1     | 12.05.2011 | Programmuebernahme in v2
 |1.1.1   | 05.07.2011 | Bearbeitungsfunktionen
 |1.2     | 14.02.2012 | Modul: Ordner sperre
 |2.0     | 05.12.2012 | Vollstaendig FTP abgetrennt
 |2.0.1   | 07.12.2014 | Infoboxen
 |2.0.2   | 08.03.2015 | Ermittlung des Zielordners
 -----------------------------------------------------
 Beschreibung :
 Verwaltung der oeffentlichen Dateien und
 Verzeichnisse.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined('ACP_CHECK_SUM'))	die();
///////////////////////////////////////////////////////
if (!ACP_FILE_SYSTEM_EN)		die();
ACP_AdminAccess(ACP_ACCESS_FILESYSTEM, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
///////////////////////////////////////////////////////

define('PFS_FILE_PATH', '?page=filesystem-public&amp;folder=');

/* Begruessung */
echo '<h1 class="first">Dateisystem</h1>';

$file_table_view = true;

/* FTP Verbindung erstellen */
$ftp = new ftp();

/* Ermittlung des Zielordners */
if (isset($_GET['folder'])) {
	if (preg_match("/(^|\/)[.]{1,2}\//", $_GET['folder']) == 0) {
		if ($_GET['folder'] == '/'
				|| (substr($_GET['folder'], 0, 1) == '/' && substr($_GET['folder'], -1, 1) == '/' && $ftp->folderExists($_GET['folder']))) {
			$current_folder = $_GET['folder'];
		}
		else {
			$file_table_view = false;
			unset($_GET['do']);
			echo ActionReport(REPORT_EINGABE, 'Verzeichnis existiert nicht', 'Das gewünschte Verzeichnis existiert nicht!');
		}
	}
	else {
		$file_table_view = false;
		unset($_GET['do']);
		echo ActionReport(REPORT_EINGABE, 'Eingabe nicht erlaubt', 'Es sind keine relativen Pfade erlaubt!');
	}
}
else {
	$current_folder = '/';
}

/* Benutzer-Aktionen im Dateisystem */
if (isset($_GET['do'])) {
	if (!in_array($current_folder, $FileSystem_ModulePahts)) {
		switch($_GET['do']) {
			/* Neues Verzeichnis anlegen */
			case 'newfolder'://mkdir
				/* Formular */
				$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
				$folder = $form->addElement('text', 'folder', 'Verzeichnisname', NULL, true);
				$submit = $form->addElement('submit', 'button', NULL, 'Anlegen');
				/* Auswertung */
				if ($form->checkForm()) {
					/* Neues Verzeichnis anlegen */
					$new_folder = ValidateFileSystem($folder->getValue());
					if (!$ftp->folderExists($current_folder.$new_folder)) {
						/* Ordner anlegen */
						if ($ftp->mkdir($current_folder.$new_folder)) {
							echo ActionReport(REPORT_OK, 'Verzeichnis erstellt',
									'Das neue Verzeichnis wurde erfolgreich erstellt!');
							/* Ins neue Verzeichnis wechseln */
							$current_folder .= $new_folder.'/';
						}
						else {
							echo ActionReport(REPORT_ERROR, 'FTP Fehler',
									'Das neue Verzeichnis konnte leider nicht erstellt werden!');
							$file_table_view = false;
						}
					}
					else {
						/* Verzeichnis existiert bereits */
						echo printInfoBox('Neues Verzeichnis anlegen',
								'<p>Im bestehenden Verzeichnis: <a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a></p>');
						echo ActionReport(REPORT_EINGABE, 'Existiert bereits',
								'Dieser Verzeichnisnamen existiert bereits!');
						/* Ausgabe Formular */
						echo $form->getForm();
						$file_table_view = false;
					}
				}
				else {
					/* Ausgabe Formular */
					echo printInfoBox('Neues Verzeichnis anlegen',
							'<p>Im bestehenden Verzeichnis: <a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a></p>');
					echo $form->getForm();
					$file_table_view = false;
				}
				break;
	
			/* Datei hochladen */
			case 'upload':
				/* Formular */
				$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
				if (ACP_MODULE_DRAG_AND_DROP) {
					$file = $form->addElement('dropfiles', 'file', 'Dateien', NULL, true);
				}
				else {
					$file = $form->addElement('file', 'file', 'Datei', NULL, true);
				}
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
								$FileSystem_AllowedDataTypes)) {
							/* Existiert dieser Dateinamen bereits */
							if ($overwrite->getValue() || !$ftp->fileExists($current_folder.$file_data['name'])) {
								/* Datei auf FPT Server kopieren */
								if ($ftp->FilePut($current_folder.$file_data['name'], $file_data['tmp_name'])) {
									echo ActionReport(REPORT_OK, 'Datei hochgeladen',
											'Die Datei wurde erfolgreich hochgeladen!');
								}
								else {
									echo ActionReport(REPORT_ERROR, 'FTP Fehler',
											'Die Datei konnte nicht auf den FTP Server kopiert werden!');
									$view = $file_table_view;
								}
							}
							else {
								/* Dateinamen existiert bereits */
								echo printInfoBox('Datei hochladen',
										'<p>Im bestehenden Verzeichnis: <a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a></p>');
								echo ActionReport(REPORT_EINGABE, 'Dateinamen existiert bereits',
										'Eine andere Datei mit gleichem Namen existiert bereits in diesem Verzeichnis!');
								echo $form->getForm();
								$file_table_view = false;
							}
						}
						else {
							/* Unerlaubter Dateityp */
							echo printInfoBox('Datei hochladen',
									'<p>Im bestehenden Verzeichnis: <a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a></p>');
							$last = array_pop($FileSystem_AllowedDataTypes);
							echo ActionReport(REPORT_EINGABE, 'Dateityp nicht erlaubt',
									'Sie wollten einen nicht erlaupten Dateityp hochladen!
									<br />Erlaubten Dateitypen: '
									.implode(', ', $FileSystem_AllowedDataTypes).' und '.$last);
							echo $form->getForm();
							$file_table_view = false;
						}
					}
					else {
						/* Datei zu gross */
						echo printInfoBox('Datei hochladen',
								'<p>Im bestehenden Verzeichnis: <a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a></p>');
						echo ActionReport(REPORT_EINGABE, 'Datei ist zu gross',
								'Die ausgewählte Datei ist zu gross!
									Die maximal erlaubte Dateigrösse ist '.BinaryMultiples(FILE_SIZE_LIMIT).'.');
						echo $form->getForm();
						$file_table_view = false;
					}
				}
				else {
					/* Ausgabe Formular */
					echo printInfoBox('Datei hochladen',
							'<p>Im bestehenden Verzeichnis: <a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a></p>');
					echo $form->getForm();
					$file_table_view = false;
				}
				break;
	
			/* Ordner umbenennen */
			case 'rename_folder':
				if (isset($_GET['foldername']) && $ftp->folderExists($current_folder.$_GET['foldername'])) {
					/* Formular */
					$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
					$folder = $form->addElement('text', 'folder', 'Verzeichnisnamen',
							StdString($_GET['foldername']), true);
					$submit = $form->addElement('submit', 'button', NULL, 'Umbenennen');
					/* Auswertung */
					if ($form->checkForm()) {
						$new_folder = ValidateFileSystem($folder->getValue());
						if (!$ftp->folderExists($current_folder.$new_folder)) {
							/* Vorbereiten um Ordner anzulegen */
							if ($ftp->Rename($current_folder.$_GET['foldername'], $new_folder)) {
								echo ActionReport(REPORT_OK, 'Verzeichnis umbenennt',
										'Das Verzeichnis wurde erfolgreich umbenennt!');
							}
							else {
								echo ActionReport(REPORT_ERROR, 'FTP Fehler',
										'Das Verzeichnis konnte leider nicht umbenennt werden!');
								$file_table_view = false;
							}
						}
						else {
							if ($_GET['foldername'] == $new_folder) {
								echo ActionReport(REPORT_INFO, 'Nicht geändert',
										'Der Verzeichnisnamen wurde von Ihnen nicht geändert!');
							}
							else {
								/* Verzeichnis existiert bereits */
								echo printInfoBox('Verzeichnis umbenennen',
										'<p>Das Verzeichnis '.StdString($_GET['foldername']).' aus dem Verzeichnis
										<a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a> umbenennen.</p>');
								echo ActionReport(REPORT_EINGABE, 'Existiert bereits',
										'Dieser Verzeichnisnamen existiert bereits in diesem Verzeichnis!');
								/* Ausgabe Formular */
								echo $form->getForm();
								$file_table_view = false;
							}
						}
					}
					else {
						/* Ausgabe Formular */
						echo printInfoBox('Verzeichnis umbenennen',
								'<p>Das Verzeichnis '.StdString($_GET['foldername']).' aus dem Verzeichnis
								<a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a> umbenennen.</p>');
						echo $form->getForm();
						$file_table_view = false;
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Verzeichnis existiert nicht',
							'Das gewünschte Verzeichnis existiert nicht!');
					$file_table_view = false;
				}
				break;
	
			/* Datei umbenennen */
			case 'rename_file':
				if (isset($_GET['filename']) && $ftp->fileExists($current_folder.$_GET['filename'])) {
					/* Formular */
					$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
					$file = $form->addElement('text', 'file', 'Dateinamen', StdString($_GET['filename']), true);
					$submit = $form->addElement('submit', 'button', NULL, 'Umbenennen');
					/* Auswertung */
					if ($form->checkForm()) {
						$new_file = ValidateFileSystem($file->getValue(), '.');
						/* Dateiendung erlaubt ? */
						if (in_array(array_pop(explode('.', $new_file)), $FileSystem_AllowedDataTypes)) {
							if (!$ftp->fileExists($current_folder.$new_file)) {
								/* Vorbereiten um Ordner anzulegen */
								if ($ftp->Rename($current_folder.$_GET['filename'], $new_file)) {
									echo ActionReport(REPORT_OK, 'Datei umbenennt',
											'Die Datei wurde erfolgreich umbenennt!');
								}
								else {
									echo ActionReport(REPORT_ERROR, 'FTP Fehler',
											'Die Datei konnte leider nicht umbenennt werden!');
									$file_table_view = false;
								}
							}
							else {
								if ($_GET['filename'] == $new_file) {
									echo ActionReport(REPORT_INFO, 'Nicht geändert',
											'Der Dateinamen wurde von Ihnen nicht geändert!');
								}
								else {
									/* Verzeichnis existiert bereits */
									echo printInfoBox('Datei umbenennen',
											'<p>Die Datei '.StdString($_GET['filename']).' aus dem Verzeichnis
											<a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a> umbenennen.</p>');
									echo ActionReport(REPORT_EINGABE, 'Existiert bereits',
											'Dieser Dateinamen existiert bereits in diesem Verzeichnis!');
									/* Ausgabe Formular */
									echo $form->getForm();
									$file_table_view = false;
								}
							}
						}
						else {
							/* Dateiendung nicht erlaubt */
							echo printInfoBox('Datei umbenennen',
									'<p>Die Datei '.StdString($_GET['filename']).' aus dem Verzeichnis
									<a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a> umbenennen.</p>');
							$last = array_pop($FileSystem_AllowedDataTypes);
							echo ActionReport(REPORT_EINGABE, 'Dateityp nicht erlaubt',
									'Der eingegebene Dateitype ist nicht erlaubt!
									<br />Erlaubten Dateitypen: '
									.implode(', ', $FileSystem_AllowedDataTypes).' und '.$last);
							echo $form->getForm();
							$file_table_view = false;
						}
					}
					else {
						/* Ausgabe Formular */
						echo printInfoBox('Datei umbenennen',
								'<p>Die Datei '.StdString($_GET['filename']).' aus dem Verzeichnis
								<a href="?page=filesystem-public&folder='.$current_folder.'">'.FILESYSTEM_DIR.$current_folder.'</a> umbenennen.</p>');
						echo $form->getForm();
						$file_table_view = false;
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Datei existiert nicht',
							'Die gewünschte Datei existiert nicht!');
					$file_table_view = false;
				}
				break;
	
			case 'rmdir':
				if (isset($_GET['foldername']) && $ftp->folderExists($current_folder.$_GET['foldername'])) {
					if (!in_array($current_folder.$_GET['foldername'], $FileSystem_ModulePahts)) {
						if ($ftp->rmdir($current_folder.$_GET['foldername'])) {
							echo ActionReport(REPORT_OK, 'Verzeichnis gelöscht',
									'Das Verzeichnis wurde mit allen Inhalten erfolgreich gelöscht!');
						}
						else {
							echo ActionReport(REPORT_ERROR, 'FTP Fehler',
									'Das Verzeichnis konnte nicht gelöscht werden!');
							$file_table_view = false;
						}
					}
					else {
						echo ActionReport(REPORT_EINGABE, 'Keine berechtigung',
								'Die Modul-Verzeichnisse dürfen nicht gelöscht werden!');
						$file_table_view = false;
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Fehler',
							'Es wurde kein Verzeichnis ausgewählt 
							oder das ausgewählte Verzeichnis wurde nicht gefunden!');
					$file_table_view = false;
				}
				break;
	
			/* Datei loeschen */
			case 'delete':
				if (isset($_GET['filename']) && $ftp->fileExists($current_folder.$_GET['filename'])) {
					if ($ftp->Delete($current_folder.$_GET['filename'])) {
						echo ActionReport(REPORT_OK, 'Datei gelöscht', 'Die Datei wurde erfolgreich gelöscht!');
					}
					else {
						echo ActionReport(REPORT_ERROR, 'FTP Fehler', 'Die Datei konnte nicht gelöscht werden!');
						$file_table_view = false;
					}
				}
				else {
					echo ActionReport(REPORT_EINGABE, 'Fehler',
							'Es wurde keine Datei ausgewählt oder die ausgewählte Datei wurde nicht gefunden!');
					$file_table_view = false;
				}
				break;
		}
	}
	else {
		echo ActionReport(REPORT_EINGABE, 'Keine berechtigung',
				'Die Modul-Verzeichnisse dürfen nicht bearbeitet werden!');
		$file_table_view = false;
	}
}


/* File System Datenanzeige */
if ($file_table_view) {
	/* Ordner einlesen */
	if ($folder_pointer = $ftp->openDir($current_folder)) {
		
		/* Verzeichnisse und Dateien sortieren */
		$folder_pointer->sortList('name');
		
		/* Upload Menue */
		echo '<p><img src="img/icons/ftp/file_upload.png" alt="" />
					<a href="'.PFS_FILE_PATH.$current_folder.'&amp;do=upload">Datei hochladen</a>
					&nbsp; | &nbsp; <img src="img/icons/ftp/folder_create.png" alt="" />
					<a href="'.PFS_FILE_PATH.$current_folder.'&amp;do=newfolder">Neues Verzeichnis</a></p>';
	
		/* Tabelle mit Pfad als Titel */
		echo '<table><tr class="table_title"><td class="icon"><img src="img/icons/ftp/path.png" alt="" /></td>
				<td>'.FILESYSTEM_DIR.$current_folder.'</td>
				<td>Änderungsdatum</td>
				<td>Grösse</td>
				<td class="icon"></td><td class="icon"></td></tr>';
	
		$data_ctr = 0;
	
		/* Eine Ebene hoeher */
		if ($current_folder != '' && $current_folder != '/') {
			$data_ctr++;
			echo '<tr class="table_odd">
					<td class="icon"><img src="img/icons/ftp/return.png" alt="" /></td>
					<td><a href="'.PFS_FILE_PATH.preg_replace("/(.*)\/(.*)\/$/i", "$1", $current_folder).'/">Übergeordnetes Verzeichnis</a></td>
					<td colspan="4"></td></tr>';
		}
		
		/* Alle Verzeichnisse und Dateine des Ordners einzeln verarbeiten */
		while($file = $folder_pointer->readDir()) {
			if(!in_array($current_folder.$file."/", $FileSystem_ModulePahts)) {
				/* Datei und Ordner zaehlen */
				$data_ctr++;

				if ($data_ctr % 2)
					$table_hover = 'odd';
				else
					$table_hover = 'even';

				if ($folder_pointer->isDir()) {
					/* Ist ein Ordner */
					echo '<tr class="table_'.$table_hover.'">
					  <td class="icon"><img src="img/icons/ftp/folder.png" alt="" /></td>
					  <td><a href="'.PFS_FILE_PATH.$current_folder.$file.'/">'.$file.'/</a></td>
	  				  <td>'.$folder_pointer->fileTime('str').'</td>
					  <td></td>
					  <td class="icon"><a href="'.PFS_FILE_PATH.$current_folder.'&amp;foldername='.$file.'&amp;do=rename_folder"
					  		onmouseover="Tip(\'Verzeichnis umbenennen\')" onmouseout="UnTip()">
					  		<img src="img/icons/ftp/folder_rename.png" alt="" /></a></td>
					  <td class="icon">
					  		<a href="javascript:confirmDeletion(\''.PFS_FILE_PATH.$current_folder.'&amp;foldername='.$file.'&amp;do=rmdir\',
							  \'Möchten Sie dieses Verzeichnis mit allen Inhalten wirklich löschen?\')" onmouseover="Tip(\'Verzeichnis löschen\')"
							  onmouseout="UnTip()">
					  		<img src="img/icons/ftp/folder_delete.png" alt="" /></a></td>
					</tr>';
				}
				else {
					/* Eine Datei */
					echo '<tr class="table_'.$table_hover.'">
					  <td class="icon"><img src="img/icons/ftp/file.png" alt="" /></td>
					  <td><a href="'.FILESYSTEM_DIR.$current_folder.$file.'" target="_blank">'.$file.'</a></td>
	  				  <td>'.$folder_pointer->fileTime('str').'</td>
					  <td>'.BinaryMultiples($folder_pointer->fileSize()).'</td>
					  <td class="icon"><a href="'.PFS_FILE_PATH.$current_folder.'&amp;filename='.$file.'&amp;do=rename_file"
					  		onmouseover="Tip(\'Datei umbenennen\')" onmouseout="UnTip()">
					  		<img src="img/icons/ftp/file_rename.png" alt="Umbenennen" /></a></td>
					  <td class="icon">
					  		<a href="javascript:confirmDeletion(\''.PFS_FILE_PATH.$current_folder.'&amp;filename='.$file.'&amp;do=delete\',
							  \'Möchten Sie diese Datei wirklich löschen?\')"
							  onmouseover="Tip(\'Datei löschen\')" onmouseout="UnTip()">
					  		<img src="img/icons/ftp/file_delete.png" alt="" /></a></td>
					</tr>';
				}
			}
		}
		$ftp->closeDir($folder_pointer);
		echo "</table>";
	}
	else {
		echo ActionReport(REPORT_ERROR, 'Verzeichnis existiert nicht', 'Das gewünschte Verzeichnis existiert nicht!');
	}
}

/* FTP Verbindung schliessen */
$ftp->close();

?>