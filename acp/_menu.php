<?php

 /*
 =====================================================
 Name ........: ACP Navigation
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: _menu.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 27.07.2008 | Programm erstellt
 |1.0.1   | 16.07.2011 | Anpassung auf CMS 2.0
 |1.0.2   | 31.03.2013 | Backup hinzu
 -----------------------------------------------------
 Beschreibung :
 Das komplette Menu der ACPs.

 (c) by Kevin Gerber
 =====================================================
 */

/** Generiert das Hauptmenue */
function HeaderMenu($active) {
	$menu = "";

	if ($active == 1) {
		$menu .= "<li><a href=\"?page=home\" class=\"active\">Home</a></li>";
	}
	else {
		$menu .= "<li><a href=\"?page=home\">Home</a></li>";
	}

	if (ACP_MODULE_NEWS_EN
				&& (ACP_AdminAccess(ACP_ACCESS_M_NEWS | ACP_ACCESS_M_NEWS_COM | ACP_ACCESS_M_NEWS_CAT))) {
		if ($active == 2) {
			$menu .= "<li><a href=\"?page=news-home\" class=\"active\">Neuigkeiten</a></li>";
		}
		else {
			$menu .= "<li><a href=\"?page=news-home\">Neuigkeiten</a></li>";
		}
	}

	if (ACP_MODULE_POLL_EN && ACP_AdminAccess(ACP_ACCESS_M_POLL)) {
		if ($active == 3) {
			$menu .= "<li><a href=\"?page=Umfrage_Bearbeiten\" class=\"active\">Umfrage</a></li>";
		}
		else {
			$menu .= "<li><a href=\"?page=Umfrage_Bearbeiten\">Umfrage</a></li>";
		}
	}

	if (ACP_MODULE_GUESTBOOK_EN && ACP_AdminAccess(ACP_ACCESS_M_GUESTBOOK)) {
		if ($active == 4) {
			$menu .= "<li><a href=\"?page=guestbook-list\" class=\"active\">Gästebuch</a></li>";
		}
		else {
			$menu .= "<li><a href=\"?page=guestbook-list\">Gästebuch</a></li>";
		}
	}

	if (ACP_MODULE_PHOTOS_EN && ACP_AdminAccess(ACP_ACCESS_M_PHOTOS)) {
		if ($active == 5) {
			$menu .= "<li><a href=\"?page=photos-show\" class=\"active\">Fotoalbum</a></li>";
		}
		else {
			$menu .= "<li><a href=\"?page=photos-show\">Fotoalbum</a></li>";
		}
	}
	
	if (ACP_MODULE_LAWDB_EN && ACP_AdminAccess(ACP_ACCESS_M_LAWDB)) {
		if ($active == 20) {
			$menu .= "<li><a href=\"?page=lawdb-list\" class=\"active\">Linkverzeichnis</a></li>";
		}
		else {
			$menu .= "<li><a href=\"?page=lawdb-list\">Linkverzeichnis</a></li>";
		}
	}

	return $menu;
}

