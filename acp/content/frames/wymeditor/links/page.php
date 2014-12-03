<?php

 /*
 =====================================================
 Name ........: Frame: Links auf eigene CMS Seiten
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: page.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 10.07.2011 | Programm erstellt.
 |2.0     | 10.04.2013 | Ueberarbeitung volle Frames.
 -----------------------------------------------------
 Beschreibung :
 Generiert die URL auf eine eigene CMS Seite.
 WYMeditor Erweiterung.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_WEBSITE, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Eigene CMS Seiten";
$ACP_ApplicationInfo['body_onload'] = "";
///////////////////////////////////////////////////////

/* Titel Optionen */
echo '<div id="pos-dummy-std"></div>';
echo '<div id="pos-fixed">';
echo '<div id="options"><a href="?page=wymeditor-links-default"><img src="img/icons/wysiwym/link.png" alt="" /></a>'
		.'<a href="?page=wymeditor-links-page"><img src="img/icons/wysiwym/link_cms.png" alt="" /></a>';
if (ACP_FILE_SYSTEM_EN) {
	echo '<a href="?page=wymeditor-links-file"><img src="img/icons/wysiwym/file_list.png" alt="" /></a>';
	if (ACP_AdminAccess(ACP_ACCESS_FILESYSTEM | ACP_ACCESS_FILESYSTEM_DATA))
		echo '<a href="?page=wymeditor-links-upload"><img src="img/icons/wysiwym/file_upload.png" alt="" /></a>';
	}
echo '</div>';
echo "<h1>Eigene CMS Seiten</h1>";
echo '</div>';

/* Menuestammbaum generieren */
$o_menutree = new buildMenuTree(DB_CMS);
$o_menutree->setSqlCondition('&& locked=0 && id NOT IN ('.implode(',', $DefaultErrorPages).')');
$menutree_txt = $o_menutree->getMenuTree(0, MENU_MAX_LEVEL, 1, true,
		"menu/wymeditor/frame");

$a_options = explode("|", $menutree_txt);
/* Letzter (leerer) Eintrag entfernen (Verursacht von Template) */
array_pop($a_options);

/* Liste mit allen Seiten */
echo '<div class="data-list"><ol>';
for ($i=0; $i < sizeof($a_options); $i++) {
	$a_options_data = explode("$", $a_options[$i]);
	
	echo '<li><a href="#" onclick="parent.document.getElementById(\'wym_href\').value = \''
				.$a_options_data[1].'\';parent.document.getElementById(\'wym_submit\').click();">'
				.str_repeat("&nbsp;", 4 * ($a_options_data[0]-1)).$a_options_data[2].'</a></li>';
}
echo '</ol></div>';

?>

