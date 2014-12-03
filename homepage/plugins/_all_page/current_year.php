<?php

 /*
 =====================================================
 Name ........: Plugin All Page: Aktuelle Jahreszahl
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: current_year.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 30.12.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Aktuelle Jahreszahl.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

$PluginContent['plugin_current_year'] = date('Y', TIME_STAMP);

?>