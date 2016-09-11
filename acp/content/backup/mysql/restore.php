<?php

 /*
 =====================================================
 Name ........: MySQL Backup
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: restore.php
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
 Spielt eine Sicherheitskopie auf den MySQL Server.

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
// Im inhalt wegen Notbremse bei falschem Passwort
//echo "<h1 class=\"first\">Datenbank Wiederherstellen</h1>";

$ftp = new ftp();
if ($ftp->ChangeDir($FileSystem_ModulePahts['mysqlbackups'])) {
	if (isset($_GET['backupfile']) && $ftp->fileExists($_GET['backupfile'])) {
		/* Formular */
		$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
		$file = $form->addElement('text', 'file', 'Sicherheitskopie', StdString($_GET['backupfile']));
		$file->setReadonly(true);
		$date = $file = $form->addElement('text', 'date', 'Datum der Sicherung');
		$date->setReadonly(true);
		$confirm1 = $form->addElement('checkbox', 'confirm', 'Sicherheitsfragen', 1, true);
		$confirm1->setSubLabel('Datenbank zurücksetzten und Sicherheitkopie einspielen');
		$confirm2 = $form->addElement('checkbox', 'confirm', '', 2, true);
		$confirm2->setSubLabel('Aktuelle Sicherheitskopie vorhanden');
		$password = $form->addElement('password', 'password', 'Ihr Passwort', null, true);
		$submit = $form->addElement('submit', 'button', NULL, 'Wiederherstellen');
		
		$file->setValue(StdString($_GET['backupfile']));
		$time = $ftp->fileTime($_GET['backupfile']);
		$date->setValue(printDate($time).' '.date(FORMAT_TIME, $time));

		/* Auswertung */
		if ($form->checkForm()) {
			/* Passwort pruefen */
			$result = mysql_query("SELECT password FROM ".DB_TABLE_ROOT."cms_admin
					WHERE admin_id=".StdSqlSafety($_SESSION['admin_id']), DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line = mysql_fetch_array($result)) {
				if ($line['password'] == sha1($password->getValue())) {
					$backup_file = tempnam(FILESYSTEM_TEMP, 'sql');
					/* Sicherheitskopie einlesen und entschluesseln */
					$key = str_pad(substr(DB_PASSWORD, 0, 16), 16 , "q");
					$stream = $ftp->FileContents($_GET['backupfile']);
					$stream = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($stream), MCRYPT_MODE_ECB,
							mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
					
					/* Datei lokal entschluesselt zur Verfuegung stellen */
					file_put_contents($backup_file, $stream) OR die('File Put Error');
					
					/* MySQL wiederherstellen */
					$command = 'mysql --host="'.DB_HOST.'" --user="'.DB_USER.'" --password="'.DB_PASSWORD.'"'
							.' '.DB_NAME.' < '.$backup_file;
					exec($command);
					unlink($backup_file);
				    
				    echo "<h1 class=\"first\">Datenbank Wiederherstellen</h1>";
				    echo ActionReport(REPORT_OK, 'Datenbank wiederhergestellt', 
							'Die Sicherheitskopie wurde erfolgreich eingespielt und die Datenbank wiederhergestellt.');
				}
				else {
					/* Falsches Passwort */
					SessionDelete();
					die(LoginFormular(1));
				}
			}
			else {
				/* Admin nicht gefunden -> Notbremse */
				SessionDelete();
				die(LoginFormular(0));
			}
		}
		else {
			/* Ausgabe Formular */
			echo "<h1 class=\"first\">Datenbank Wiederherstellen</h1>";
			echo ActionReport(REPORT_INFO, 'Sicherheitshinweis', 
					'Durch die Widerherstellung der Datenbank wird der jetzige Stand komplett gelöscht und der Stand der Sicherheitskopie wird eingespielt. Zu beachten gilt daher, dass zum Beispiel zwischenzeitlich geänderte Passwörter zurückgesetzt werden oder die Besucher seit der Sicherheitskopie nicht mehr gezählt werden.</p>
					<p>Um nur Seiteninhalte wiederherzustellen sollten die vorgesehenen Sicherheitskopien verwendet werden. Diese sind über den Menüstamm erreichbar und verändern den Rest der Datenbank nicht.</p>
					<p>Bei Unsicherheiten nehmen Sie Kontakt mit dem Supportteam auf.');
			echo $form->getForm();
		}
	}
	else {
		echo "<h1 class=\"first\">Datenbank Wiederherstellen</h1>";
		echo ActionReport(REPORT_EINGABE, 'Sicherheitskope existiert nicht',
				'Die gewünschte Sicherheitskopie wurde auf dem Server nicht gefunden!');
	}
}
else {
	echo "<h1 class=\"first\">Datenbank Wiederherstellen</h1>";
	echo ActionReport(REPORT_ERROR, 'Verzeichnis existiert nicht',
			'Es existiert kein Verzeichnis mit Datenbank Sicherungskopien!');
}
$ftp->close();

?>