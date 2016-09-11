<?php

 /*
 =====================================================
 Name ........: MySQL Backup
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: build.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 17.07.2013 | Programm erstellt.
 |1.0.1   | 23.07.2013 | Bugfix: Temp-Datei.
 |1.0.2   | 11.09.2016 | Bugfix: Vercshlüsselung
 -----------------------------------------------------
 Beschreibung :
 Erstellen eines aktuellen Backups.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_ADMIN, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_setting\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_setting\"";
///////////////////////////////////////////////////////

/* Titel */
echo "<h1 class=\"first\">Datenbank Sicherung</h1>";

/* Formular */
$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
$download = $form->addElement('checkbox', 'download', 'Lokal Speichern', '1');
$download->setChecked(true);
$submit = $form->addElement('submit', 'button', NULL, 'Sichern');
/* Auswertung */
if ($form->checkForm()) {
	$backup_file = tempnam(FILESYSTEM_TEMP, 'dump');
	           
	$command = 'mysqldump --add-drop-table --no-create-db --all-databases --complete-insert '
			.'--host="'.DB_HOST.'" --user="'.DB_USER.'" --password="'.DB_PASSWORD.'" '
			.' > '.$backup_file;
	
	/* Ausfuehren */
	exec($command);
	
	/* Sicherheitskopie verschluesseln */
	$key = str_pad(substr(DB_PASSWORD, 0, 16), 16 , "q");
	$stream = file_get_contents($backup_file);
	$stream = trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $stream, MCRYPT_MODE_ECB, 
			mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	file_put_contents($backup_file, $stream);
	
	/* In FTP verzeichnis kopieren */
	$ftp = new ftp();
	if ($ftp->ChangeDir($FileSystem_ModulePahts['mysqlbackups'])) {
		$filename = date('Y-m-d-H-i-s', TIME_STAMP).'.sql';
		if ($ftp->FilePut($filename, $backup_file)) {
			echo ActionReport(REPORT_OK, 'Sicherungskopie erstellt',
					'Es wurde erfolgreich eine Sicherungskopie der gesamten Datenbank erstellt!');
			/* Sicherheitskopie automatisch herunterladen */
			if ($download->getValue()) {
				echo ActionReport(REPORT_INFO, 'Download', 
						'Falls der Download nicht automatisch startet, versuchen Sie es mit dem 
						<a href="../download.php?path='.$FileSystem_ModulePahts['mysqlbackups']
						.$filename.'">Direcktlink</a>.');
				$acp_info_header = '<meta http-equiv="refresh" content="0; url=../download.php?path='
						.$FileSystem_ModulePahts['mysqlbackups'].$filename.'" />';
			}
		}
		else {
			echo ActionReport(REPORT_ERROR, 'Fehlgeschlagen',
					'Es konnte keine Sicherungskopie der Datenbank generiert werden!');
		}
	}
	else {
		echo ActionReport(REPORT_ERROR, 'Verzeichnis existiert nicht',
				'Es existiert kein Verzeichnis für Datenbank Sicherungskopien!');
	}
	$ftp->close();
	unlink($backup_file);
}
else {
	/* Ausgabe Formular */
	echo ActionReport(REPORT_INFO, 'Sicherheitshinweis', 
			'Um eine Wiederherstellung nach einem kompletten Serverausfall zu gewährleisten, 
			muss die Sicherheitskopie auch lokal gespeichert werden.');
	echo $form->getForm();
}

?>