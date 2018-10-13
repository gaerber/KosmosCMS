<?php

 /*
 =====================================================
 Name ........: Klassen
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: _classes.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 08.07.2008 | Template Klasse
 |1.1     | 14.12.2008 | Add Menu Link Settings
 |1.2     | 22.11.2009 | Active In hinzugefuegt
 |2.0     | 05.05.2011 | Neuueberarbeitung Menu Klasse
 |2.0.1   | 11.05.2011 | cms_menu bei buildMenuTree
 |2.0.2   | 07.10.2011 | Bugfix level_printed_all
 |2.0.3   | 13.10.2011 | Bugfix FTP Class
 |2.0.4   | 20.08.2012 | changeActivePage mit Stammbaum
 |2.0.4.1 | 16.09.2012 | activePage::isUrlCategorie()
 |2.1     | 21.11.2012 | class::ftpOpenDir()
 |2.2     | 24.11.2012 | cativePage::getUrlById()
 |3.0     | 21.11.2012 | FTP Erweiterung, class::ftpOpenDir
 |3.1     | 21.04.2013 | class::ftpOpenDir mit RawList
 |3.1.1   | 29.04.2013 | class::ftp checkPath() hinzu
 |3.1.2   | 13.10.2014 | FTP CleanFolder fuer Dot-Files
 |3.1.3   | 11.12.2014 | Bugfix ftp Hilfsklassen
 |3.1.4   | 08.03.2015 | Bugfix versteckte Dateien
 |3.1.5   | 25.03.2015 | Bugfix Content-Length
 |3.1.6   | 13.08.2015 | Bugfix Ordnerloeschen
 |4.0     | 13.10.2018 | Datenbank Abstraktion
 -----------------------------------------------------
 Beschreibung :
 Alle Klassen enthalten.

 (c) by Kevin Gerber
 =====================================================
 */

include("class.form.php");

/**
 * Database abstraction
 */
class Database {
	public static function instance() {
		if (self::$instance == NULL) {
			self::$instance = new Database();
		}
		return self::$instance;
	}

	public function query($sql) {
		return $this->mysqli->query($sql);
	}

	public function hasError() {
		return ($this->mysqli->errno != 0);
	}

	public function getErrorMessage() {
		return $this->mysqli->error;
	}

	public function close() {
		$this->mysqli->close();
		self::$instance = NULL;
	}

	private function __construct() {
		$this->connect();
		$this->mysqli->set_charset("utf8") OR FatalError(FATAL_ERROR_MYSQL);
	}

	private function connect() {
		$this->mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if ($this->mysqli->connect_errno) {
			die('MySQL connection error: '.$this->mysqli->connect_error);
		}
	}

	protected static $instance = NULL;
	private $mysqli;
}

/**
 * Template Klasse
 */
class tpl {
	var $template_file;
	var $replace_array;
	var $delimiterStart = '{';
	var $delimiterEnd = '}';

	function setStartDelim($delim) {
		$this->delimiterStart = $delim;
	}

	function setEndDelim($delim) {
		$this->delimiterEnd = $delim;
	}

	function tpl($template_file) {
		$template_file = ROOT_TEMPLATE.$template_file.TEMPLATE_TYPE;
		if(file_exists($template_file)) {
			$this->template_file = implode('', file($template_file));
			return $this->template_file;
		}
		else {
			//$this->template_file = '';
			die('failed to load template file: '.$template_file);
		}
	}

	function assign($searchString,$key=false) {
		if(is_array($searchString)) {
			foreach($searchString as $var => $key) {
				$search = $this->delimiterStart.$var.$this->delimiterEnd;
				$replace = $key;
				$this->template_file = str_replace($search,$replace,$this->template_file);
			}
		}
		else {
			$search = $this->delimiterStart.$searchString.$this->delimiterEnd;
			$replace = $key;
			$this->template_file = str_replace($search,$replace,$this->template_file);
		}
		return $this->template_file;
	}

	function get() {
		return $this->template_file;
	}

	function out() {
		echo($this->get());
	}

