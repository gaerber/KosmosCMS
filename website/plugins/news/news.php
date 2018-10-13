<?php

 /*
 =====================================================
 Name ........: Plugin: Newssystem
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: news.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |3.0     | 02.10.2011 | Programm erstellt
 |3.1     | 23.01.2012 | Fertigstellung Ausgaben
 |3.2     | 26.10.2012 | SPAM Schutz
 -----------------------------------------------------
 Beschreibung :
 Plugin: Anzeigesoftware der News mit allen Feautures.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_NEWS_EN) {
	/* Einen Artikel (Komplette News mit Kommentaren ) */
	if (isset($_GET[PLUGIN_NEWS_GETP_LONGNEWS]) && $_GET[PLUGIN_NEWS_GETP_LONGNEWS] != "") {
		/* Komplette Neuigkeit ausgeben */
		$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."news
				WHERE id_str='".StdSqlSafety($_GET[PLUGIN_NEWS_GETP_LONGNEWS])."'
				&& locked=0")
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = $result->fetch_assoc()) {
			/* Berechtigung pruefen */
			if (CheckAccess($line['access'])) {
				/* Ausgabe Neuigkeit */
				$tpl = new tpl("plugins/news/article_long");
				/* Kategorie Informationen */
				$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."news_categorie
						WHERE id=".$line['categorie_id'])
						OR FatalError(FATAL_ERROR_MYSQL);
				$line_cat = $result->fetch_assoc();
				$line['categorie_id_str'] = $line_cat['id_str'];
				$line['categorie_name'] = $line_cat['name'];
				/* Autor Informationen */
				$line['writer_name'] = "";
				$line['writer_email'] = "";
				getWriterInfo($line['writer'],
						$line['writer_name'], $line['writer_email']);
				/* Datum */
				$line['date'] = printDate($line['timestamp']);
				$PluginContent['date'] = printDate($line['timestamp']);
				/* Short News hervorheben */
				if ($line['news_long'] != "")
					$line['news_short'] = "<div class=\"news_short\">".$line['news_short']."</div>";
				$line['read_more_url_only'] = "{module_path}/".PLUGIN_NEWS_GETP_LONGNEWS."/".$line['id_str'];
				$tpl->assign($line);
				$tpl->out();

				/* Kommentare */
				if (PLUGIN_NEWS_COMMENT_EN) {
					/* Formular fuer Kommentare */
					$form = new formWizard('form', '', 'post', 'form_standard');
					$o_spam = new SpamProtection('module_news_'.$line['id'], $form);
					/* Bei Gaesten nach Kontaktinformationen fragen */
					if (!$_SESSION['user_id']) {
						$name = $form->addElement('text', 'name', 'Name', getUserInfo('user_name'), true);
						$email = $form->addElement('text', 'email', 'Email Adresse', getUserInfo('ueser_email'));
						$email->setCustomValidation('email', NULL);
						$website = $form->addElement('text', 'website', 'Website', getUserInfo('user_website'));
						$website->setCustomValidation('website', NULL);
					}
					$comment = $form->addElement('textarea', 'comment', 'Nachricht', NULL, true);
					$comment->setRowsCols(7,20);
					$o_spam->printCaptcha();
					$submit = $form->addElement('submit', 'btn', NULL, 'Speichern');

					/* Formular pruefen */
					if ($form->checkForm()) {
						if ($o_spam->check()) {
							/* Kommentar abspeichern */
							$user_infos = array();
							if ($_SESSION['user_id']) {
								$user_infos['name'] = $_SESSION['user_name'];
								if ($_SESSION['user_email_show'])
									$user_infos['email'] = $_SESSION['user_email'];
								else
									$user_infos['email'] = "";
								$user_infos['website'] = $_SESSION['user_website'];
							}
							else {
								$user_infos['name'] = StdSqlSafety($name->getValue());
								$user_infos['email'] = StdSqlSafety($email->getValue());
								$user_infos['website'] = StdSqlSafety($website->getValue());
							}
							if (Database::instance()->query("INSERT INTO ".DB_TABLE_PLUGIN."news_comment(
									news_id, writer_id, writer_name, writer_email, writer_website,
									comment, timestamp)VALUES(
									".$line['id'].", ".$_SESSION['user_id'].", '".$user_infos['name']."',
									'".$user_infos['email']."', '".$user_infos['website']."',
									'".StdSqlSafety(StdContent($comment->getValue(), false))."',
									".TIME_STAMP.")")) {
								echo ActionReport(REPORT_OK, "Kommentar gespeichern",
										"Vielen Dank für Ihren Kommentar!");
							}
							else {
								echo ActionReport(REPORT_ERROR, "Fehler beim Abspeichern",
										"Beim abspeichern trat eine Fehler auf!<br />Mysql Error: ".Database::instance()->getErrorMessage());
							}
						}
					}

					/* Ausgabe der Kommentare (mit Seitenzahlen) */
					$result = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news_comment
							WHERE news_id=".$line['id'])
							OR FatalError(FATAL_ERROR_MYSQL);
					$count = $result->fetch_row();

					if ($count[0] > 0) {
						/* Eintraege vorhanden */
						$classPagination = new pagination($count[0], isset($_GET[PLUGIN_NEWS_GETP_COM_PAGE])
								? $_GET[PLUGIN_NEWS_GETP_COM_PAGE] : 1, PLUGIN_NEWS_NUM_COMMENT);

						$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."news_comment
								WHERE news_id=".$line['id']." ORDER BY timestamp DESC
								LIMIT ".$classPagination->Offset().",".PLUGIN_NEWS_NUM_COMMENT)
								OR FatalError(FATAL_ERROR_MYSQL);

						while ($row = $result->fetch_assoc()) {
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

							/* Datum */
							$row['date'] = printDate($row['timestamp']);

							/* Email */
							if ($row['writer_email'] != "") {
								$tpl = new tpl("plugins/news/comment_icon/email");
								$row['writer_email'] = chgToUC($row['writer_email']);
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
							$tpl = new tpl("plugins/news/comment");
							$tpl->assign($place_holder);
							$tpl->assign($row);
							$tpl->out();
						}

						/* Seitenzahlen */
						if ($classPagination->NumberOfPage() > 1) {
							echo "<div class=\"pagination\">";
							echo $classPagination->PaginationLinks("{module_path}/".PLUGIN_NEWS_GETP_COM."/".$line['id_str']."/".PLUGIN_NEWS_GETP_COM_PAGE."/", PAGINATION_NUM);
							echo "</div>\r\n";
						}

						/* Neuster Kommentar fuer Stand der Seite */
						$res = Database::instance()->query('SELECT timestamp FROM '.DB_TABLE_PLUGIN.'news_comment
								WHERE news_id='.$line['id'].' ORDER BY timestamp DESC LIMIT 1')
								OR FatalError(FATAL_ERROR_MYSQL);
						if ($l = $res->fetch_assoc()) {
							$PluginContent['date'] = printDate($l['timestamp']);
						}
					}


					/* Ausgabe des Formulars */
					if (!$form->checkForm()) {
						$o_spam->printingForm();
						echo $form->getForm();
					}
					else if (!$o_spam->checkState()) {
						echo ActionReport(REPORT_SPAM, 'Anti Spam Sicherung', $o_spam->getErrorMessage());
						echo $form->getForm();
					}
				}
			}
			else {
				/* Keine Berechtigung */
				echo ActionReport(REPORT_WARNING, "Keine Berechtigung",
						"Sie müssen sich anmelden um diesen Beitrag zu lesen.");
			}
		}
		else {
			/* Artikel nicht gefunden */
			echo ActionReport(REPORT_EINGABE, "Artikel nicht gefunden",
					"Der gewünschte Artikel wurde in der Datenbank nicht gefunden.");
		}
	}
	else {
		/* Ausgabe der Neuigkeiten */
		/* Filter */
		$filter_sql = "locked=0 ";
		$filter_txt = "Beiträge";
		if (isset($_GET[PLUGIN_NEWS_GETP_WRITER]) && $_GET[PLUGIN_NEWS_GETP_WRITER] != "") {
			$result = Database::instance()->query("SELECT admin_id, login, name FROM ".DB_TABLE_ROOT."cms_admin
					WHERE login='".StdSqlSafety($_GET[PLUGIN_NEWS_GETP_WRITER])."'")
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line = $result->fetch_assoc()) {
				$filter_sql .= "&& writer=".$line['admin_id']." ";
				$filter_txt .= " von ".$line['name'];
			}
		}
		if (isset($_GET[PLUGIN_NEWS_GETP_CAT]) && $_GET[PLUGIN_NEWS_GETP_CAT] != "") {
			$result = Database::instance()->query("SELECT id, id_str, name FROM ".DB_TABLE_PLUGIN."news_categorie
					WHERE id_str='".StdSqlSafety($_GET[PLUGIN_NEWS_GETP_CAT])."'")
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line = $result->fetch_assoc()) {
				$filter_sql .= "&& categorie_id=".$line['id'];
				$filter_txt .= " aus der Kategorie ".$line['name'];
			}
		}

		/* Anzahl Neuigkeiten ermitteln*/
		$result = Database::instance()->query("SELECT count(*) FROM ".DB_TABLE_PLUGIN."news WHERE ".$filter_sql." && ".CheckSQLAccess())
				OR FatalError(FATAL_ERROR_MYSQL);
		$line = $result->fetch_row();

		if ($line[0] > 0) {
			/* Eintraege vorhanden */
			if (PLUGIN_NEWS_VIEW_ALL) {
				$classPagination = new pagination($line[0], isset($_GET[PLUGIN_NEWS_GETP_COM_PAGE])
						? $_GET[PLUGIN_NEWS_GETP_COM_PAGE] : 1, PLUGIN_NEWS_NUM);

				$result = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."news
						WHERE ".$filter_sql." && ".CheckSQLAccess()." ORDER BY timestamp DESC
						LIMIT ".$classPagination->Offset().",".PLUGIN_NEWS_NUM)
						OR FatalError(FATAL_ERROR_MYSQL);
			}
			else {
				$result = Database::instance()->query("SELECT *	FROM ".DB_TABLE_PLUGIN."news
						WHERE ".$filter_sql." && ".CheckSQLAccess()." ORDER BY timestamp DESC
						LIMIT ".PLUGIN_NEWS_NUM)
						OR FatalError(FATAL_ERROR_MYSQL);
			}

			$news_ctr = 1;

			while ($row = $result->fetch_assoc()) {
				/* Ausgabe Neuigkeit */
				$tpl = new tpl("plugins/news/article_short");
				/* CSS Class first */
				if ($news_ctr == 1)
					$row['class_first'] = " first";
				else
					$row['class_first'] = "";
				/* Kategorie Informationen */
				$result_cat = Database::instance()->query("SELECT * FROM ".DB_TABLE_PLUGIN."news_categorie
						WHERE id=".$row['categorie_id'])
						OR FatalError(FATAL_ERROR_MYSQL);
				$line_cat = $result_cat->fetch_assoc();
				$row['categorie_id_str'] = $line_cat['id_str'];
				$row['categorie_name'] = $line_cat['name'];
				/* Autor Informationen */
				$row['writer_name'] = "";
				$row['writer_email'] = "";
				getWriterInfo($row['writer'],
						$row['writer_name'], $row['writer_email']);
				/* Datum */
				$row['date'] = printDate($row['timestamp']);
				/* Weiterlesen Kommentare */
				/* Anzahl Kommentare */
				$res_com = Database::instance()->query("SELECT count(*), timestamp FROM ".DB_TABLE_PLUGIN."news_comment
						WHERE news_id=".$row['id']." ORDER BY timestamp DESC")
						OR FatalError(FATAL_ERROR_MYSQL);
				if (($line_com = $res_com->fetch_row()) && $line_com[0]) {
					if ($line_com[0] == 1)
						$row['comment_text'] = "1 Kommentar";
					else
						$row['comment_text'] = $line_com[0]." Kommentare";
				}
				else {
					/* Keine Kommentare */
					$row['comment_text'] = "Kommentar schreiben";
				}

				$row['read_more_url_only'] = "{module_path}/".PLUGIN_NEWS_GETP_LONGNEWS."/".$row['id_str'];

				if ($row['news_long'] != "") {
					$row['read_more'] = "<a href=\"".$row['read_more_url_only']."\">Weiterlesen</a>";
					if ($row['comment_text'][0] == 'K')
						$row['read_more_comment_text'] = "Weiterlesen";
					else
						$row['read_more_comment_text'] = "Weiterlesen (".$row['comment_text'].")";
				}
				else {
					$row['read_more'] = '';
					$row['read_more_comment_text'] = $row['comment_text'];
				}

				$row['comment_url'] = "<a href=\"{module_path}/".PLUGIN_NEWS_GETP_COM."/".$row['id_str']."\">".$row['comment_text']."</a>";
				$row['read_more_comment_url'] = "<a href=\"{module_path}/".PLUGIN_NEWS_GETP_COM."/".$row['id_str']."\">".$row['read_more_comment_text']."</a>";

				$tpl->assign($row);
				$tpl->out();

				$news_ctr++;
			}

			/* Seitenzahlen */
			if (PLUGIN_NEWS_VIEW_ALL && $classPagination->NumberOfPage() > 1) {
				echo "<div class=\"pagination\">";
				echo $classPagination->PaginationLinks("{module_path}/".PLUGIN_NEWS_GETP_COM_PAGE."/", PAGINATION_NUM);
				echo "</div>\r\n";
			}

			/* Neuster Artikel fuer Stand der Seite */
			$result = Database::instance()->query('SELECT timestamp FROM '.DB_TABLE_PLUGIN.'news
					WHERE '.$filter_sql.' && '.CheckSQLAccess().'
					ORDER BY timestamp DESC LIMIT 1')
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line = $result->fetch_assoc()) {
				$PluginContent['date'] = printDate($line['timestamp']);
			}
		}
		else {
			/* Keine Eintraege vorhanden */
			echo ActionReport(REPORT_INFO, "Keine Beiträge vorhanden", "Es existieren noch keine ".$filter_txt."!");
		}
	}
}

?>
