<?php

 /*
 =====================================================
 Name ........: Plugin: Sitemap
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: sitemap.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |2.0     | 26.01.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin: Liste aller Seiten.

 TO DO: Manipulation durch Module.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

$o_menutree = new buildMenuTree(Database::instance());
$o_menutree->setSqlCondition(" && locked=0 && ".CheckSQLAccess());
$o_menutree->allowPluginSubmenu(true);

echo $o_menutree->getMenuTree(0, MENU_MAX_LEVEL, MENU_MAX_LEVEL, true, "plugins/sitemap/list/{pos}");

$PluginContent['date'] = printDate(TIME_STAMP);

?>
