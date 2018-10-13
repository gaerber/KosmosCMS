<?php

 /*
 =====================================================
 Name ........: Plugin: Gaestebuch
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: guestbook.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |3.0     | 23.01.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :
 Plugin: Nur ausgabe des Gaestebuches

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_GUESTBOOK_EN) {
	/* Anzahl Eintraege ermitteln */
	$result = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."guestbook")
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = $result->fetch_row();

	if ($line[0] > 0) {
		$classPagination = new pagination($line[0], isset($_GET[PLUGIN_GUESTBOOK_GETP_PAGE])
				? $_GET[PLUGIN_GUESTBOOK_GETP_PAGE] : 1, PLUGIN_GUESTBOOK_NUM);

		/* Eintraege selektieren */
		$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."guestbook
				ORDER BY timestamp DESC LIMIT ".$classPagination->Offset().",".PLUGIN_GUESTBOOK_NUM)
				OR FatalError(FATAL_ERROR_MYSQL);

		/* Eintraege */
		while ($row = $result->fetch_assoc()) {
			$place_holder = array();
			/* Benutzerinfos bei registrierten Benutzer */
			if ($row['writer_id']) {
				$res = Database::instance()->query("SELECT user_name, user_email_show, user_email, user_website
						FROM ".DB_TABLE_ROOT."cms_access_user
						WHERE user_id=".$row['writer_id'])
						OR FatalError(FATAL_ERROR_MYSQL);
				if ($line_usr = $res->fetch_assoc()) {
					/* Daten ueberschreiben */
					$row['writer_name'] = $line_usr['user_name'];
					if ($line_usr['user_email_show'])
						$row['writer_email'] = $line_usr['user_email'];
					else
						$row['writer_email'] = "";
					$row['writer_website'] = $line_usr['user_website'];
				}
			}

			/* Admin Kommentar */
			if ($row['admin_comment']) {
				/* Admin Informationen */
				$admin_info = array();
				$admin_info['writer_name'] = "";
				$admin_info['writer_email'] = "";
				getWriterInfo($row['admin_id'],
						$admin_info['writer_name'], $admin_info['writer_email']);

				$tpl = new tpl("plugins/guestbook/admincomment");
				$tpl->assign($admin_info);
				$tpl->assign("admin_comment", $row['admin_comment']);
				$place_holder['admin_comment_tpl'] = $tpl->get();
			}
			else {
				$row['admin_comment_tpl'] = "";
			}

			/* Datum */
			$row['date'] = printDate($row['timestamp']);

			/* Email */
			$row['writer_email'] = chgToUC($row['writer_email']);
			if ($row['writer_email'] != "") {
				$tpl = new tpl("plugins/guestbook/icon/email");
				$place_holder['writer_email_tpl'] = $tpl->get();
			}
			else {
				$place_holder['writer_email_tpl'] = "";
			}

			/* Website */
			if ($row['writer_website'] != "") {
				$tpl = new tpl("plugins/guestbook/icon/website");
				$row['writer_website_short'] = getSmallUrlView($row['writer_website']);
				$place_holder['writer_website_tpl'] = $tpl->get();
			}
			else {
				$place_holder['writer_website_tpl'] = "";
			}

			/* Ausgabe */
			$tpl = new tpl("plugins/guestbook/comment");
			$tpl->assign($place_holder);
			$tpl->assign($row);
			$tpl->out();
		}

		/* Seitenzahlen */
		echo "<div class=\"pagination\">";
		echo $classPagination->PaginationLinks("{module_path}/".PLUGIN_GUESTBOOK_GETP_PAGE."/", PAGINATION_NUM);
		echo "</div>\r\n";

		/* Neuster Eintrag fuer Stand der Seite */
		$result = Database::instance()->query('SELECT timestamp FROM '.DB_TABLE_PLUGIN.'guestbook
				ORDER BY timestamp DESC LIMIT 1')
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			$PluginContent['date'] = printDate($line['timestamp']);
		}
	}
	else {
		/* Keine Eintraege */
		echo ActionReport(REPORT_INFO, "Keine Einträge vorhanden", "Es existieren noch keine Gästebucheinträge!");
	}
}

?>
