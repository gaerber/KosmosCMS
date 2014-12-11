<?php

 /*
 =====================================================
 Name ........: Plugin Submenu: Fotoalbum
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
 |1.0     | 29.04.2012 | Programm erstellt
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

/* Rekursive Funktion */
function generatePhotoList($ftp, $o_menuClass, $current_path, $current_album_id) {
	global $FileSystem_ModulePahts;
	
	$html_menu = "";
	
	/* Liste aller Unteralben erstellen */
	$result = mysql_query('SELECT id_str FROM '.DB_TABLE_PLUGIN.'photoalbum WHERE menu_sub='.$current_album_id.' 
			&& locked=0 && '.CheckSQLAccess().' ORDER BY menu_order ASC', DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	
	/* Fake Nummerierung */
	$ctr = 0;
	$a_size = mysql_num_rows($result);
	
	while ($row = mysql_fetch_assoc($result)) {
		/* Erst prüfen ob es ein valides Album ist */
		if ($album_info_sub = readAlbumConfig2($ftp, $FileSystem_ModulePahts['photos2'].$current_path.$row['id_str'].'/')) {
			/* Nach weiteren Unteralben suchen */
			$o_menuClass->settings['level']++;
			$submenu = generatePhotoList($ftp, $o_menuClass, $current_path.$album_info_sub['id_str'].'/', $album_info_sub['id']);	
			$o_menuClass->settings['level']--;
			
			/* Ausgabe des Albums */
			$replace = array('element' => 'page', 'level' => $o_menuClass->settings['level'],
					'pos' => $o_menuClass->positionElements($ctr+1, $a_size), 'active' => '');
		
			$temp_template_folder = $o_menuClass->settings['template_folder'];
			foreach ($replace as $key => $value) {
				$temp_template_folder = str_replace("{".$key."}", $value,
						$temp_template_folder);
			}
		
			/* Ausgabe */
			$tpl = new tpl($temp_template_folder);
			$tpl->assign($replace);
			$tpl->assign('submenu', $submenu);
			$tpl->assign('label', $album_info_sub['caption']);
			$tpl->assign("url", ROOT_WEBSITE.implode("/", $o_menuClass->settings['path']).URL_ENDSTR_PAGE
					."/".MODULE_PHOTOS_GETP_ALBUM."/"
					.str_replace('/', '_', $current_path).$album_info_sub['id_str']);
		
			$html_menu .= $tpl->get();
		}
		$ctr++;
	}
	return $html_menu;
}

/* Funktion ausfuehren */
$ftp = new ftp();
$html_menu .= generatePhotoList($ftp, $this, '', 0);
$ftp->close();

?>