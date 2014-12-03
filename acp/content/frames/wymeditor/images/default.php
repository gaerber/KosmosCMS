<?php

 /*
 =====================================================
 Name ........: Frame: Bild
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
 |1.0     | 16.04.2013 | Ueberarbeitung volle Frames.
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
$ACP_ApplicationInfo['title'] = "Bild einfügen";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

/* Titel Optionen */
echo '<div id="options"><a href="?page=wymeditor-images-default"><img src="img/icons/wysiwym/image.png" alt="" /></a>';
if (ACP_FILE_SYSTEM_EN) {
	echo '<a href="?page=wymeditor-images-list"><img src="img/icons/wysiwym/image_list.png" alt="" /></a>';
	if (ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_DATA))
		echo '<a href="?page=wymeditor-images-upload"><img src="img/icons/wysiwym/image_upload.png" alt="" /></a>';
	}
echo '</div>';
echo "<h1>Bild einfügen</h1>";

/* Formular */
$form = new formWizard('form', '?'.$_SERVER['QUERY_STRING'], 'post', 'form_acp_standard');
$url = $form->addElement('text', 'url', 'Link', NULL, true);
$submit = $form->addElement('submit', 'button', NULL, 'Einfügen');

/* Auswertung */
if ($form->checkSubmit() && $form->checkForm()) {
	/* Javascript um originalfelder zu fuellen und Link direkt einzufuegen */
	$ACP_ApplicationInfo['body_onload'] = "parent.document.getElementById('wym_src').value = '".$url->getValue()."';"
			."parent.document.getElementById('wym_submit').click();";
}
else {
	$ACP_ApplicationInfo['body_onload'] = "document.getElementById('form_1').value = parent.document.getElementById('wym_src').value";
	/* Ausgabe Formular */
	echo $form->getForm();
}

?>