<?php

 /*
 =====================================================
 Name ........: Frame: Links
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: default.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 10.04.2013 | Ueberarbeitung volle Frames.
 -----------------------------------------------------
 Beschreibung :
 Bearbeiten einer URL. WYMeditor Erweiterung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Hyperlink einfügen";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

/* Titel Optionen */
echo '<div id="options"><a href="?page=wymeditor-links-default"><img src="img/icons/wysiwym/link.png" alt="" /></a>'
		.'<a href="?page=wymeditor-links-page"><img src="img/icons/wysiwym/link_cms.png" alt="" /></a>';
if (ACP_FILE_SYSTEM_EN) {
	echo '<a href="?page=wymeditor-links-file"><img src="img/icons/wysiwym/file_list.png" alt="" /></a>';
	if (ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_DATA))
		echo '<a href="?page=wymeditor-links-upload"><img src="img/icons/wysiwym/file_upload.png" alt="" /></a>';
	}
echo '</div>';
echo "<h1>Hyperlink</h1>";

/* Formular */
$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
$url = $form->addElement('text', 'url', 'Link', NULL, true);
//$newwin = $form->addElement('checkbox', 'newwin', 'Neues Fenster', 1);
$submit = $form->addElement('submit', 'button', NULL, 'Einfügen');

/* Auswertung */
if ($form->checkSubmit() && $form->checkForm()) {
	/* Javascript um originalfelder zu fuellen und Link direkt einzufuegen */
	$ACP_ApplicationInfo['body_onload'] = "parent.document.getElementById('wym_href').value = '".$url->getValue()."';"
			."parent.document.getElementById('wym_submit').click();";
}
else {
	$ACP_ApplicationInfo['body_onload'] = "document.getElementById('form_1').value = parent.document.getElementById('wym_href').value";
	/* Ausgabe Formular */
	echo $form->getForm();
}

?>