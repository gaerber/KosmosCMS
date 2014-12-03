<?php

 /*
 =====================================================
 Name ........: Plugin: Newsletter versenden
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: send.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 24.09.2011 | Programm erstellt.
 |1.0.1   | 01.10.2011 | Kategorie wechsel.
 |1.0.2   | 30.03.2013 | Gruppenwahl verstecken.
 -----------------------------------------------------
 Beschreibung :
 Plugin: Vesenden eines Newsletters.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_NEWSLETTER_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_NEWS_LETTER, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_user\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_user\"";
///////////////////////////////////////////////////////

echo "<h1 class=\"first\">Newsletter versenden</h1>";

$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
/* Gruppenverwaltung */
$access_log = $form->addElement('radio', 'access', 'Empfänger', '1');
$access_grp = $form->addElement('radio', 'access', NULL, '2');
$access_groups = $form->addElement('select', 'access_group', 'Gruppen');
$access_groups->setCssClass('select_groups');
$access_log->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'none\';"');
$access_grp->setJavaScript('onclick="document.getElementsByClassName(\'select_groups\')[0].style.display=\'block\';"');
$access_log->setSubLabel("Alle Benutzer");
$access_grp->setSubLabel("Benutzer aus bestimmten Gruppen");
$access_log->setChecked(true);
$access_groups->setMultiple(true);
$access_groups->setSize(7);

$subject = $form->addElement('text', 'subject', 'Betreff', NULL, true);
$message = $form->addElement('textarea', 'message', 'Nachricht', NULL, true);
$message->setRowsCols(10, 20);

$submit = $form->addElement('submit', 'btn', NULL, 'Senden');

/* Liste aller Gruppen */
$result = mysql_query("SELECT id, name FROM ".DB_TABLE_ROOT."cms_access_group
		ORDER BY name ASC", DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
while ($row = mysql_fetch_array($result)) {
	$access_groups->addOption($row['name'], 1<<$row['id']);
}

/* Formular pruefen */
if ($form->checkForm()) {
	if ($access_grp->getValue() && !sizeof($access_groups->getValue())) {
		/* Es muss nim. eine Gruppe ausgewaehlt werden */
		$access_groups->setError(true);
		$access_groups->setCssClass('select_groups_view');
		/* Ausgabe des Formulars */
		echo $form->getForm();
	}
	else {
		/* Absender Emailadresse */
		$result = mysql_query("SELECT newsletter_email, newsletter_sender
				FROM ".DB_TABLE_ROOT."cms_setting
				ORDER BY id DESC LIMIT 1", DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			$newsletter_message_sender = $line['newsletter_sender']." <".$line['newsletter_email'].">";
			/* Eamil Header */
			$newsletter_header = "Mime-Version: 1.0\nContent-type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\nFrom: ".$newsletter_message_sender."\n";
			/* Bretreff */
			$newsletter_subject = $subject->getValue();
			/* Nachricht vorbereiten */
			$newsletter_message = StdStringEmail($message->getValue());
			/* Gruppen */
			if ($access_grp->getValue()) {
				$access = 0;
				foreach ($access_groups->getValue() as $group) {
					$access |= $group;
				}
			}
			else {
				$access = 1;
			}
			/* Beutzer Selektieren */
			$result = mysql_query("SELECT * FROM ".DB_TABLE_ROOT."cms_access_user
					WHERE (user_access & ".$access.") && user_allow_newsletter=1 && user_email!=''", DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			$email_ctr = 0;
			while ($row = mysql_fetch_array($result)) {
				/* Email vorbereiten */
				$newsletter_message_tmp = $newsletter_message;
				foreach ($row as $search => $replace) {
					if ($search == 'user_lastlogin' || $search == 'user_regist')
						$replace = printDate($replace)." ".date(FORMAT_TIME, $replace);
					if ((!is_numeric($replace) || $search == "user_id") && $search != "user_password")
						$newsletter_message_tmp = str_replace("{".$search."}", $replace,
								$newsletter_message_tmp);
				}
				/* Email versenden */
				if (mail($row['user_name']." <".$row['user_email'].">", $newsletter_subject,
						$newsletter_message_tmp, $newsletter_header)) {
					$email_ctr++;
				}
			}
			/* Pruefen ob alle Mails gesendet wurden */
			if (mysql_num_rows($result)) {
				if (mysql_num_rows($result) == $email_ctr) {
					echo ActionReport(REPORT_OK, "Alle Newsletter wurden versendet",
							"Alle ".$email_ctr." Newsletter wurden erfolgreich versendet!");
				}
				else {
					/* Nicht alle Mails wurden versendet */
					echo ActionReport(REPORT_ERROR, "Nicht alle Newsletter wurden versendet",
							"Es konnten nur ".$email_ctr." von ".mysql_num_rows($result)
							." Newsletter versendet werden!");
				}
			}
			else {
				/* Keine Mails versendet */
				echo ActionReport(REPORT_INFO, "Keine Mails versendet",
						"Es existieren keine Benutzer, welche den Newsletter erhalten möchten!");
			}
		}
		else {
			/* Absenderemailadresse nicht gefunden */
			echo ActionReport(REPORT_ERROR, "Absenderadresse nicht gefunden",
					"Die Absenderadresse für den Newsletter wurde in der Datenbank nicht gefunden!");
		}
	}
}
else {
	/* Ausgabe Formular */
	if ($access_grp->getValue()) {
		$access_groups->setCssClass('select_groups_view');
	}
	echo $form->getForm();
	
	echo "<p><img src=\"img/icons/plugins/newsletter/code.png\" alt=\"\" />
			<a href=\"#\" onclick=\"javascript:MyWindow=window.open('frame.php?page=newsletter','cms_newsletter','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=400'); return false;\">Liste der Platzhalter</a></p>";
}

?>