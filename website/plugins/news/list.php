<?php

 /*
 =====================================================
 Name ........: Plugin Submenu: News
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: list.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 22.02.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :

 (c) by Kevin Gerber
 =====================================================
 */

/*
$this->db_stream
$this->settings['level']
$this->settings['path'][]
*/

///////////////////////////////////////////////////////
if (!defined("SWISS_WEBDESIGN"))	die();
///////////////////////////////////////////////////////

if (ACP_MODULE_NEWS_EN) {
	$result = $this->db_stream->query("SELECT id, id_str, caption FROM ".DB_TABLE_PLUGIN."news
			WHERE ".CheckSQLAccess()." && locked=0
			ORDER BY timestamp DESC")
			OR FatalError(FATAL_ERROR_MYSQL);

	$ctr = 1;
	$anz_elements = $result->num_rows;

	while ($row = $result->fetch_assoc()) {
		$replace = array('element' => 'page', 'level' => $this->settings['level'],
				'pos' => $this->positionElements($ctr, $anz_elements), 'active' => '');

		$temp_template_folder = $this->settings['template_folder'];
		foreach ($replace as $key => $value) {
			$temp_template_folder = str_replace("{".$key."}", $value,
					$temp_template_folder);
		}

		/* Ausgabe */
		$tpl = new tpl($temp_template_folder);
		$tpl->assign($row);
		$tpl->assign($replace);
		$tpl->assign('submenu', '');
		$tpl->assign('label', $row['caption']);
		$tpl->assign("url", ROOT_WEBSITE.implode("/", $this->settings['path']).URL_ENDSTR_PAGE
				."/".PLUGIN_NEWS_GETP_LONGNEWS."/".$row['id_str']);

		$html_menu .= $tpl->get();

		$ctr++;
	}
}

?>