/** Generiert das sekundaere Menue */
function SecondMenu($active) {
	$menu = "";
	switch ($active) {
		case 1:
			/* Home */
			$menu .= "<li class=\"first\"><a href=\"?page=home\">Startseite</a></li>";
			if (ACP_AdminAccess(ACP_ACCESS_WEBSITE)) {
				$menu .= "<li><a href=\"?page=website-tree\">Menu Stamm</a>";
				$menu .= "  <ol class=\"submenu\" style=\"display:none\" id=\"secondmenu_menutree\">";
				if (MENU_MAX_LEVEL_CATEGORIE)
					$menu .= "    <li><a href=\"?page=website-edit&amp;mode=categorie\">Neue Kategorie</a></li>";
				$menu .= "    <li><a href=\"?page=website-edit&amp;mode=page\">Neue Seite</a></li>";
				$menu .= "  </ol></li>";
			}
			if (ACP_FILE_SYSTEM_EN && ACP_AdminAccess(ACP_ACCESS_FILESYSTEM)) {
				$menu .= "<li><a href=\"?page=filesystem-public\">Dateisystem</a></li>";
			}
			if (ACP_USER_SYSTEM_EN && ACP_AdminAccess(ACP_ACCESS_USER)) {
				$menu .= "<li><a href=\"?page=user-list\">Benutzer</a>";
				$menu .= "  <ol class=\"submenu\" style=\"display:none\" id=\"secondmenu_user\">";
				$menu .= "    <li><a href=\"?page=user-group-list\">Gruppen</a></li>";
				if (ACP_MODULE_NEWSLETTER_EN && ACP_AdminAccess(ACP_ACCESS_M_NEWS_LETTER)) {
					$menu .= "<li><a href=\"?page=newsletter-send\">Newsletter</a></li>";
				}
				$menu .= "  </ol></li>";
			}
			else {
				if (ACP_MODULE_NEWSLETTER_EN && ACP_AdminAccess(ACP_ACCESS_M_NEWS_LETTER)) {
					$menu .= "<li><a href=\"?page=newsletter-send\">Newsletter</a></li>";
				}
			}
			if (ACP_AdminAccess(ACP_ACCESS_ADMIN)) {
				$menu .= "<li><a href=\"?page=website-settings\">Einstellungen</a>";
				$menu .= "  <ol class=\"submenu\" style=\"display:none\" id=\"secondmenu_setting\">";
				$menu .= "    <li><a href=\"?page=admin-list\">Administratoren</a></li>";
				//$menu .= "    <li><a href=\"?page=website-ipban-list\">IP Sperren</a></li>";
				$menu .= "    <li><a href=\"?page=backup-mysql-list\">Backup</a></li>";
				$menu .= "  </ol></li>";
			}
			if (ACP_MODULE_STATISTIC) {
				$menu .= "<li><a href=\"?page=stats-month\">Statistik</a>";
				$menu .= "  <ol class=\"submenu\" style=\"display:none\" id=\"secondmenu_stats\">";
				$menu .= "    <li><a href=\"?page=stats-page\">Top Seiten</a></li>";
				$menu .= "    <li><a href=\"?page=stats-timeview\">Top Zeiten</a></li>";
				$menu .= "  </ol></li>";
			}
			$menu .= "<li><a href=\"?page=password\">Passwort ändern</a></li>";
			break;

		case 2:
			/* News */
			$menu .= "<li class=\"first\"><a href=\"?page=news-home\">Übersicht</a></li>";
			if (ACP_MODULE_NEWS_EN && ACP_AdminAccess(ACP_ACCESS_M_NEWS | ACP_ACCESS_M_NEWS_COM)) {
				$menu .= "<li><a href=\"?page=news-list\">Neuigkeiten</a>";
				$menu .= "<ol class=\"submenu\" style=\"display:none\" id=\"secondmenu_news\">";
				$menu .= "  <li><a href=\"?page=news-edit\">Schreiben</a></li>";
				$menu .= "</ol></li>";
			}
			if (ACP_MODULE_NEWS_EN && ACP_AdminAccess(ACP_ACCESS_M_NEWS_CAT)) {
				$menu .= "<li><a href=\"?page=news-categorie-list\">Kategorien</a></li>";
			}
			break;

		case 3:
			/* Umfrage */
			if (ACP_MODULE_POLL_EN && ACP_AdminAccess(ACP_RECHTE_UMFRAGE)) {
				$menu .= "<li class=\"first\"><a href=\"?page=Umfrage_Bearbeiten\">Bearbeiten</a></li>";
				$menu .= "<li><a href=\"?page=Umfrage_Neu\">Neue Umfrage</a></li>";
			}
			break;

		case 4:
			/* Gaestebuch */
			if (ACP_MODULE_GUESTBOOK_EN && ACP_AdminAccess(ACP_ACCESS_M_GUESTBOOK)) {
				$menu .= "<li class=\"first\"><a href=\"?page=guestbook-list\">Einträge</a></li>";
			}
			break;

		case 5:
			/* Fotoalbum */
			if (ACP_MODULE_PHOTOS_EN && ACP_AdminAccess(ACP_ACCESS_M_PHOTOS)) {
				$menu .= "<li class=\"first\"><a href=\"?page=photos-show\">Alben</a></li>";
			}
			break;
			
		case 20:
			/* Linkverzeichnis */
			if (ACP_MODULE_LAWDB_EN && ACP_AdminAccess(ACP_ACCESS_M_LAWDB)) {
				$menu .= "<li class=\"first\"><a href=\"?page=lawdb-list\">Linkverzeichnis</a>";
				$menu .= "  <ol class=\"submenu\" style=\"display:none\" id=\"secondmenu_lawdb\">";
				$menu .= "    <li><a href=\"?page=lawdb-edit\">Neuer Artikel</a></li>";
				$menu .= "    <li><a href=\"?page=lawdb-filter\">Filter</a></li>";
				$menu .= "  </ol></li>";
				$menu .= "<li><a href=\"?page=lawdb-categorie-list\">Kategorien</a></li>";
				$menu .= "<li><a href=\"?page=lawdb-office-list\">Unternehmensbereiche</a></li>";
				$menu .= "<li><a href=\"?page=lawdb-source-list\">Quellen</a></li>";
			}
			break;

	}

	return $menu;
}

?>