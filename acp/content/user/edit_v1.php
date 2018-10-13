<?php

 /*
 =====================================================
 Name ........: Benutzer Verwalten
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
 |1.0     | 06.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Erstellen und bearbeiten von Benutzern.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_USER_SYSTEM_EN)		die();
ACP_AdminAccess(ACP_ACCESS_USER, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_user\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_user\"";
///////////////////////////////////////////////////////

/* Titel */
if (isset($_GET['id']))
	echo "<h1 class=\"first\">Benutzer bearbeiten</h1>";
else
	echo "<h1 class=\"first\">Neuer Benutzer</h1>";
	
/* Formular */
$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
if (ACP_ACCESS_SYSTEM_EN) {
	$login = $form->addElement('text', 'login', 'Benutzernamen', NULL, true);
	if (isset($_GET['id']))
		$password = $form->addElement('password', 'password', 'Neues Passwort');
	else
		$password = $form->addElement('password', 'password', 'Passwort', NULL, true);
}
$name = $form->addElement('text', 'name', 'Name', NULL, true);
$email = $form->addElement('text', 'email', 'Email Adresse', NULL, true);
$email->setCustomValidation('email', NULL);
$email_show = $form->addElement('checkbox', 'email_show', 'Emailadresse anzeigen', 1);
if (ACP_MODULE_NEWSLETTER_EN)
	$email_letter = $form->addElement('checkbox', 'email_letter', 'Newsletter abonieren', 1);
$tel = $form->addElement('text', 'tel', 'Telefonnummer');
$website = $form->addElement('text', 'website', 'Website');
$website->setCustomValidation('website', NULL);
if (ACP_USER_POINTS) {
	$points = $form->addElement('text', 'points', 'Punkte', NULL, true);
}
$description = $form->addElement('text', 'description', 'Beschreibung');

$access_groups = $form->addElement('select', 'access_group', 'Gruppen');
$access_groups->setMultiple(true);
$access_groups->setSize(7);

$locked = $form->addElement('checkbox', 'locked', 'Sperren', 1);
$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

/* Defaultwerte Setzen */
if (!$form->checkSubmit() && isset($_GET['id'])) {
	/* Daten lesen */
	$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_ROOT."cms_access_user
			WHERE user_id=".StdSqlSafety($_GET['id']))
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		if (ACP_ACCESS_SYSTEM_EN)
			$login->setValue($line['user_login']);
		$name->setValue($line['user_name']);
		$email->setValue($line['user_email']);
		$tel->setValue($line['user_tel']);
		$website->setValue($line['user_website']);
		$description->setValue($line['user_description']);
		if (ACP_USER_POINTS)
			$points->setValue($line['user_points']);
		if ($line['user_email_show'])
			$email_show->setChecked(true);
		if (ACP_MODULE_NEWSLETTER_EN && $line['user_allow_newsletter'])
			$email_letter->setChecked(true);
		if ($line['user_locked'])
			$locked->setChecked(true);
		/* Gruppen */
		$result = Database::instance()->query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
				ORDER BY name ASC")
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = $result->fetch_assoc()) {
			$access_groups->addOption($row['name'], (1<<$row['id']),
					(bool) ($line['user_access'] & (1<<$row['id'])));
		}
	}
	else {
		/* Benutzer existiert nicht */
		echo ActionReport(REPORT_EINGABE, "Benutzer existiert nicht", "Dieser Benutzer existiert nicht!");
		$dont_show_form = true;
	}
}
else {
	/* Gruppen */
	$result = Database::instance()->query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
			ORDER BY name ASC")
			OR FatalError(FATAL_ERROR_MYSQL);
	while ($row = $result->fetch_assoc()) {
		$access_groups->addOption($row['name'], (1<<$row['id']));
	}
}