	function compress_gzip() {
		$compression_level = 9;
		$append_message = '<!-- zlib compression level '.$compression_level.' -->';

		if(!headers_sent() && extension_loaded('zlib')
				&& isset($_SERVER['HTTP_ACCEPT_ENCODING'])
				&& (strstr($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip'))) {
			$tpl_source = gzencode($this->get().$append_message, $compression_level);
    	  	header('Content-Encoding: gzip');
    	  	header('Vary: Accept-Encoding');
     	 	header('Content-Length: '.strlen($tpl_source));
     	 	echo($tpl_source);
		}
    	else {
			$this->out();
		}
	}
}


///////////////////////////////////////////////////////
// Menue
///////////////////////////////////////////////////////

/**
 * Berechnet die Anzahl Unterseiten. Callback.
 * @param $id ist die ID der Seite / Kategorie von der die Anzahl Unterseiten
 * berechnet werden soll.
 */
function countSubLevels($id) {
	static $level=0;
	$level_max = 0;

	$result = mysql_query("SELECT id FROM ".DB_TABLE_ROOT."cms_menu WHERE menu_sub=".$id, DB_CMS)
			OR FatalError(FATAL_ERROR_MYSQL);
	while ($row = mysql_fetch_array($result)) {
		$level++;
		$level_temp = countSubLevels($row['id']);
		if ($level_temp > $level_max)
			$level_max = $level_temp;
		$level--;
	}

	if ($level_max > $level)
		return $level_max;
	else
		return $level;
}

/**
 * Errechnet das Level einer Kategorie oder einer Seite.
 * @param $id ist die ID der Kategorie oder der Seite.
 */
function countLevels($id) {
	$level = 0;

	while ($id > 0) {
		$result = mysql_query("SELECT menu_sub FROM ".DB_TABLE_ROOT."cms_menu WHERE id=".$id, DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			$level++;
			$id = $line['menu_sub'];
		}
		else {
			FatalError(FATAL_ERROR_MENU);
		}
	}

	return $level;
}

/**
 * Berechnet und verwaltet die aktive Seite
 */
class activePage {
	private $db_stream;

	private $active_elements = array();
	private $active_page_id = NULL;
	private $url_is_categorie = false;

	private $path_array = array();
	private $path_ptr = 0;

	private $user_no_access = false;
	private $page_locked = false;

	/**
	 * Initialisierungsfunktion speichert den Datenbank-Stream.
	 * @param $db_stream Datenbank-Stream.
	 */
	function __construct($db_stream) {
		$this->db_stream = $db_stream;
	}

	/**
	 * Loest die uebergebene URL auf.
	 *
	 * @param $url ist die URL der Seite, die der Benuter angefordert hat.
	 * @return true, wenn die URL komplet und fehlerfrei aufgeloest werden konnte,
	 * sonst false.
	 */
	function calcActivePage($url) {
		$this->user_no_access = false;
		if ($url != "") {
			/* Pfad aufteilen */
			$this->path_array = explode("/", $url);
			//$this->path_array_size = count($this->path_array);

			/* URL komplett aufloesen
			   Nur wenn die URL vollstaendig aufgeloest wurde, befinden wir uns auf der
			   richtigen Seite */
			if ($this->solvePath(0)) {
				/* URL wurde vollstaendig aufgeloest */
				if (count($this->active_elements)) {
					/* Tiefstes gefundene Element */
					$this->active_page_id = $this->active_elements[count($this->active_elements)-1];
				}
				/* Pruefen ob es eine Kategorie ist */
				$result = mysql_query("SELECT menu_is_categorie FROM ".DB_TABLE_ROOT."cms_menu
						WHERE id=".$this->active_page_id, $this->db_stream)
						OR FatalError(FATAL_ERROR_MYSQL);
				if ($line = mysql_fetch_array($result)) {
					if ($line['menu_is_categorie'] == 1) {
						$this->url_is_categorie = true;
						$this->active_page_id = $this->selectFirstPage($this->active_page_id);
						if ($this->active_page_id === false) {
							$this->active_page_id = NULL;
							return false;
						}
					}
				}
				else {
					FatalError(FATAL_ERROR_MENU);
				}
			}
			else {
				/* Fehlerhafte URL -> Errorausgabe */
				return false;
			}
		}
		else {
			/* Startseite */
			$this->active_page_id = $this->selectFirstPage(0);
			if ($this->active_page_id === false) {
				$this->active_page_id = NULL;
				return false;
			}
		}
		/* URL ist richtig und die aktive Seite wurde selektiert */
		return true;
	}

	/**
	 * Rueckgabe der aktiven Seite, nachdem die URL fehlerfrei aufgeloest wurde.
	 * @see calcActivePage()
	 * @return ID der aktiven Seite
	 */
	function getActivePage() {
		return $this->active_page_id;
	}

	/**
	 * Die aktive Seite manuell aendern.
	 * @param $active_page_id ist die ID der neuen, aktiven Seite
	 * @return true, wenn die aktive Seite gewechselt werden konnte, sonst false.
	 */
	function changeActivePage($active_page_id) {
		global $DefaultErrorPages;

		/* Variablen zuruecksetzten */
		$this->user_no_access = false;
		$this->page_locked = false;
		$this->active_elements = array();
		
		/* Active Elements aendern zur neuen Stammbaumgenerierung */
		if ($this->calcPathById($active_page_id)) {
			/* Bei Spezialseiten (HTTP Error Pages) spielt locked und access keine Rolle */
			if (in_array($active_page_id, $DefaultErrorPages)
					|| ($this->user_no_access == false && $this->page_locked == false)) {
				$this->active_page_id = $active_page_id;
				/* Arrayreihenvolge anpassen */
				$this->active_elements = array_reverse($this->active_elements);
				return true;
			}
			else {
				/* Kein Zugriff */
				return false;
			}
		}
		return false;
	}

	function getUserAccess() {
		return !$this->user_no_access;
	}
	function getSubElements() {
		return $this->active_elements;
	}
	function getUrlIsCategorie() {
		return $this->url_is_categorie;
	}
	
	function getActuelPath() {
		return $this->path_array;
	}
	function isUrlCategorie() {
		return $this->url_is_categorie;
	}

	/**
	 * Rekursive Funktion um den Pfad aufzuloesen
	 */
	private function solvePath($menu_sub) {
		$result = mysql_query("SELECT id, access FROM ".DB_TABLE_ROOT."cms_menu
				WHERE id_str='".$this->path_array[$this->path_ptr]."' && menu_sub=".$menu_sub."
				&& locked=0", $this->db_stream)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			/* Pruefen ob der Benutzer die Berechtigung hat */
			if (!CheckAccess($line['access'])) {
				$this->user_no_access = true;
			}

			/* Element abspeichern */
			$this->active_elements[] = $line['id'];

			/* Wurde die URL komplett aufgeloest? */
			if ($this->path_ptr++ == (count($this->path_array) - 1)) {
				/* URL wurde komplett aufgeloest */
				return true;
			}
			/* ELSE: Nach Kinderelementen suchen */
			return $this->solvePath($line['id']);
		}
		else {
			/* Kein Element gefunden */
			return false;
		}
	}

	/**
	 * Selektiert die oberste Seite im Stammbaum einer Kategorie, auf die der
	 * Besucher zugriff hat. Es werden auch Unterkategorien beruecksichtigt,
	 * resp. durchsucht falls noetig. (Rekursive Funktion)
	 *
	 * @param $categorie ist die ID der Kategorie in der gesucht werden soll.
	 * @return ID der obersten Seite, oder false falls keine Seite mit Zurgiff
	 * existiert,
	 */
	function selectFirstPage($categorie) {
		$result = mysql_query("SELECT id FROM ".DB_TABLE_ROOT."cms_menu
				WHERE menu_is_categorie=0 && menu_sub=".$categorie."
				&& locked=0 && ".CheckSQLAccess()."
				ORDER BY menu_order ASC LIMIT 1", $this->db_stream)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			$this->active_elements[] = $line['id'];
			return $line['id'];
		}
		else {
			/* Keine Seite in dieser Kategorie -> Nach Unterkategorien suchen */
			$result = mysql_query("SELECT id FROM ".DB_TABLE_ROOT."cms_menu
					WHERE menu_is_categorie=1 && menu_sub=".$categorie."
					&& locked=0 && ".CheckSQLAccess()."
					ORDER BY menu_order ASC", $this->db_stream)
					OR FatalError(FATAL_ERROR_MYSQL);
			while ($row = mysql_fetch_array($result)) {
				$this->active_elements[] = $row['id'];
				/* Die gefundenen Unterkategorien werden der Reihe nach durchsucht */
				$temp_first_page_id = $this->selectFirstPage($row['id']);
				if ($temp_first_page_id !== false) {
					return $temp_first_page_id;
				}
				/* Keine Seite in dieser Unterkategorie gefunden -> Weitersuchen */
				array_pop($this->active_elements);
			}

			/* Auch keine Unterkategorien gefunden -> Existiert keine Seite mit Zugriffsrecht */
			return false;
		}
	}
	
	/**
	 * URL einer bestimmten Seite aufloesen.
	 * @param id ID der Seite nach der aufgeloest werden soll.
	 * @note \v user_no_access, \v page_locked und \v active_elements muss vorher zurueckgesetzt werden.
	 */
	private function calcPathById($id) {
		$result = mysql_query('SELECT menu_sub, access, locked FROM '.DB_TABLE_ROOT.'cms_menu
				WHERE id='.$id, $this->db_stream)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			/* Pruefen ob der Benutzer die Berechtigung hat */
			if (!CheckAccess($line['access'])) {
				$this->user_no_access = true;
			}
			if ($line['locked']) {
				$this->page_locked = true;
			}

			/* Element abspeichern */
			$this->active_elements[] = $id;

			/* Wurde der Pfad komplett aufgeloest? */
			if ($line['menu_sub'] == 0) {
				/* URL wurde komplett aufgeloest */
				return true;
			}
			/* ELSE: Nach Elternelementen suchen */
			return $this->calcPathById($line['menu_sub']);
		}
		else {
			/* Kein Element gefunden */
			return false;
		}
	}
	
	/**
	 * Ermittelt die URL einer eliebigen Seite
	 * @param $page_id ID der Seite
	 * @note Funktioniert nicht mit Kategorien!
	 * @return URL, FALSE falls der Benutzer keine Berechtigung zu diesr Seite hat, oder wenn die Seite gesperrt ist.
	 */
	function getUrlById($page_id) {
		/* Klassenvariablen zuruecksetzten */
		$this->user_no_access = false;
		$this->page_locked = false;
		$this->active_elements = array();
		
		/* Stambaum der Seite generieren */
		if (!$this->calcPathById($page_id))
			return false;
			
		/* Zugriff auf diese Seite pruefen */
		if ($this->user_no_access || $this->page_locked)
			return false;
		
		/* Pfad in URL umwandeln */
		$url = ROOT_WEBSITE;
		$id_list = implode(', ', $this->active_elements);
		
		$result = mysql_query('SELECT id_str FROM '.DB_TABLE_ROOT.'cms_menu 
				WHERE id IN ('.$id_list.') ORDER BY FIELD(id, '.$id_list.') DESC', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		while ($row = mysql_fetch_row($result)) {
			$url .= $row[0].'/';
		}
		
		/* Letztens / ist ueberfluessig */
		$url = substr($url, 0, -1);
		
		/* Abschluss URL */
		$url .= URL_ENDSTR_PAGE;
		
		return $url;
	}
}


/**
 * Generiert die Ausgabe fuer das Menue
 */
class buildMenuTree {
	private $db_stream;
	
	private $active_elements = array();

	private $active_page_id = NULL;

	public $settings = array();


	/**
	 * Initialisierungsfunktion speichert den Datenbank-Stream, sowie die aktiven
	 * Kategorien und Seiten. Diese Stammen von der Klasse activePage
	 * @param $db_stream Datenbank-Stream.
	 * @param $active_elements ist ein Array mit allen aktiven Kategorien und Seiten.
	 */
	function __construct($db_stream, $active_elements=NULL) {
		$this->db_stream = $db_stream;

		if (is_array($active_elements)) {
			$this->active_elements = array_merge($this->active_elements, $active_elements);
			if (count($active_elements))
				$this->active_page_id = $active_elements[count($active_elements)-1];
		}

		$this->settings['sql_condition'] = "";
		$this->settings['allow_plugin_submenu'] = false;
	}

	/**
	 * Aendert die SQL Bedingung
	 */
	function setSqlCondition($condition) {
		$this->settings['sql_condition'] = $condition;
	}
	
	function allowPluginSubmenu($allow) {
		$this->settings['allow_plugin_submenu'] = $allow;
	}

	/**
	 * Berechnet fuer die Templatewahl die Position des Linkes.
	 * @param $element_now ist die Nummer des aktuellen Elements.
	 * @param $elements ist die anzahl aller Elemente.
	 * @return String fuer die Templatewahl.
	 */
	function positionElements($element_now, $max_elements) {
		if ($max_elements == 1)
			return "solo";
		else if ($element_now == 1)
			return "first";
		else if ($element_now == $max_elements)
			return "last";
		else
			return "normal";
	}

	/**
	 * Menuestamm generieren
	 * @param $start_categorie ist die ID der Kategorie, von der aus der
	 * Menuestamm generiert werden soll.
	 * @param $num_level ist die Anzahl Ebene, die beruecksichtigt werden.
	 * @param $level_print_all ist die Anzahl Ebene, bei denen alle
	 * Unterkategorien und -seiten gedruckt werden.
	 * @param $print_all true wenn immer alle Unterkategorien und -seiten gedruckt
	 * werden, oder false wenn nach $level_print_all nur noch die aktiven
	 * Unterkategorien und -seiten gedruckt werden.
	 * @param $template_folder ist die Pfadangaben zum Templateordner.
	 * @return HTML-Code der Menuestruktur.
	 */
	function getMenuTree($start_categorie, $num_level,
			$level_printed_all, $print_all, $template_folder) {
		/* Menueinstellungen zusammenfassen */
		$this->settings['print_all'] = $print_all;
		$this->settings['template_folder'] = $template_folder;
		$this->settings['path'] = array();

		/* Pfad zur Startkategorie ermitteln */
		$start_path = "";
		if (!$this->calcElementPath($start_categorie, $start_path)) {
			FatalError(FATAL_ERROR_CONTENT);
		}

		if ($start_path != "") {
			$this->settings['path'] = explode("/", $start_path);
		}

		$this->settings['level'] = count($this->settings['path']);
		$this->settings['max_level'] = $this->settings['level'] + $num_level;
		$this->settings['print_all_level'] = $this->settings['level'] + $level_printed_all;

		if (($this->settings['level'] < $this->settings['max_level'])
				&& (($this->settings['print_all'] == true)
					|| ($this->settings['level'] < $this->settings['print_all_level'])
					|| in_array($start_categorie, $this->active_elements))) {
			/* Menu generieren */
			$this->settings['level']++;
			return $this->buildMenu($start_categorie);
			$this->settings['level']--;
		}
		return "";
	}

	/**
	 * Druckt das Menue der Kategorien und Seiten. (Rekursive Funktion)
	 */
	private function buildMenu($sub_categorie) {
		/* In einer Kategorie koennen sich Unterkategorien und Seiten befinden */
		$result = mysql_query("SELECT id, id_str, label, menu_is_categorie, plugin
				FROM ".DB_TABLE_ROOT."cms_menu
				WHERE menu_sub=".$sub_categorie."
				".$this->settings['sql_condition']."
				ORDER BY menu_order ASC", $this->db_stream)
				OR FatalError(FATAL_ERROR_MYSQL);

		$html_menu = "";

		$e = 1;
		$elements = mysql_num_rows($result);

		/* Kategorien und Seiten dieser Ebene */
		while ($row = mysql_fetch_array($result)) {
			$this->settings['path'][] = $row['id_str'];
			/* Nur wenn Unterkategorien gedruckt werden duerfen, wird nach ihnen gesucht */
			if (($this->settings['level'] < $this->settings['max_level'])
					&& (($this->settings['print_all'] == true)
						|| ($this->settings['level'] < $this->settings['print_all_level'])
						|| in_array($row['id'], $this->active_elements))) {
				/* Nach Unterkategorien suchen */
				$this->settings['level']++;
				$html_sub_menu = $this->buildMenu($row['id']);
				/* Submenus von Plugins */
				if ($row['plugin'] && $this->settings['allow_plugin_submenu']) {
					$res = mysql_query("SELECT path_menu FROM ".DB_TABLE_ROOT."cms_plugin
							WHERE id=".$row['plugin']." && locked=0", $this->db_stream)
							OR FatalError(FATAL_ERROR_MYSQL);
					if ($line = mysql_fetch_array($res)) {
						if ($line['path_menu'] != "")
							$html_sub_menu .= $this->getPluginSubmenu($line['path_menu']);
					}
					else {
						FatalError(FATAL_ERROR_MENU);
					}
				}
				$this->settings['level']--;
			}
			else {
				$html_sub_menu = "";
			}

			/* Element */
			if ($row['menu_is_categorie'])
				$element_name = "categorie";
			else
				$element_name = "page";

			/* Aktive Kategorie */
			if ($row['id'] == $this->active_page_id) {
				$active = "active";
			}
			else if (in_array($row['id'], $this->active_elements)) {
				$active = "active_in";
			}
			else {
				$active = "";
			}

			/* Position der Seite */
			$pos = $this->positionElements($e, $elements);

			/* Forbereitung Template */
			$replace = array('element' => $element_name, 'level' => $this->settings['level'],
					'pos' => $pos, 'active' => $active);

			$temp_template_folder = $this->settings['template_folder'];

			foreach ($replace as $key => $value) {
				$temp_template_folder = str_replace("{".$key."}", $value,
						$temp_template_folder);
			}

			/* Ausgabe */
			$tpl = new tpl($temp_template_folder);
			$tpl->assign($row);
			$tpl->assign($replace);
			$tpl->assign("submenu", $html_sub_menu);
			/* PFAD */
			if ($row['menu_is_categorie'])
				$tpl->assign("url", ROOT_WEBSITE.implode("/", $this->settings['path']).URL_ENDSTR_CATEGORIE);
			else
				$tpl->assign("url", ROOT_WEBSITE.implode("/", $this->settings['path']).URL_ENDSTR_PAGE);

			$html_menu .= $tpl->get();

			/* Ein Element gedruckt */
			$e++;
			array_pop($this->settings['path']);
		}

		/* Rueckgabe html Code */
		return $html_menu;
	}

	/**
	 * Druckt den Pfad der aktiven Seite.
	 * @param $template_folder ist die Pfadangaben zum Templateordner.
	 * @return HTML-Code des Pfades.
	 */
	function getMenuPath($template_folder) {
		$e = 1;
		$elements = count($this->active_elements);

		$html = "";
		$path_array = array();

		/* Zuerst die Kategorien */
		for ($i = 0; $i < $elements; $i++) {
			$result = mysql_query("SELECT id, id_str, label, menu_is_categorie
					FROM ".DB_TABLE_ROOT."cms_menu
					WHERE id=".$this->active_elements[$i], $this->db_stream)
					OR FatalError(FATAL_ERROR_MYSQL);
			if ($line = mysql_fetch_array($result)) {
				$path_array[] = $line['id_str'];
				$pos = $this->positionElements($e, $elements);

				/* Element */
				if ($line['menu_is_categorie'])
					$element_name = "categorie";
				else
					$element_name = "page";

				/* Template vorbereiten */
				$replace = array('element' => $element_name, 'pos' => $pos);
				$temp_template_folder = $template_folder;

				foreach ($replace as $key => $value) {
					$temp_template_folder = str_replace("{".$key."}", $value,
							$temp_template_folder);
				}

				/* Ausgabe */
				$tpl = new tpl($temp_template_folder);
				$tpl->assign($line);
				$tpl->assign($replace);
				/* Pfad */
				if ($line['menu_is_categorie'])
					$tpl->assign("url", ROOT_WEBSITE.implode("/", $path_array).URL_ENDSTR_CATEGORIE);
				else
					$tpl->assign("url", ROOT_WEBSITE.implode("/", $path_array).URL_ENDSTR_PAGE);
				$html .= $tpl->get();

				/* Ein Element gedruckt */
				$e++;
			}
			else {
				FatalError(FATAL_ERROR_MENU);
			}
		}

		/* Rueckgabe HTML Code */
		return $html;
	}

	/**
	 * Berechnet den dynamischen Pfad einer Seite oder Kategorie (Rekursive Funktion)
	 *
	 * @param $element_id ist die ID der Seite/Kategorie
	 * @param &$path ist der Zeiger auf eine Stringvariable, in der das Resultat
	 * gespeichert wird.
	 * @return true, wenn der Pfad berechnet wurde, sonst false
	 */
	function calcElementPath($element_id, &$path) {
		if ($element_id == 0) {
			/* Muss nicht aufgeloest werden, da Startkategorie (Startseite) */
			return true;
		}

		$result = mysql_query("SELECT id_str, menu_sub FROM ".DB_TABLE_ROOT."cms_menu
				WHERE id=".$element_id, $this->db_stream)
				OR FatalError(FATAL_ERROR_MYSQL);
		if ($line = mysql_fetch_array($result)) {
			if ($path != "")
				$path = $line['id_str']."/".$path;
			else
				$path = $line['id_str'];

			if ($line['menu_sub'] != 0) {
				return $this->calcElementPath($line['menu_sub'], $path);
			}
			else {
				/* Vollstaendig aufgeloest */
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Generiert das Submenu eines Modules
	 * @param $path ist der Pfad zum generieren
	 **/
	private function getPluginSubmenu($path) {
		$html_menu = "";
		
		if (!file_exists(ROOT_PLUGIN.$path)) {
			die("Module ".$path." could not load!");
		}
		
		include(ROOT_PLUGIN.$path);
		return $html_menu;
	}
}


///////////////////////////////////////////////////////
// Seitenzahlen
///////////////////////////////////////////////////////

/**
 * Klasse zur vollstaendigen Gestaltung der Seitenzahlen
 */
class pagination {

	private $num_entry;
	private $active_page;
	private $entry_per_page;
	private $num_page;

	/**
	 * Initialisierungsrutine
	 * @param $anz_eintraege Anzahl aller eintraege ermittelt durch count(*)
	 * @param $aktuelle_seite Aktuelle Seitenzahl uebergeben von einem GET Parameter
	 * @param $eintraege_pro_seite Anzahl Eintraege, die auf eine Seite kommen
	 */
	public function __construct($num_entry, $active_page, $entry_per_page) {
		$this->num_entry = $num_entry;
		$this->entry_per_page = $entry_per_page;

		/* Berechnung der Anzahl Seiten */
		$this->num_page = ceil($num_entry / $entry_per_page);
		if (!$this->num_page)
			$this->num_page = 1;

		/* Aktuelle Seite */
		if($active_page < 1)
			$this->active_page = 1;
		else if($active_page > $this->num_page)
			$this->active_page = $this->num_page;
		else
			$this->active_page = $active_page;
	}

	/**
	 * Berechnet den Datensatz Offset
	 * @return Offset der Datensaetze
	 */
	public function Offset() {
		return ($this->active_page - 1) * $this->entry_per_page;
	}

	/**
	 * Sagt wie viele Seiten gebraucht werden
	 * @return Anzahl Seiten
	 */
	public function NumberOfPage() {
		return $this->num_page;
	}
	
	/**
	 * Aktuelle Seite
	 */
	public function ActivePage() {
		return $this->active_page;
	}

	/**
	 * Dantenbank Zeilennummer der ersten Zeile
	 * @param $richtung Auffteigend (up) oder absteigende (down) Nummerierung
	 * @return Integer Num
	 */
	public function FirstNumber($direction) {
		if ($direction == 'up') {
			/* Aufsteigende Nummerierung ASC */
			return $this->Offset() + 1;
		}
		else {
			/* Absteigende Nummerierung DESC */
			return $this->num_entry - $this->Offset();
		}
	}

	/**
	 * Rueckgabe der Seitenlinks
	 * @param $url Dies ist die komplette URL mit allen Parametern. In dieser
	 * Funktion wird am ende von \iurl nur noch die Seitenzahlen angehaengt.
	 * @param $small Gibt an wie viele Seiten Nummerisch dargestellt werden.
	 * ist \i$small Null, so werden alle Seitenzahlen gelistet.
	 * @return Ein String mit allen Links
	 */
	public function PaginationLinks($url, $small) {
		/* Ausgabe Puffer */
		$content = "";

		if ($small > 0) {
			/* Small muss ungerade sein! */
			if (($small % 2) == 0)
				$small++;
			$small = ($small - 1) / 2;
		}

		if($this->active_page > 1) {
			/* Zurueck */
			$content .= ' <span class="preview"><a href="'.$url.($this->active_page - 1).'">Zurück</a></span> ';
		}
		/* Seiten Links */
		if($this->num_page > 1) {
			$content .= ' ';

			if($small > 0) {
				$small_min = $this->active_page - $small;
				$small_max = $this->active_page + $small;

				/* Verbotene, nicht moegliche Werte */
				if($small_min <= 0) {
					$small_max += 1 - $small_min;
					$small_min = 1;
					if($small_max > $this->num_page) {
						$small_max = $this->num_page;
					}
				}
				else {
					if($small_max > $this->num_page) {
						$small_min -= $small_max - $this->num_page;
						$small_max = $this->num_page;
						if($small_min <= 0) {
							$small_min = 1;
						}
					}
				}

				for($i = $small_min; $i <= $small_max; $i++) {
					if($this->active_page == $i) {
						$content .= ' <span class="current-page">'.$i.'</span> ';
					}
					else {
						$content .= ' <span><a href="'.$url.$i.'">'.$i.'</a></span> ';
					}
				}
			}
			else {
				for($i = 1; $i <= $this->num_page; $i++) {
					if($this->active_page == $i) {
						$content .= ' <span class="current-page">'.$i.'</span> ';
					}
					else {
						$content .= ' <span><a href="'.$url.$i.'">'.$i.'</a></span> ';
					}
				}
			}
			$content .= ' ';
		}
		if($this->active_page < $this->num_page) {
			/* Weiter */
			$content .= ' <span class="next"><a href="'.$url.($this->active_page + 1).'">Weiter</a></span> ';
		}
		return $content;
	}
}


///////////////////////////////////////////////////////
// FTP Klasse
///////////////////////////////////////////////////////

/**
 * FTP Klasse
 */
class ftp {
	public $stream = false;

	/**
	 * Eine ftp Verbindung herstellen
	 */
	function __construct() {
		/* Verbindung herstellen */
		if (FTP_SERVER_USE_SSL) {
			$this->stream = ftp_ssl_connect(FTP_SERVER_HOST, FTP_SERVER_HOST_PORT);
		}
		
		if ($this->stream == false) {
			/* Unsichere Verbindung falls SSL nicht unterstuetzt */
			$this->stream = ftp_connect(FTP_SERVER_HOST, FTP_SERVER_HOST_PORT);
 		}

		if ($this->stream) {
			if (!ftp_login($this->stream, FTP_USER, FTP_USER_PASSWORD)) {
				die('Error: Could not login to the ftp server!');
			}
		}
		else {
			die('Error: Could not connect to the ftp server!');
		}
		
		/* In Startverzeichnis wechseln */
		$this->ChangeDir('/');
	}

	/**
	 * Gibt das aktuelle Verzeichnis aus (Debuggen)
	 */
	function CurrentDir() {
		return ftp_pwd($this->stream);
	}

	/**
	 * Wechselt das Verzeichnis auf dem FTP Server
	 */
	function ChangeDir($folder) {
		return ftp_chdir($this->stream, FTP_DIR.$folder);
	}
	
	/**
	 * Sicherheitspruefung des Verzeichnisses
	 */
	private function checkPath($file) {
		$file = str_replace('../', '', $file);
		$ftp_path = pathinfo($file);
		
		if ($ftp_path['dirname'] != '.') {
			if (!$this->ChangeDir($ftp_path['dirname']))
				return false;
		}
		
		return $ftp_path['basename'];
	}

	/**
	 * Neues Verzeichnis erstellen
	 */
	function mkdir($dir_name) {
		return ftp_mkdir($this->stream, $dir_name);
	}
	
	/**
	 * CHMOD Rechte einer Datei/Ordner wechseln
	 */
	function chmod($file_name, $chmod) {
		if (!($file_name = $this->checkPath($file_name)))
			return false;
		return ftp_chmod($this->stream, $chmod, $file_name);
	}

	/**
	 * Eine Datei hochladen
	 */
	function FilePut($remote_file, $local_file) {
		if (!($remote_file = $this->checkPath($remote_file)))
			return false;

		if (is_string($local_file))
			$bool = ftp_put($this->stream, $remote_file, $local_file, FTP_BINARY);
		else
			$bool = ftp_fput($this->stream, $remote_file, $local_file, FTP_BINARY);
		
		return $bool;
	}
	
	/**
	 * Eine Datei vom FTP Server Herunterladen und in eine bestehende Datei speichern.
	 */
	function FileRead($remote_file, $p_local_file) {
		if (!($remote_file = $this->checkPath($remote_file)))
			return false;
		return ftp_fget($this->stream, $p_local_file, $remote_file, FTP_BINARY);
	}

	/**
	 * Inhalt einer Datei auf dem FTP Servers einlesen.
	 */
	function FileContents($remote_file) {
		/* Temporaere Datei als Zwischenspeicher */
		$p_local_file = tmpfile();
		
		/* Config Datei herunterladen */
		if (!$this->FileRead($remote_file, $p_local_file))
			return false;
		
		/* Zeiger an Dateianfang */
		fseek($p_local_file, 0);
		
		/* Dateiinhalt einlesen */
		$str_content = fread($p_local_file, $this->fileSize($remote_file));
		
		/* Temporaere Datei wieder schliessen */
		fclose($p_local_file);
		
		/* Inhalt rueckgeben */
		return $str_content;
	}

	/**
	 * Datei oder Verzeichnis umbenennen
	 */
	function Rename($old_name, $new_name) {
		return ftp_rename($this->stream, $old_name, $new_name);
	}

	/**
	 * Loescht ein Verzeichnis
	 */
	function rmdir($folder) {
		/* Das Verzeichnis muss leer sein, damit es geloescht werden kann */
		/* Ins zu loeschende Verzeichnis wechseln */
		ftp_chdir($this->stream, $folder);
		$this->CleanFolder();
		return ftp_rmdir($this->stream, $folder);
	}

	/**
	 * Loescht eine einzelne Datei
	 */
	function Delete($datei) {
		if (!($datei = $this->checkPath($datei)))
			return false;
		return ftp_delete($this->stream, $datei);
	}

	/**
	 * Entleert ein Verzeichnis vollkommen
	 */
	private function CleanFolder() {
		/* Parameter -a damit auch dot-files geloescht werden */
		$list = ftp_nlist($this->stream, '.');
		if (is_array($list)) {
			for ($i=count($list)-1; $i >=0; $i--) {
				/* Ohne Zurueckverzeichnisse */
				if ($list[$i] != '.' && $list[$i] != '..') {
					if ($this->isDir($list[$i])) {
						/* Ordner */
						/* in Ordner wechseln */
						ftp_chdir($this->stream, $list[$i]);
						/* Rekursive Funktion */
						$this->CleanFolder();
						/* Geleerter Ordner loeschen */
						ftp_rmdir($this->stream, $list[$i]);
					}
					else {
						/* Datei */
						ftp_delete($this->stream, $list[$i]);
					}
				}
			}
		}	/* T_ELSE Folder is empty */
		/* Ein Ordner nach Oben */
		ftp_cdup($this->stream);
		/* Sicherheitskontrolle */
		if (mb_substr('/'.$this->CurrentDir(), 1, mb_strlen(FTP_DIR)) != FTP_DIR) die('Fatal ftp Class Error!');
	}
	
	/**
	 * Prueft, ob der angegebene Dateiname ein Verzeichnis ist.
	 */
	function isDir($dir) {
		$temp = $this->CurrentDir();

		if (@ftp_chdir($this->stream, $dir)) {
			ftp_chdir($this->stream, $temp);
			return true;
		}
		else {
			ftp_chdir($this->stream, $temp);
			return false;
		}
	}

	/**
	 * Verzeichnis oeffnen.
	 * @param $folder Absoluter Pfad des Ordners, der ausgelesen werden soll.
	 * @return Stream des geoeffneten Ordners, im Fehlerfall NULL.
	 */
	function openDir($folder) {
		$dir = new ftpOpenDir($this, FTP_DIR.$folder);
		if ($dir->state_construct)
			return $dir;
		
		/* Objekt wieder loeschen */
		unset($dir);
		return NULL;
	}
	
	/**
	 * Verzeichnis schliessen.
	 */
	function closeDir($dir) {
		if ($dir != NULL) {
			/* Objekt loeschen */
			unset($dir);
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Verzeichnisliste erstellen.
	 * @param $folder Orndernamen von dem Ausgegangen werden soll.
	 * @param $p_func_callback Funktionnamen, die fuer jeden Ordner aufgerufen wird.
	 * @note Aufbau der Parameter:
	 * 1. Ordnernamen.
	 * 2. Ebene, in der sich der Ordner befindet, falls der Ordner rekursiv ausgewertet wird.
	 * 3. Pfad zum Ordner, falls der Ordner rekursiv ausgewertet wird.
	 * Falls der Return-Wert false ist, werden die Unterordner des Verzeichnisses nicht mehr beruecksichtigt.
	 * @param $recursive Bei true werden Unterordner ebenfalls durchsucht.
	 */
	function folderListCallback($folder, $p_func_callback, $recursive) {
		/* Liste der Unterordner erstellen und ausfuehren */
		$list = new ftpFolderList($this);
		$list->buildList($folder, $p_func_callback, $recursive);
		
		/* Klasse wieder loeschen */
		unset($list);
		
		return true;
	}

	/**
	 * Prueft ob eine Datei existiert.
	 * @param $file_name Dateiname mit Phad vom Spammverzeichnis des FTP Servers aus.
	 */
	function fileExists($file_name) {
		if (!($file_name = $this->checkPath($file_name)))
			return false;
			
		if (ftp_size($this->stream, $file_name) >= 0)
			return TRUE;
		else
			return FALSE;
	}
	
	/**
	 * Prueft ob ein Verzeichnis existiert.
	 * @param $folder_name Verzeichnisnamen mit Phad vom Stammverzeichnis des FTP Servers aus.
	 */
	function folderExists($folder_name) {
		if (!($folder_name = $this->checkPath($folder_name)))
			return false;
		
		return $this->isDir($folder_name);
	}
	
	/**
	 * Liefert UNIX Timestamp der letzten Dateiaenderung.
	 * @param $data Dateinamen.
	 * @return UNIX Zeitstempel.
	 */
	function fileTime($file_name) {
		if (!($file_name = $this->checkPath($file_name)))
			return false;
			
		return ftp_mdtm($this->stream, $file_name);
	}
	
	/**
	 * Liefert die Groesse einer Datei.
	 * @param $data Dateinamen.
	 * @return Dateigroesse in Bytes.
	 */
	function fileSize($file_name) {
		if (!($file_name = $this->checkPath($file_name)))
			return false;
			
		return ftp_size($this->stream, $file_name);
	}
	
	/**
	 * Konfiguration eines Verzeichnisses lesen.
	 * @param[in]	Verzeichnis.
	 * @return		Assoziatives Array mit den Verzeichniskonfigurationen.
	 */
	function readFolderConfig($folder) {
		$retval = NULL;
	
		/* Pruefen ob Verzeichnis existiert und eine Konfigurationsdatei vorhanden ist */
		if ($this->folderExists($folder) && $this->fileExists($folder.'/'.FILE_SYSTEM_CONFIGFILE)) {
			/* Config Datei einlesen */
			$s_config = $this->FileContents($folder.'/'.FILE_SYSTEM_CONFIGFILE);
			$retval = json_decode($s_config, true);
		}
		
		return $retval;
	}
	
	/**
	 * Schreiben der Konfigurationen eines Verzeichnisses.
	 * @param[in]	$folder Verzeichnis.
	 * @param[in]	$config Assoziatives Array mit den Konfigurationen.
	 * @retval		TRUE falls die Konfigurationsdatei erfolgreich geschrieben wurde.
	 */
	function writeFolderConfig($folder, $config) {
		$retval = false;
	
		/* Pruefen ob Verzeichnis existiert */
		if ($this->folderExists($folder)) {
			/* JSON generieren */
			$s_config = json_encode($config);
			
			/* Konfigurationsdatei erzeugen */
			if ($ftemp = tmpfile()) {
				/* Verzeichnisschutz erstellen */
				fwrite($ftemp, $s_config);
				fseek($ftemp, 0);
				/* Hochladen */
				if ($this->FilePut($folder.'/'.FILE_SYSTEM_CONFIGFILE, $ftemp)) {
					$retval = true;
				}
				fclose($ftemp);
			}
		}
		
		return $retval;
	}

	/**
	 * Geoeffnete FTP Verbindung schliessen
	 */
	function close() {
		/* FTP Verbindung schliessen */
		ftp_close($this->stream);
	}
}

/**
 * Generiert eine Liste mit allen Unterordnert.
 */
class ftpFolderList {
	private $ftp;
	
	private $level=1;
	private $p_func_callback, $recursive;
	
	/**
	 * Generiert eine Liste mit allen Unterordnert.
	 * @param $ftp Zeiger auf das Elternelement mit der geoeffneten FZTP Verbindung.
	 */
	function __construct($ftp) {
		$this->ftp = $ftp;
	}
	
	/**
	 * Erstellt die komplette Lister der Unterverzeichnisse.
	 * @param $folder Orndernamen von dem Ausgegangen werden soll.
	 * @param $function_pointer Funktionnamen, die fuer jeden Ordner aufgerufen wird.
	 * @note Aufbau der Parameter:
	 * 1. Ordnernamen.
	 * 2. Ebene, in der sich der Ordner befindet, falls der Ordner rekursiv ausgewertet wird.
	 * 3. Pfad zum Ordner, falls der Ordner rekursiv ausgewertet wird.
	 * Falls der Return-Wert false ist, werden die Unterordner des Verzeichnisses nicht mehr beruecksichtigt.
	 * @param $recursive Bei true werden Unterordner ebenfalls durchsucht.
	 */
	function buildList($folder, $p_func_callback, $recursive) {
		$this->p_func_callback = $p_func_callback;
		$this->recursive = $recursive;
		
		/* Verzeichnisnamen speichern mit Abschliessendem / */
		if (!substr($folder, -1, 1) == '/')
			$folder = $folder.'/';
		
		/* Erstellung starten */
		$this->readSubFolders($folder);
	}
	
	/**
	 * Sucht alle Verzeichnise in einem Ordner und ruft die Benutzerfunktion auf.
	 * @param $current_folder Aktueller Ordner.
	 * @note Rekursive Funktion.
	 */
	private function readSubFolders($current_folder) {
		/* Ordner einlesen */
		if($folder_pointer = $this->ftp->openDir($current_folder)) {
			/* Funktionspointer funktionieren nur als Variablen */
			$p_folder = $this->p_func_callback;
			
			/* Alle Verzeichnisse und Dateine des Ordners einzeln verarbeiten */
			while($file = $folder_pointer->readDir()) {
				/* Es intereessieren nur die gefundenen Ordner */
				if ($folder_pointer->isDir($file)) {
					/* Ordner gefunden */
					if ($this->recursive) {
						/* Benutzerfunktion ausfuehren */
						if ($p_folder($file, $this->level, $current_folder)) {
							/* Unterordner durchsuchen */
							$this->level++;
							$this->readSubFolders($current_folder.$file.'/');
							$this->level--;
						}
					}
					else {
						/* Benutzerfunktion ausfuehren */
						$function_pointer($file);
					}		
				}
			}
			/* Ordner schliessen */
			$this->ftp->closeDir($folder_pointer);
		}	
	}
}


/**
 * Oeffnen eines Ordners und alle Dateien und Verzeichnisse auslesen.
 */
class ftpOpenDir {
	private $ftp;
	private $data_list_ctr=-1;
	private $raw_list;
	private $data_list;
	private $dir_path;
	
	public $state_construct=false;
	
	/**
	 * Verzeichnis oeffnen und alle Dateien und Verzeichnisse einlesen.
	 * @param $ftp Zeiger auf das Elternelement mit der geoeffneten FZTP Verbindung.
	 * @param $folder Phad zum Verzeichnis, das geoeffnet werden soll.
	 */
	function __construct($ftp, $folder) {
		$this->ftp = $ftp;
		
		/* Verzeichnisnamen speichern mit Abschliessendem / */
		if (substr($folder, -1, 1) == '/')
			$this->dir_path = $folder;
		else
			$this->dir_path = $folder.'/';

		/* Liste aller Dateinen im Verzeichnis erstellen */
		$this->raw_list = ftp_rawlist($this->ftp->stream, substr($this->dir_path, 0, -1));
		
		$this->data_list = $this->parseRawList($this->raw_list);

		/* Klasse erfolgreich erstellt und Daten gelesen */
		$this->state_construct = true;
	}
	
	/**
	 * Parser der RAW Liste vom FTP Server.
	 * @param $rawList String Array mit den Rohdaten.
	 * @param $Win Serverbetriebssystem
	 */
	private function parseRawList($rawList, $Win=false) {
		$Output = array();
		$i = 0;
		if ($Win) {
			foreach ($rawList as $Current) {
				ereg('([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|) +(.+)', $Current, $Split);
				if (is_array($Split) && substr($Split[8], 0, 1) != '.') {
					if ($Split[3] < 70) {
						$Split[3] += 2000;
					}
					else {
						$Split[3] += 1900;
					}
					$Output[$i]['isdir']     = ($Split[7] == '') ? 1 : 0;
					$Output[$i]['size']      = $Split[7];
					$Output[$i]['month']     = $Split[1];
					$Output[$i]['day']       = $Split[2];
					$Output[$i]['time/year'] = $Split[3];
					$Output[$i]['name']      = $Split[8];
					$i++;
				}
			}
			return !empty($Output) ? $Output : false;
		}
		else {
			foreach ($rawList as $Current) {
				$Split = preg_split('[ ]', $Current, 9, PREG_SPLIT_NO_EMPTY);
 				if ($Split[0] != 'total' && substr($Split[8], 0, 1) != '.') {
					$Output[$i]['isdir']     = ($Split[0] {0} === 'd') ? 1 : 0;
					$Output[$i]['perms']     = $Split[0];
					$Output[$i]['number']    = $Split[1];
					$Output[$i]['owner']     = $Split[2];
					$Output[$i]['group']     = $Split[3];
					$Output[$i]['size']      = $Split[4];
					$Output[$i]['month']     = $Split[5];
					$Output[$i]['day']       = $Split[6];
					$Output[$i]['time/year'] = $Split[7];
					$Output[$i]['name']      = $Split[8];
					$i++;
				}
			}
			return !empty($Output) ? $Output : FALSE;
		}
	}
	
	/**
	 * Gibt den Dateinamen der naechsten Datei des Verzeichnisses zurueck.
	 * @return Dateinamen der naechsten Datei, im Fehlerfall FASE.
	 */
	function readDir() {
		/* Zaehler erhoehen */
		$this->data_list_ctr++;
		/* Zaehler auswerten */
		if (is_array($this->data_list) && sizeof($this->data_list) > $this->data_list_ctr) {
			//return basename($this->data_list[$data_id]);
			return $this->data_list[$this->data_list_ctr]['name'];
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Sortierung der Verzeichnisse und Dateien
	 */
	function sortList($sort) {
		if (is_array($this->data_list)) {
			/* Relevante Sparten generieren */
			$isdir = array();
			$name = array();
			foreach ($this->data_list as $element) {
				$isdir[] = $element['isdir'];
				$name[] = $element['name'];
			}
			
			return array_multisort($isdir, SORT_DESC, $name, SORT_ASC, $this->data_list);
		}
		
		/* Keine Liste: Ordner ist leer und muss nicht sortiert werden. */
		return true;
	}
	
	/**
	 * Liefert UNIX Timestamp der letzten Dateiaenderung.
	 * @param $data Dateinamen.
	 * @return UNIX Zeitstempel.
	 */
	function fileTime($format='unix') {
		$str_months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		
		$month = array_keys($str_months, $this->data_list[$this->data_list_ctr]['month']);
		$month = $month[0];
		
		if ($format == 'str') {
			/* Ausgabe als String */
			return $this->data_list[$this->data_list_ctr]['day'].' '.$this->data_list[$this->data_list_ctr]['month']
					.' '.$this->data_list[$this->data_list_ctr]['time/year'];
		}
		else {
			/* Ausgabe im UNIX Format */
			$date = explode(':', $this->data_list[$this->data_list_ctr]['time/year']);

			if (sizeof($date) == 2) {
				return mktime($date[0], $date[1], 0, $month,
						$this->data_list[$this->data_list_ctr]['day'], date('Y', TIME_STAMP));
			}
			else {
				return mktime(0, 0, 0, $month, $this->data_list[$this->data_list_ctr]['day'], $date[0]);
			}
		}
	}
	
	/**
	 * Prueft, ob der angegebene Dateiname ein Verzeichnis ist.
	 * @param $data Dateinamen.
	 * @return TRUE fuer Verzeichnisse, bei Dateien FALSE.
	 */
	function isDir() {
		return ($this->data_list[$this->data_list_ctr]['isdir'] == 1);
	}
	
	/**
	 * Liefert die Groesse einer Datei.
	 * @param $data Dateinamen.
	 * @return Dateigroesse in Bytes.
	 */
	function fileSize() {
		return $this->data_list[$this->data_list_ctr]['size'];
	}
}

///////////////////////////////////////////////////////

?>