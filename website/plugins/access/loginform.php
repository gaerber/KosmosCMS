<?php

 /*
 =====================================================
 Name ........: Plugin: Access System Benutzer
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: login_page.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 14.04.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin: Die Seite des Benutzers. Hier sind seinie
 Informationen oder das Loginformular zu finden.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_ACCESS_SYSTEM_EN) {
	if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
		/* Passwort aendern */
		if (isset($_GET['password'])) {
			$PluginContent['caption'] = 'Passwort ändern';
			$PluginContent['slogan'] = 'Erneuern Sie hier Ihr Passwort';
			echo '<h1 class="first">Passwort ändern</h1>';

			$form = new formWizard('form', '?password', 'post', 'form_standard');
			$password0 = $form->addElement('password', 'password0', 'Altes Passwort', NULL, true);
			$password1 = $form->addElement('password', 'password1', 'Neues Passwort', NULL, true);
			$password2 = $form->addElement('password', 'password2', 'Passwort wiederholen', NULL, true);
			$submit = $form->addElement('submit', 'btn', NULL, 'Ändern');

			if ($form->checkForm()) {
				if (sha1($password0->getValue()) == $_SESSION['user_password']) {
					if ($password1->getValue() == $password2->getValue()) {
						/* Passwort aendern */
						if (Database::instance()->query('UPDATE '.DB_TABLE_ROOT.'cms_access_user SET
								user_password="'.sha1($password1->getValue()).'"
								WHERE user_id='.$_SESSION['user_id'])) {
							$_SESSION['user_password'] = sha1($password1->getValue());
							echo ActionReport(REPORT_OK, 'Passwort geändert',
									'Das Passwort wurde erfolgreich geändert.');
						}
						else {
							echo ActionReport(REPORT_ERROR, 'Fehlgeschlagen',
									'Das Passwort konnte nicht geändert werden.
									<br />MySQL Error:'.Database::instance()->getErrorMessage());
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
					/* Altes Passwort stimmt nicht -> Notabmeldung */
					$_SESSION['user_id'] = 0;
					$_SESSION['user_access'] = 0;
					unset($_SESSION['user_login'], $_SESSION['user_password']);
					/* Autologin Cookie loeschen */
					$_COOKIE['cms_autologin_login'] = NULL;
					$_COOKIE['cms_autologin_password'] = NULL;
					setcookie("cms_autologin_login", "", TIME_STAMP - 3600, "/");
					setcookie("cms_autologin_password", "", TIME_STAMP - 3600, "/");
					echo ActionReport(REPORT_EINGABE, 'Passwörter falsch',
							'Das eingegebene, aktuelle Passwort war falsch. Sie wurden aus
							Sicherheitsgründen vom System abgemeldet.');
				}
			}
			else {
				/* Formular */
				echo $form->getForm();
			}
		}

		/* Profilinformationen aendern */
		else if (isset($_GET['profile'])) {
			include('profile_edit.php');
		}

		/* Benutzer hat sich erfolgreich angemeldet */
		else {
			$tpl = new tpl("plugins/access/login/user");
			$PluginContent['caption'] = $_SESSION['user_name'];
			$PluginContent['slogan'] = 'Herzlich willkommen im internen Bereich';
			if (isset($_SESSION['user_address']))
				$PluginContent['user_address'] = str_replace("\n", "<br />\n", $_SESSION['user_address']);
			$tpl->out();
		}
	}
	else {
		/* Passwort zurücksetzen */
		if (isset($_GET['password'])) {
			$PluginContent['caption'] = 'Passwort zurücksetzen';
			$PluginContent['slogan'] = 'Ihr neues Passwort wird Ihnen per Email zugesendet.';
			echo '<h1 class="first">Passwort zurücksetzen</h1>';

			$form = new formWizard('form', '?password', 'post', 'form_standard');
			$email = $form->addElement('text', 'email', 'Email Adresse', NULL, true);
			$email->setCustomValidation('email', NULL);
			$submit = $form->addElement('submit', 'btn', NULL, 'Zurücksetzen');

			if ($form->checkForm()) {
				$result = Database::instance()->query('SELECT user_login, user_email FROM '.DB_TABLE_ROOT.'cms_access_user
						WHERE user_email="'.StdSqlSafety($email->getValue()).'"')
						OR FatalError(FATAL_ERROR_MYSQL);
				if ($line = $result->fetch_assoc()) {
					echo 'Comming Soon...';
				}
				else {
					/* Emailadresse existiert in der Datenbank nicht */
					echo ActionReport(REPORT_EINGABE, 'Emailadresse existiert nicht',
					'Diese Emailadresse existiert in der Datenbank nicht. Sie müssen
					die Emailadresse angeben, mit der Sie sich registriert haben.');
					echo $form->getForm();
				}
			}
			else {
				echo $form->getForm();
			}
		}

		/* Neues Konto erstellen */
		else if (isset($_GET['profile'])) {
			include('profile_edit.php');
		}

		/* Loginformular anzeigen */
		else {
			$tpl = new tpl("plugins/access/login/form");
			$url = $_SERVER["REQUEST_URI"];
			$url = str_replace("&", "&amp;", $url);
			$url = preg_replace("/(&amp;cms_logout|\?cms_logout)/s", "", $url);
			$tpl->assign("url", $url);
			$tpl->out();
		}
	}
}

?>
