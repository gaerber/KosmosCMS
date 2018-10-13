<?php

 /*
 =====================================================
 Name ........: Plugin: Kontaktformular
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: contact_business.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 04.09.2011 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin fuer Kontaktformular.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

$form = new formWizard('form', "?", 'post', 'form_standard');

$form_data = array();
$form_data['anrede'] = $form->addElement('radio', 'anrede', 'Anrede', 'Herr');
$form_data['anrede']->setSubLabel("Herr");
$form_data['anrede_w'] = $form->addElement('radio', 'anrede', '', 'Frau');
$form_data['anrede_w']->setSubLabel("Frau");

$form_data['name'] = $form->addElement('text', 'name', 'Ihr Name', NULL, true);

$form_data['email'] = $form->addElement('text', 'email', 'Email Adresse', NULL, true);
$form_data['email']->setCustomValidation("email",
		"Ihre angegebene Email Adresse ist nicht gültig!<br />");

$form_data['telefon'] = $form->addElement('text', 'tel', 'Telefonnummer');
$form_data['telefon']->setCustomValidation("/[0-9\+\(\) ]{10,17}/i",
		"Ihre angegebene Telefonnummer ist nicht gültig!<br />");

$form_data['firma'] = $form->addElement('text', 'firma', 'Firma');

$form_data['adresse'] = $form->addElement('textarea', 'adresse', 'Adresse');
$form_data['adresse']->setRowsCols(2,50);

$form_data['nachricht'] = $form->addElement('textarea', 'nachricht', 'Nachricht', NULL, true);
$form_data['nachricht']->setBigArea(false);
$form_data['nachricht']->setRowsCols(10,50);

$submit = $form->addElement('submit', 'btn', NULL, 'Senden');

/* Emails an Benutzer */
if (isset($_GET['user']) && $_GET['user'] != "") {
	$result = Database::instance()->query("SELECT user_name, user_email, user_email_show FROM ".DB_TABLE_ROOT."cms_access_user
			WHERE user_id_str='".StdSqlSafety($_GET['user'])."'")
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		if ($line['user_email_show']) {
			$contact_data = array();
			$contact_data['title'] = "Nachricht an ".$line['user_name'];
			$contact_data['email'] = $line['user_email'];
		}
		/* ELSE: Buguser */
	}
	/* ELSE: Eingabefehler */
}

/* Default Zieladresse */
if (!isset($contact_data)) {
	$contact_data = array();
	$result = Database::instance()->query("SELECT admin_email FROM ".DB_TABLE_ROOT."cms_setting
			ORDER BY id DESC LIMIT 1")
			OR FatalError(FATAL_ERROR_MYSQL);
	if ($line = $result->fetch_assoc()) {
		$contact_data['title'] = "";
		$contact_data['email'] = $line['admin_email'];
	}
	else {
		echo ActionReport(REPORT_ERROR, "Keine Emailadresse gefunden",
				"In der Datenbank wurde keine Administrator-Emailadresse gefunden!");
	}
}


if ($form->checkForm()) {
    /* Daten sind alle Korekt */
	$tpl = new tpl("plugins/contact/email");
	foreach ($form_data as $search => $object) {
		$tpl->assign($search, $object->getValue());
	}

	/* Email senden */
	$header = "Mime-Version: 1.0\nContent-type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n";
	if(mail($contact_data['email'], "Nachricht von Ihrer Website", StdStringEmail($tpl->get()),
			$header."From: ".$form_data['name']->getValue()." <".$form_data['email']->getValue().">")) {
		echo "Ihre Nachricht wurde erfolgreich versendet!";
	}
	else {
		echo "Ihre Nachricht konnte leider nicht versendet werden!";
	}
}
else {
	/* Ausgabe Formular */
    if ($contact_data['title'])
    	echo "<h2>".$contact_data['title']."</h2>\r\n";
    echo $form->getForm();
    echo "\r\n<p class=\"form_end\">&nbsp;</p>";
}

?>
