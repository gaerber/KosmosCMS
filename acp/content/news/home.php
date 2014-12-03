<?php

 /*
 =====================================================
 Name ........: Plugin: Neuigkeiten Uebersicht
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: home.php
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
 Plugin: Uebersicht des Moduls Neuigkeiten.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_NEWS_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_NEWS | ACP_ACCESS_M_NEWS_COM
		| ACP_ACCESS_M_NEWS_CAT, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 2;
///////////////////////////////////////////////////////

echo "<h1 class=\"first\">Neuigkeiten</h1>";

/* Ausgabe Liste */
echo printBoxStart();

/* Anzahl Neuigkeiten (Neu, Gesperrt) */
$special_count = mysql_count(DB_TABLE_PLUGIN."news", "locked=1");
echo printBox("Neuigkeiten", mysql_count(DB_TABLE_PLUGIN."news", "")." Neuigkeiten<br />".
		"Davon sind ".$special_count." nicht freigegeben",
		NULL, NULL, NULL, $special_count);

/* Ankahl Kommentare (Neu) */
$special_count = mysql_count(DB_TABLE_PLUGIN."news_comment", "timestamp>".$_SESSION['admin_lastlogin']);
echo printBox("Kommentare", mysql_count(DB_TABLE_PLUGIN."news_comment", "")." Kommentare<br />".
		"Davon sind ".$special_count." neue Kommentare",
		NULL, NULL, NULL, $special_count);

/* Kategorien */
echo printBox("Kategorien", mysql_count(DB_TABLE_PLUGIN."news_categorie", "")." Kategorien");

/* Anzahl Newsletterempfaenger */
if (ACP_MODULE_NEWSLETTER_EN && ACP_AdminAccess(ACP_ACCESS_M_NEWS_LETTER))
	echo printBox("Newsletter", mysql_count(DB_TABLE_ROOT."cms_access_user", "user_allow_newsletter=1").
			" Benutzer erhalten den Newsletter");

/* Ende der Ausgabe */
echo printBoxEnd();

?>