/* Auswertung */
if (!isset($dont_show_form)) {
	if ($form->checkSubmit() && $form->checkForm()) {
		if (ACP_ACCESS_SYSTEM_EN) {
			/* Loginnamen pruefen */
			$validate_login = StdSqlSafety(substr($login->getValue(), 0, 20));
			if (isset($_GET['id'])) {
				$result = Database::instance()->query("SELECT user_id FROM ".DB_TABLE_ROOT."cms_access_user
						WHERE user_login='".$validate_login."' && user_id!=".StdSqlSafety($_GET['id']))
						OR FatalError(FATAL_ERROR_MYSQL);
			}
			else {
				$result = Database::instance()->query("SELECT user_id FROM ".DB_TABLE_ROOT."cms_access_user
						WHERE user_login='".$validate_login."'")
						OR FatalError(FATAL_ERROR_MYSQL);
			}
		}
		if (!ACP_ACCESS_SYSTEM_EN || $result->num_rows == 0) {
			/* Gruppen */
			$access = 1;
			if (sizeof($access_groups->getValue())) {
				foreach ($access_groups->getValue() as $group) {
					$access |= $group;
				}
			}
			/* Abspeichern */
			if (isset($_GET['id'])) {
				/* ID Str ermitteln */
				$validate_id_str = getIdStr($name->getValue(), DB_TABLE_ROOT."cms_access_user",
						"&& user_id!=".StdSqlSafety($_GET['id']), "user_id_str");
				
				/* Vorbereiten der Spezialfaelle */
				$sql = "";
				if (ACP_ACCESS_SYSTEM_EN) {
					$sql .= ", user_login='".$validate_login."' ";
					if ($password->getValue() != "")
						$sql .= ", user_password='".sha1($password->getValue())."' ";
				}
				if (ACP_MODULE_NEWSLETTER_EN)
					$sql .= ", user_allow_newsletter=".(int)$email_letter->getValue()." ";
				if (ACP_USER_POINTS)
					$sql .= ", user_points=".(int)$points->getValue()." ";
					

				if (Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_access_user SET
						user_id_str='".$validate_id_str."',
						user_name='".StdSqlSafety($name->getValue())."',
						user_email='".StdSqlSafety($email->getValue())."',
						user_email_show=".(int)$email_show->getValue().",
						user_tel='".StdSqlSafety($tel->getValue())."',
						user_website='".StdSqlSafety($website->getValue())."',
						user_description='".StdSqlSafety($description->getValue())."',
						user_access=".$access.", user_locked=".(int) $locked->getValue()."
						".$sql."
						WHERE user_id=".StdSqlSafety($_GET['id']))) {
					echo ActionReport(REPORT_OK, "Benutzer gespeichert",
							"Die Änderungen wurde erfolgreich übernommen.");
				}
				else {
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Die Änderungen konnten nicht übernommen werden.
							<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
				}
			}
			else {
				/* ID Str ermitteln */
				$validate_id_str = getIdStr($name->getValue(),
						DB_TABLE_ROOT."cms_access_user", "", "user_id_str");
						
				/* Vorbereiten der Spezialfaelle */
				$sql_col = "";
				$sql_data = "";
				if (ACP_ACCESS_SYSTEM_EN) {
					$sql_col .= "user_login, user_password, ";
					$sql_data .= "'".$validate_login."', '".sha1($password->getValue())."', ";
				}
				if (ACP_MODULE_NEWSLETTER_EN) {
					$sql_col .= "user_allow_newsletter, ";
					$sql_data .= (int)$email_letter->getValue().", ";
				}
				if (ACP_USER_POINTS) {
					$sql_col .= "user_points, ";
					$sql_data .= (int)$points->getValue().", ";
				}
				
				if (Database::instance()->query("INSERT INTO ".DB_TABLE_ROOT."cms_access_user(".$sql_col."
						user_id_str, user_name, user_email, user_email_show,  user_tel, user_website,
						user_description, user_access, user_regist, user_locked)
						VALUES(".$sql_data."
						'".$validate_id_str."',
						'".StdSqlSafety($name->getValue())."', '".StdSqlSafety($email->getValue())."',
						".(int)$email_show->getValue().", '".StdSqlSafety($tel->getValue())."', 
						'".StdSqlSafety($website->getValue())."', '".StdSqlSafety($description->getValue())."',
						".$access.", ".TIME_STAMP.", ".(int) $locked->getValue().")")) {
					echo ActionReport(REPORT_OK, "Benutzer erstellt",
							"Der Benutzer wurde erfolgreich erstellt.");
				}
				else {
					echo ActionReport(REPORT_ERROR, "Fehler",
							"Der Benutzer konnten nicht erstellt werden.
							<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
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
		/* Formularausgabe */
		echo $form->getForm();
	}
}

?>