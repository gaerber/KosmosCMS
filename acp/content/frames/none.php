<?php

 /*
 =====================================================
 Name ........: Frame: None
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: none.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 07.07.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Anzeige, falls kein Frame ausgewaehlt wurde. (HTTP
 Fehler 404)

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Kein Frame ausgewählt";
///////////////////////////////////////////////////////

echo ActionReport(REPORT_EINGABE, "Kein Frame ausgewählt",
		"Es wurde kein Frame ausgewählt oder das ausgewählte Frame existiert nicht!")

?>