<?php

 /*
 =====================================================
 Name ........: Plugin: Gaestebuch Eintrag
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: entry.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |3.0     | 23.01.2012 | Programm erstellt
 |3.1     | 23.10.2012 | SPAM Schutz
 -----------------------------------------------------
 Beschreibung :
 Plugin: Besucher kann hier einen Gaestebucheintrag
 verfassen.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_GUESTBOOK_EN) {
	/* Formular fuer Kommentare */
	$form = new formWizard('form', '', 'post', 'form_standard');
	$o_spam = new SpamProtection('module_guestbook', $form);
	/* Bei Gaesten nach Kontaktinformationen fragen */
	if (!$_SESSION['user_id']) {
		$name = $form->addElement('text', 'name', 'Name', getUserInfo('user_name'), true);
		$email = $form->addElement('text', 'email', 'Email Adresse', getUserInfo('ueser_email'));
		$email->setCustomValidation('email', NULL);
		$website = $form->addElement('text', 'website', 'Website', getUserInfo('user_website'));
		$website->setCustomValidation('website', NULL);
	}
	$comment = $form->addElement('textarea', 'comment', 'Nachricht', NULL, true);
	$comment->setRowsCols(7,20);
	$o_spam->printCaptcha();
	$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

	/* Formular pruefen */
	if ($form->checkForm()) {
		if ($o_spam->check()) {
			/* Kommentar abspeichern */
			$user_infos = array();
			if ($_SESSION['user_id']) {
				$user_infos['name'] = $_SESSION['user_name'];
				if ($_SESSION['user_email_show'])
					$user_infos['email'] = $_SESSION['user_email'];
				else
					$user_infos['email'] = "";
				$user_infos['website'] = $_SESSION['user_website'];
			}
			else {
				$user_infos['name'] = StdSqlSafety($name->getValue());
				$user_infos['email'] = StdSqlSafety($email->getValue());
				$user_infos['website'] = StdSqlSafety($website->getValue());
			}
			if (Database::instance()->query("INSERT INTO ".DB_TABLE_PLUGIN."guestbook(
					writer_id, writer_name, writer_email, writer_website,
					comment, timestamp)VALUES(
					".$_SESSION['user_id'].", '".$user_infos['name']."',
					'".$user_infos['email']."', '".$user_infos['website']."',
					'".StdSqlSafety(StdContent($comment->getValue(), false))."',
					".TIME_STAMP.")")) {
				echo ActionReport(REPORT_OK, "Beitrag gespeichern",
						"Vielen Dank für Ihren Beitrag!");
			}
			else {
				echo ActionReport(REPORT_ERROR, 'Fehler beim Abspeichern',
						'Beim abspeichern trat eine Fehler auf!<br />Mysql Error: '.Database::instance()->getErrorMessage());
			}
		}
		else {
			echo ActionReport(REPORT_SPAM, 'Anti Spam Sicherung', $o_spam->getErrorMessage());
			echo $form->getForm();
		}
	}
	else {
		$o_spam->printingForm();
		/* Ausgabe Formular */
		echo $form->getForm();
	}
}

?>
