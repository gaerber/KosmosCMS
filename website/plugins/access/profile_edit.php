<?php

 /*
 =====================================================
 Name ........: Plugin: Access System Benutzerprofil
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: profil_edit.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 02.10.2011 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin: Der Benutzer kann hier seine Informationen und
 Einstellungen aendern.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_ACCESS_SYSTEM_EN) {
	if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
		echo '<h1 class="first">Profil bearbeiten</h1>';
		$new_account = false;
		$PluginContent['caption'] = 'Profil bearbeiten';
		$PluginContent['slogan'] = 'Ändern Sie Ihre Profilangaben und Einstellungen';
	}
	else {
		echo '<h1 class="first">Neues Profil erstellen</h1>';
		$new_account = true;
		$PluginContent['caption'] = 'Neues Profil erstellen';
		$PluginContent['slogan'] = 'Erstellen Sie ein eigenes Benutzerkonto';
	}

	$form = new formWizard('form', '?profile', 'post', 'form_standard');
	if ($new_account) {
		$login = $form->addElement('text', 'login', 'Benutzernamen', NULL, true);
		$password1 = $form->addElement('password', 'password1', 'Passwort', NULL, true);
		$password2 = $form->addElement('password', 'password2', 'Passwort wiederholen', NULL, true);
	}
	$name = $form->addElement('text', 'name', 'Name', NULL, true);
	$email = $form->addElement('text', 'email', 'Email Adresse', NULL, true);
	$email->setCustomValidation('email', NULL);
	$email_show = $form->addElement('checkbox', 'email_show', 'Emailadresse anzeigen', 1);
	if (ACP_MODULE_NEWSLETTER_EN)
		$email_letter = $form->addElement('checkbox', 'email_letter', 'Newsletter abonieren', 1);
	$website = $form->addElement('text', 'website', 'Website');
	$website->setCustomValidation('website', NULL);
	/* Benutzereigene Felder */
	$customParameters = array();
	foreach($UserSystem_customParameters as $id => $custline) {
		if ($custline[1]=='text' || $custline[1]=='textarea')
			$customParameters[$id] = $form->addElement($custline[1], $custline[0], $custline[2]);
		/*if ($line[1]=='checkbox' || $line[1]=='radio')
			$customParameters[$id] = $form->addElement($custline[1], $custline[0], $custline[2], $custline[3]);
		*/
	}

	if ($new_account)
		$submit = $form->addElement('submit', 'btn', NULL, 'Registrieren');
	else
		$submit = $form->addElement('submit', 'btn', NULL, 'Ändern');

	/* Defaultwerte Setzen */
	if (!$form->checkSubmit() && !$new_account) {
		/* Daten lesen */
		$result = Database::instance()->query('SELECT * FROM '.DB_TABLE_ROOT.'cms_access_user
				WHERE user_id='.$_SESSION['user_id'])
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			$name->setValue($line['user_name']);
			$email->setValue($line['user_email']);
			$website->setValue($line['user_website']);
			if ($line['user_email_show'])
				$email_show->setChecked(true);
			if (ACP_MODULE_NEWSLETTER_EN && $line['user_allow_newsletter'])
				$email_letter->setChecked(true);
			/* Benutzereigene Felder */
			foreach($UserSystem_customParameters as $id => $custline) {
				if ($custline[1]=='text' || $custline[1]=='textarea')
					$customParameters[$id]->setValue($line[$custline[0]]);
			}
		}
		else {
			/* Benutzer existiert nicht */
			echo ActionReport(REPORT_EINGABE, 'Benutzer existiert nicht', 'Dieser Benutzer existiert nicht!');
		}
	}

	/* Formular pruefen */
	if ($form->checkForm()) {
		if (!$new_account) {
			/* ID Str ermitteln */
			$validate_id_str = getIdStr($name->getValue(), DB_TABLE_ROOT.'cms_access_user',
					'&& user_id!='.StdSqlSafety($_SESSION['user_id']), 'user_id_str');

			/* Vorbereiten der Spezialfaelle */
			$sql = '';

			if (ACP_MODULE_NEWSLETTER_EN)
				$sql .= ', user_allow_newsletter='.(int)$email_letter->getValue().' ';

			/* Benutzereigene Felder */
			foreach($UserSystem_customParameters as $id => $custline) {
				$sql .= ', '.$custline[0].'="'.StdSqlSafety($customParameters[$id]->getValue()).'" ';
			}

			if (Database::instance()->query("UPDATE ".DB_TABLE_ROOT."cms_access_user SET
					user_id_str='".$validate_id_str."',
					user_name='".StdSqlSafety($name->getValue())."',
					user_email='".StdSqlSafety($email->getValue())."',
					user_email_show=".(int)$email_show->getValue().",
					user_website='".StdSqlSafety($website->getValue())."'
					".$sql."
					WHERE user_id=".$_SESSION['user_id'])) {
				echo ActionReport(REPORT_OK, "Änderungen gespeichert",
						"Ihre Profiländerungen wurden erfolgreich übernommen.");
			}
			else {
				echo ActionReport(REPORT_ERROR, "Fehler",
						"Die Änderungen konnten nicht übernommen werden.
						<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
			}
		}
		else {
			/* Loginnamen pruefen */
			$validate_login = StdSqlSafety(substr($login->getValue(), 0, 20));
			$result = Database::instance()->query('SELECT user_id FROM '.DB_TABLE_ROOT.'cms_access_user
					WHERE user_login="'.$validate_login.'"')
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($result->num_rows == 0) {
				if ($password1->getValue() == $password2->getValue()) {
					/* ID Str ermitteln */
					$validate_id_str = getIdStr($name->getValue(),
							DB_TABLE_ROOT.'cms_access_user', '', 'user_id_str');

					/* Vorbereiten der Spezialfaelle */
					$sql_col = 'user_login, user_password, ';
					$sql_data = '"'.$validate_login.'", "'.sha1($password1->getValue()).'", ';

					if (ACP_MODULE_NEWSLETTER_EN) {
						$sql_col .= 'user_allow_newsletter, ';
						$sql_data .= (int)$email_letter->getValue().', ';
					}

					/* Benutzereigene Felder */
					foreach($UserSystem_customParameters as $id => $custline) {
						$sql_col .= $custline[0].', ';
						$sql_data .= '"'.StdSqlSafety($customParameters[$id]->getValue()).'", ';
					}

					if (Database::instance()->query("INSERT INTO ".DB_TABLE_ROOT."cms_access_user(".$sql_col."
							user_id_str, user_name, user_email, user_email_show, user_website,
							user_regist)
							VALUES(".$sql_data."
							'".$validate_id_str."',
							'".StdSqlSafety($name->getValue())."', '".StdSqlSafety($email->getValue())."',
							".(int)$email_show->getValue().", '".StdSqlSafety($website->getValue())."',
							".TIME_STAMP.")")) {
						echo ActionReport(REPORT_OK, "Benutzerkonto erstellt",
								"Ihr Benutzerkonto wurde erfolgreich erstellt.");
					}
					else {
						echo ActionReport(REPORT_ERROR, "Fehler",
								"Das Benutzerkonto konnten nicht erstellt werden.
								<br />MySQL Fehler: ".Database::instance()->getErrorMessage());
					}
				}
				else {
					/* Passwoerter stimmen nicht ueberein */
					$password1->setError(true);
					$password2->setError(true);
					echo ActionReport(REPORT_EINGABE, 'Passwörter stimmen nicht',
							'Die angegebenen Passwörter stimmen nicht überein.');
					echo $form->getForm();
				}
			}
			else {
				/* Loginname existiert bereits */
				$login->setError(true);
				echo ActionReport(REPORT_EINGABE, 'Benutzernamen existiert bereits',
						'Der angegebene Benutzernamen existiert bereits!');
				echo $form->getForm();
			}
		}
	}
	else {
		/* Formularausgabe */
		echo $form->getForm();
	}
}

?>
