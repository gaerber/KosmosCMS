<?php

 /*
 =====================================================
 Name ........: Plugin: Kontaktformular
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: contact.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 04.09.2011 | Programm erstellt
 |1.1     | 15.02.2012 | Angemeldete Benutzer
 |1.2     | 26.10.2012 | SPAM Schutz
 -----------------------------------------------------
 Beschreibung :
 Plugin fuer Kontaktformular.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

$form = new formWizard('form', '?', 'post', 'form_standard');
$o_spam = new SpamProtection('module_contact', $form);
$form_data = array();

if (!getUserInfo('user_id')) {
	$form_data['user_name'] = $form->addElement('text', 'name', 'Ihr Name',
			getUserInfo('user_name'), true);
	$form_data['user_email'] = $form->addElement('text', 'email', 'Email Adresse',
			getUserInfo('user_email'), true);
	$form_data['user_email']->setCustomValidation('email',
			ActionReport(REPORT_EINGABE, 'Email Adresse' ,'Ihre angegebene Email Adresse ist nicht gültig!'));
}

$form_data['email_copy'] = $form->addElement('checkbox', 'email_copy', 'Kopie erhalten', 1);

/*if (!$_SESSION['user_id']) {
	$form_data['user_tel'] = $form->addElement('text', 'tel', 'Telefonnummer');
	$form_data['user_tel']->setCustomValidation("/[0-9\+\(\) ]{10,17}/i",
			"Ihre angegebene Telefonnummer ist nicht gültig!<br />");
}*/

$form_data['betreff'] = $form->addElement('text', 'betreff', 'Betreff', NULL, true);

$form_data['nachricht'] = $form->addElement('textarea', 'nachricht', 'Nachricht', NULL, true);
$form_data['nachricht']->setBigArea(false);
$form_data['nachricht']->setRowsCols(10,50);

$o_spam->printCaptcha();
$submit = $form->addElement('submit', 'btn', NULL, 'Senden');

/* Emails an Benutzer */
if (isset($_GET['user']) && $_GET['user'] != "") {
	$result = mysql_query("SELECT user_name, user_email, user_email_show FROM ".DB_TABLE_ROOT."cms_access_user
			WHERE user_id_str='".StdSqlSafety($_GET['user'])."'", DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		if ($line['user_email_show']) {
			$contact_data = array();
			$contact_data['title'] = "Nachricht an ".$line['user_name'];
			$contact_data['name'] = $line['user_name'];
			$contact_data['email'] = $line['user_email'];
		}
		/* ELSE: Buguser */
	}
	/* ELSE: Eingabefehler */
}

/* Default Zieladresse */
if (!isset($contact_data)) {
	$contact_data = array();
	$result = mysql_query("SELECT admin_email FROM ".DB_TABLE_ROOT."cms_setting
			ORDER BY id DESC LIMIT 1", DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = mysql_fetch_array($result)) {
		$contact_data['title'] = "";
		$contact_data['name'] = 'den Administrator';
		$contact_data['email'] = $line['admin_email'];
	}
	else {
		echo ActionReport(REPORT_ERROR, "Keine Emailadresse gefunden",
				"In der Datenbank wurde keine Administrator-Emailadresse gefunden!");
	}
}


if ($form->checkForm()) {
	if ($o_spam->check()) {
	    /* Daten sind alle Korrekt */
		$email_to = new tpl("plugins/contact/email_to");
		$email_cc = new tpl("plugins/contact/email_cc");
		foreach ($form_data as $search => $object) {
			$email_to->assign($search, $object->getValue());
			$email_cc->assign($search, $object->getValue());
			$email_cc->assign('receiver_name', $contact_data['name']);
		}
		
		if (getUserInfo('user_id')) {
			$email_to->assign($_SESSION);
			$email_cc->assign($_SESSION);
			$user_name = $_SESSION['user_name'];
			$user_email = $_SESSION['user_email'];
		}
		else {
			$user_name = $form_data['user_name']->getValue();
			$user_email = $form_data['user_email']->getValue();
		}
		
		/* Email senden */
		$header = "Mime-Version: 1.0\nContent-type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n";
		if(mail($contact_data['email'], $form_data['betreff']->getValue(), StdStringEmail($email_to->get()),
				$header."From: ".$user_name." <".$user_email.">")) {
			/* Emailkopie an Absender */
			if ($form_data['email_copy']->getValue()) {
				if(mail($user_email, "CC: ".$form_data['betreff']->getValue(),
						StdStringEmail($email_cc->get()),
						$header."From: ".$user_name." <".$user_email.">")) {
					echo ActionReport(REPORT_OK, "Nachricht gesendet",
							"Ihre Nachricht und die Kopie wurden erfolgreich versendet!");
				}
				else {
					echo ActionReport(REPORT_WARNING, "Nachricht gesendet",
							"Ihre Nachricht wurde erfolgreich versendet, nicht aber ihre Kopie!");
				}
			}
			else {
				echo ActionReport(REPORT_OK, "Nachricht gesendet",
						"Ihre Nachricht wurde erfolgreich versendet!");
			}
		}
		else {
			echo ActionReport(REPORT_ERROR, "Fehlgeschlagen",
					"Ihre Nachricht konnte leider nicht versendet werden!");
		}
	}
	else {
		echo ActionReport(REPORT_SPAM, 'Anti Spam Sicherung', $o_spam->getErrorMessage());
		echo $form->getForm();
	}
}
else {
	/* Ausgabe Formular */
    if ($contact_data['title'])
    	echo "<h2>".$contact_data['title']."</h2>\r\n";
    $o_spam->printingForm();
	echo $form->getForm();
}

?>