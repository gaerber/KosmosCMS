<?php

 /*
 =====================================================
 Name ........: IP Ban Liste
 Projekt .....: CMS 2.0
 Datiename ...: list.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 13.07.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Anzeige alle gesperrten IPs.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
ACP_AdminAccess(ACP_ACCESS_ADMIN, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 1;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_setting\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_setting\"";
///////////////////////////////////////////////////////

/* Titel */
echo "<h1 class=\"first\">IP sperren</h1>";

echo "<p><img src=\"img/icons/admin/ipban/newip.png\" alt=\"\" />
		<a href=\"?page=admin-edit\">Neue IP Adresse sperren</a></p>";

/* Tabelle */
echo "<table><tr class=\"table_title\"><td>IP Adresse</td><td>Grund</td><td>";

?>