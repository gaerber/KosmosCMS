<?php

 /*
 =====================================================
 Name ........: Plugin All Page: Login
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: user_login.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 02.10.2011 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin auf allen Seiten damit sich Benutzer anmelden
 und abmelden koennen.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if ($_SESSION['user_id']) {
	$tpl = new tpl("plugins/access/allpage/user");
	
	$url = $_SERVER["REQUEST_URI"];
	$url = str_replace("&", "&amp;", $url);
	if (strpos($url, "?") === false)
		$url .= "?";
	else 
		$url .= "&amp;";
	
	$tpl->assign("url", $url."cms_logout");
	$PluginContent['plugin_access_form'] = $tpl->get();
}
else {
	$tpl = new tpl("plugins/access/allpage/form");
	
	$url = $_SERVER["REQUEST_URI"];
	$url = str_replace("&", "&amp;", $url);
	$url = preg_replace("/(&amp;cms_logout|\?cms_logout)/s", "", $url);
	
	$tpl->assign("url", $url);
	$PluginContent['plugin_access_form'] = $tpl->get();
}

?>