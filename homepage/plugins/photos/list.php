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
function generatePhotoList($ftp, $o_menuClass, $settings, $current_album) {
	global $FileSystem_ModulePahts;
	
	$path = $FileSystem_ModulePahts['photos'];
	$html_menu = "";
	
	/* Liste aller Unteralben und Fotos erstellen */
	$a_albums = array();
	$a_photos = array();
	readAlbumPhotosFtp($ftp, $path.$current_album, $a_albums, $a_photos, true);
	
	$a_size = sizeof($a_albums);
	
	/* Liste aller Unteralben erstellen */
	for ($ctr=0; $ctr<$a_size; $ctr++) {
		if (($album_info_sub = readAlbumConfigFtp($ftp, $path.$current_album.$a_albums[$ctr]))
				&& CheckAccess($album_info_sub['access'])
				&& $album_info_sub['locked'] == 0) {
			/* Nach weiteren Unteralben suchen */
			$settings['level']++;
			$submenu = generatePhotoList($ftp, $o_menuClass, $settings, $current_album.$a_albums[$ctr]);	
			$settings['level']--;
			
			/* Ausgabe des Albums */
			$replace = array('element' => 'page', 'level' => $settings['level'],
					'pos' => $o_menuClass->positionElements($ctr+1, $a_size), 'active' => '');
		
			$temp_template_folder = $settings['template_folder'];
			foreach ($replace as $key => $value) {
				$temp_template_folder = str_replace("{".$key."}", $value,
						$temp_template_folder);
			}
		
			/* Ausgabe */
			$tpl = new tpl($temp_template_folder);
			$tpl->assign($replace);
			$tpl->assign('submenu', $submenu);
			$tpl->assign('label', $album_info_sub['title']);
			$tpl->assign("url", ROOT_WEBSITE.implode("/", $settings['path']).URL_ENDSTR_PAGE
					."/".MODULE_PHOTOS_GETP_ALBUM."/"
					.str_replace('/', '_', $current_album).substr($a_albums[$ctr], 0, -1));
		
			$html_menu .= $tpl->get();
		}
	}
	
	return $html_menu;
}

/* Funktion ausfuehren */
$ftp = new ftp();
$html_menu .= generatePhotoList($ftp, $this, $this->settings, '');
$ftp->close();

?>