<?php

 /*
 =====================================================
 Name ........: Plugin All Page: Headlines
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: news_headlines.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 13.02.2012 | Programm erstellt
 |1.1     | 24.11.2012 | Dynamische URL des Moduls
 -----------------------------------------------------
 Beschreibung :
 Eine Liste der aktuellsten Neuigkeiten.

 (c) by Kevin Gerber
 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_NEWS_EN) {
	
	/* HTML Buffer */
	$PluginContent['plugin_news_headlines'] = '';
	
	/* URL des Moduls ermitteln fuer die Links */
	$result = mysql_query('SELECT menu.id 
			FROM '.DB_TABLE_ROOT.'cms_plugin AS plugin 
			INNER JOIN '.DB_TABLE_ROOT.'cms_menu AS menu ON plugin.id=menu.plugin
			WHERE plugin.label="Neuigkeiten"', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	$line = mysql_fetch_row($result);
	
	$o_modlue_path = new activePage(DB_CMS);
	
	if ($url = $o_modlue_path->getUrlById($line[0])) {
		$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'news
				WHERE locked=0 && '.CheckSQLAccess().' && timestamp>='.(TIME_STAMP - 5184000).' ORDER BY timestamp DESC
				LIMIT 0,2', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		
		while ($row = mysql_fetch_array($result)) {
			$tpl = new tpl('plugins/news/headlines');
			/* Kategorie Informationen */
			/*$res_cat = mysql_query("SELECT * FROM ".DB_TABLE_PLUGIN."news_categorie
					WHERE id=".$row['categorie_id'], DB_CMS)
					OR FatalError(FATAL_ERROR_MYSQL);
			$line_cat = mysql_fetch_array($res_cat);
			$row['categorie_id_str'] = $line_cat['id_str'];
			$row['categorie_name'] = $line_cat['name'];*/
			/* Autor Informationen */
			/*$row['writer_name'] = "";
			$row['writer_email'] = "";
			getWriterInfo($row['writer'],
					$row['writer_name'], $row['writer_email']);*/
			/* Datum */
			/*$row['date'] = printDate($row['timestamp']);*/
			$row['read_more_url_only'] = $url.'/'.PLUGIN_NEWS_GETP_LONGNEWS.'/'.$row['id_str'];
			$row['news_short'] = substr(StdContentEdit($row['news_short']), 0, 80);
			$tpl->assign($row);
			$PluginContent['plugin_news_headlines'] .= $tpl->get();
		}
	}
}

?>