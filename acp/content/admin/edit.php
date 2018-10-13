<?php

 /*
 =====================================================
 Name ........: Administratoren bearbeiten
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
 |1.0     | 12.07.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Erstellen und bearbeiten von Administratoren.

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
if (isset($_GET['id']))
	echo "<h1 class=\"first\">Administrator bearbeiten</h1>";
else
	echo "<h1 class=\"first\">Neuer Administrator</h1>";

/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$name = $form->addElement('text', 'name', 'Name', NULL, true);
$login = $form->addElement('text', 'login', 'Benutzernamen', NULL, true);
if (isset($_GET['id']))
	$password = $form->addElement('password', 'password', 'Neues Passwort');
else
	$password = $form->addElement('password', 'password', 'Passwort', NULL, true);
$email = $form->addElement('text', 'email', 'Email Adresse', NULL, true);
$email->setCustomValidation('email', NULL);

$access_ctr = 0;
$access = array();
$access[$access_ctr] = $form->addElement('checkbox', 'access', 'Berechtigungen', ACP_ACCESS_WEBSITE);
$access[$access_ctr++]->setSubLabel("Verwaltung von Kategorien und Seiten");
if (ACP_FILE_SYSTEM_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_FILESYSTEM_IMAGES);
	$access[$access_ctr++]->setSubLabel("Bilder hochladen");
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_FILESYSTEM);
	$access[$access_ctr++]->setSubLabel("Verwalten von Dateien und Ordner im Dateisystem");
}
if (ACP_USER_SYSTEM_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_USER);
	$access[$access_ctr++]->setSubLabel("Verwalten von Benutzern");
}
$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_ADMIN);
$access[$access_ctr++]->setSubLabel("Verwalten von Einstellungen und Administratoren");
if (ACP_MODULE_NEWS_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_NEWS);
	$access[$access_ctr++]->setSubLabel("Neuigkeiten schreiben");
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_NEWS_COM);
	$access[$access_ctr++]->setSubLabel("Verwalten von Neuigkeiten und Kommentaren");
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_NEWS_CAT);
	$access[$access_ctr++]->setSubLabel("Kategorien der Neuigkeiten verwalten");
}
if (ACP_MODULE_NEWSLETTER_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_NEWS_LETTER);
	$access[$access_ctr++]->setSubLabel("Newsletter versenden");
}
if (ACP_MODULE_POLL_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_POLL);
	$access[$access_ctr++]->setSubLabel("Verwalten von Umfragen");
}
if (ACP_MODULE_GUESTBOOK_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_GUESTBOOK);
	$access[$access_ctr++]->setSubLabel("Verwalten des Gästebuches");
}
if (ACP_MODULE_PHOTOS_EN) {
	$access[$access_ctr] = $form->addElement('checkbox', 'access', NULL, ACP_ACCESS_M_PHOTOS);
	$access[$access_ctr++]->setSubLabel("Verwalten des Fotoalbums");
}

$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

/* Auswertung */
if ($form->checkSubmit() && $form->checkForm()) {
	/* Loginnamen pruefen */
	if (isset($_GET['id'])) {
		$result = Database::instance()->query("SELECT admin_id FROM ".DB_TABLE_ROOT."cms_admin
				WHERE login='".StdSqlSafety($login->getValue())."' && admin_id!=".StdSqlSafety($_GET['id']))
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	else {
		$result = Database::instance()->query("SELECT admin_id FROM ".DB_TABLE_ROOT."cms_admin
				WHERE login='".StdSqlSafety($login->getValue())."'")
				OR FatalError(FATAL_ERROR_MYSQL);
	}
	if ($result->num_rows == 0) {
		/* Rechte */
		$access_int = 0;
		for ($i=0; $i<sizeof($access); $i++) {
			$access_int |= $access[$i]->getValue();
		}
		/* Speichern */
		if (isset($_GET['id'])) {
			/* Ausschliessung verhindenr */
			if ($_SESSION['admin_id'] == $_GET['id']) {
				$access_int |= ACP_ACCESS_ADMIN;
			}
			/* Neues Passwort */
			if ($password->getValue() != "")
				$sql_pw = ", password='".sha1($password->getValue())."' ";
			else
				$sql_pw = "";
			if (Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_admin
					SET login='".StdSqlSafety($login->getValue())."', name='".StdSqlSafety($name->getValue())."',
					email='".StdSqlSafety($email->getValue())."', access=".$access_int.$sql_pw."
					WHERE admin_id=".StdSqlSafety($_GET['id']))) {
				echo ActionReport(REPORT_OK, "Administrator geändert",
						"Der Administrator wurde erfolgreich geändert!");
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Der Administrator konnte nicht geändert werden!<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
			}
		}
		else {
			if (Database::instance()->query("INSERT INTO ".DB_TABLE_ROOT."cms_admin(login, password, name, email, access)
					VALUE('".StdSqlSafety($login->getValue())."', '".sha1($password->getValue())."',
					'".StdSqlSafety($name->getValue())."', '".StdSqlSafety($email->getValue())."',
					".$access_int.")")) {
				echo ActionReport(REPORT_OK, "Administrator erstellt",
						"Der neue Administrator wurde erfolgreich erstellt!");
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Der neue Administrator konnte nicht erstellt werden!<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
			}
		}
	}
	else {
		/* Loginname existiert bereits */
		$login->setError(true);
		echo ActionReport(REPORT_EINGABE, "Benutzernamen existiert bereits",
				"Der angegebene Benutzernamen existiert bereits!");
		echo $form->getForm();
	}
}
else {
	if (isset($_GET['id'])) {
		/* Formulare fuellen */
		$result = Database::instance()->query("SELECT admin_id, login, access, name, email FROM ".DB_TABLE_ROOT."cms_admin
				WHERE locked=0 && admin_id=".StdSqlSafety($_GET['id']))
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			$login->setValue($line['login']);
			$name->setValue($line['name']);
			$email->setValue($line['email']);

			for ($i=0; $i<sizeof($access); $i++) {
				if ($access[$i]->getFieldValue() & $line['access'])
					$access[$i]->setChecked(true);
			}

			/* Formularausgabe */
			echo $form->getForm();
		}
		else {
			/* Admin existiert nicht */
			echo ActionReport(REPORT_EINGABE, "Administrator existiert nicht", "Dieser Administrator existiert nicht!");
		}
	}
	else {
		/* Formularausgabe */
		echo $form->getForm();
	}
}

?>
