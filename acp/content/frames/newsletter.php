<?php

 /*
 =====================================================
 Name ........: Frame: Newsletter Variablen
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: newsletter.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 28.09.2011 | Programm erstellt.
 -----------------------------------------------------
 Beschreibung :
 Eine Liste aller Variablen, welche in der Nachricht
 eines Newsletters verwendet werden duerfen.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['title'] = "Newsletter Platzhalter";
///////////////////////////////////////////////////////

echo "<h1>Newsletter Platzhalter</h1>";
echo "<p>Nachstehend ist eine Liste aller erlaubten Platzhalter der Nachricht<p>";

echo "<ol>";

$result = Database::instance()->query("SHOW COLUMNS FROM ".DB_TABLE_ROOT."cms_access_user")
		OR FatalError(FATAL_ERROR_MYSQL);

while ($row = $result->fetch_assoc()) {
	if ($row['Type'][0] != 't' && $row['Type'][0] != 'b' && $row['Field'] != "user_password") {
		echo "<li>{".$row['Field']."}</li>";
	}
}

?>