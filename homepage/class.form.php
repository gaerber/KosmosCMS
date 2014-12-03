<?php

 /*
 =====================================================
 Name ........: Klasse um Formulare zu verwalten
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: class.form.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 29.04.2011 | Programm erstellt
 |1.0.1   | 04.07.2011 | Konzeptaenderung Maskierung
 |1.0.2   | 06.09.2011 | ACP Standard
 |1.0.3   | 11.09.2012 | form-after hinzugefuegt
 |1.1     | 23.10.2012 | SPAM-Protection hinzu
 |1.1.1   | 20.11.2012 | Statistik: SPAM-Versuche
 |1.2     | 30.03.2013 | Javascript Unterstuetzung
 |1.3     | 29.04.2013 | Bilder hinzugefuegt
 |1.3.1   | 19.07.2013 | Bugfix Checkbox: Precheck
 |1.3.2   | 06.02.2014 | Bugfix Input hidden (HTML5)
 -----------------------------------------------------
 Beschreibung :
 Erstellen, verwalten und pruefen von Formularen.

 (c) by Kevin Gerber
 =====================================================
 */

/**
 * Generieren und auswerten von Formularen
 */
class formWizard {
	private $form_name;
	private $form_action;
	private $form_method;
	private $form_class;
	private $form_id;

	private $form_checked = false;
	private $form_checked_status;
	private $form_empty_message="";
	private $form_use_files = false;

	/** Beinhaltet alle Formularelemente */
	private $elements = array();
	private $element_ctr = 0;
	private $id_ctr = 0;

	/**
	 * Initialisierungsfunktion speichert die wichtigsten Formulareigenschaften ab.
	 * @param $name ist der Formularname.
	 * @param $action URL zum Script.
	 * @param $methode ist post oder get.
	 * @param $class ist der CSS Klasse des Formulars.
	 */
	public function __construct($name, $action, $method='post', $class=NULL) {
		$this->form_name = $name;
		$this->form_action = $action;
		$this->form_method = $method;
		$this->form_class = $class;

		$this->addElement("hidden", "__form_submit_checker", NULL, "1");
	}

	/**
	 * Array mit allen Elementtypen und der dazugehoerigen Klasse.
	 * @return Assoziatives Array mit den Elementtypen.
	 */
	public function getElementClass() {
		$elementTypes = array(
				'checkbox' => 'formWizardChoice',
				'text' => 'formWizardInput',
				'password' => 'formWizardInput',
				'hidden' => 'formWizardInput',
				'textarea' => 'formWizardTextarea',
				'select' => 'formWizardDropdown',
				'file' => 'formWizardFile',
				'radio' => 'formWizardChoice',
				'submit' => 'formWizardInput',
				'reset' => 'formWizardInput',
				'html' => 'formWizardHtml',
				'image' => 'formWizardImage',
				'dropfiles' => 'formWizardFileDrop'
		);

		return $elementTypes;
	}

	/**
	 * Gibt den Formularnamen zurueck.
	 * Benoetigt fuer das Generieren der Feldnamen.
	 */
	public function getFormName() {
		return $this->form_name;
	}
	public function getFormClass() {
		return $this->form_class;
	}
	public function setFormUseFiles($form_use_files) {
		$this->form_use_files = $form_use_files;
	}
	public function setFormId($id) {
		$this->form_id = $id;
	}
	public function getFormUseFiles() {
		return $this->form_use_files;
	}

	/**
	 * Prueft ob ein Element mit gleichem Namen existiert und gibt im Falle
	 * dessen ID zurueck, sonst die naechstfolgende neue ID.
	 * @param $name ist der Name des neuen Elements.
	 * @return ID des neuen Elements.
	 */
	private function checkElementID($name) {
		if ($name == NULL)
			return $this->element_ctr;

		foreach ($this->elements as $element) {
			if ($element->getName() == $name) {
				return $element->getIdName();
			}
		}
		return $this->element_ctr++;
	}

	/**
	 * Hinzufuegen eines neuen Elements.
	 * @param $element ist der Type des neuen Elements. Definiert in
	 * getElementClass().
	 * @param $name ist der Name des neuen Elements.
	 * @param $label ist der Titel des Elements. Wird dem Benutzer angezeigt.
	 * @param $value ist der vordefinierte Wert des neuen Elements.
	 * @param $obligation ist true, wenn es sich um ein Pflichtfeld handelt.
	 * @return Objekt des neugenerierten Elements.
	 */
	public function addElement($element, $name, $label=NULL, $value=NULL,
			$obligation=false) {

		/* Alle ElementTypen (Klassen) laden */
		$elementTypes = $this->getElementClass();

		/* Neues Objekt des Elements erstellen */
		$object = new $elementTypes[$element]($this, $element,
				$this->id_ctr, $name, $this->checkElementID($name), $label,
				$value, $obligation);

		/* Element abspeichern */
		$this->elements[] = $object;

		/* Zaehler inkrementieren */
		$this->id_ctr++;

		/* Rueckgabe der Element Klasse */
		return $object;
	}

	/**
	 * Fehlermeldung bei leeren Formularen
	 */
	public function setEmptyMessage($string) {
		$this->form_empty_message = $string;
	}
	public function addEmptyMessage($string) {
		$this->form_empty_message .= $string;
	}
	public function getEmptyMessage() {
		return $this->form_empty_message;
	}

	/**
	 * Prueft ob das Formular vom Benutzer abgesendet wurde.
	 * @return true, wenn es abgesendet wurde, sonst false.
	 */
	public function checkSubmit() {
		/* Das hidden-Element (__form_submit_checker) mit ID=0 muss existieren */
		if (isset($_REQUEST[$this->getFormName()][0]) && $_REQUEST[$this->getFormName()][0] == 1)
			return true;
		else
			return false;
	}

	/**
	 * Ueberprueft die Eingaben des Benutzers.
	 * @return Bei vollstaendeiger und korrekter Eingabe true, sonst false.
	 */
	public function checkForm() {
	 	if (!$this->checkSubmit()) {
	 		/* Nichts zum pruefen */
			return false;
	 	}
		if ($this->form_checked) {
	 		/* Wurde bereits geprueft */
	 		return $this->form_checked_status;
 		}

	 	$this->form_checked = true;

	 	$check = true;

		foreach ($this->elements as $element) {
	 		if (!$element->checkValue()) {
	 			$check = false;
	 		}
	 	}

	 	$this->form_checked_status = $check;
	 	return $check;
	}

	/**
	 * Generiert den HTML-Code des Formulars.
	 * @return HTML-Code.
	 */
	public function getForm() {
		/* Fehlerausgabe */
		$html = $this->form_empty_message;

		/* Wenn das Formular vom Benutzer abgeschickt wurde, muss es vorher geprueft
		werden */
		$this->checkForm();

		$html .= "<form name=\"".$this->form_name."\" action=\"".$this->form_action
				."\" method=\"".$this->form_method."\" class=\"".$this->form_class."\"";
		if ($this->getFormUseFiles())
			$html .= " enctype=\"multipart/form-data\"";
		if ($this->form_id != '')
			$html .= ' id="'.$this->form_id.'"';
		$html .= " accept-charset=\"utf-8\">\r\n  <ol>\r\n";

		foreach ($this->elements as $element) {
	 		$html .= $element->renderHtml();
	 	}

	 	$html .= "  </ol>\r\n";
	 	/* Clear Formular */
		$html .= "  <p class=\"form-after\"></p>\r\n";
	 	$html .= "</form>";

	 	return $html;
	}

	public function debugPrint() {
		print_r($this->form_empty_message);
		print_r($this->elements);
	}
}

/**
 * Beinhaltet die Funktionen um die gemeinsamen Parameter aller Elemente aendern.
 */
class formWizardElement {
	/* Parameter aller Elemente */
	protected $class_form;
	protected $type;
	protected $id;
	protected $name;
	protected $name_id;
	protected $value;
	protected $label;
	protected $obligation;

	protected $css_class;
	protected $css_class_field;
	protected $description;
	
	protected $javascript;
	
	protected $readonly = false;

	protected $error_empty=false;

	public function __construct($class_form, $type, $id, $name, $name_id, $label,
			$value, $obligation) {
		$this->class_form = $class_form;
		$this->type = $type;
		$this->id = $id;
		$this->name = $name;
		$this->name_id = $name_id;
		$this->value = $value;
		$this->label = $label;
		$this->obligation = $obligation;

		if ($type == "file")
			$class_form->setFormUseFiles(true);
	}

	public function getFormName() {
		return $this->class_form->getFormName();
	}
	/** formWizard::checkElementID() */
	public function getIdName() {
		return $this->name_id;
	}
	/** formWizard::checkElementID() */
	public function getName() {
		return $this->name;
	}
	public function getValue() {
		return $this->value;
	}
	public function getRequest() {
		if ($this->type == "file")
			return $_FILES[$this->class_form->getFormName()."_".$this->name_id];
		else
			if (isset($_REQUEST[$this->class_form->getFormName()][$this->name_id]))
				return $_REQUEST[$this->class_form->getFormName()][$this->name_id];
			else
				return false;
	}

	public function setCssClass($css_class) {
		if ($this->css_class != '')
			$this->css_class .= ' '.$css_class;
		else
			$this->css_class = $css_class;
	}
	public function setCssClassField($css_class) {
		$this->css_class_field = $css_class;
	}
	public function setJavaScript($javascript) {
		$this->javascript = $javascript;
	}
	public function setValue($value) {
		$this->value = $value;
	}
	public function setLabel($label) {
		$this->label = $label;
	}
	public function setDescription($description) {
		$this->description = $description;
	}
	public function setError($error) {
		$this->error_empty = $error;
	}
	public function setReadonly($readonly) {
		$this->readonly = $readonly;
	}

	public function getCssClassField() {
		$cssClasses = array(
				'checkbox' => NULL,
				'text' => 'input_text',
				'password' => 'input_text',
				'hidden' => NULL,
				'textarea' => NULL,
				'select' => NULL,
				'file' => 'input_file',
				'radio' => NULL,
				'submit' => 'input_btn',
				'reset' => 'input_btn'
				);
		if ($cssClasses[$this->type] && $this->css_class_field)
			return $this->css_class_field." ".$cssClasses[$this->type];
		if ($this->css_class_field)
			return $this->css_class_field;
		return $cssClasses[$this->type];
	}

	/**
	 * Generiert den HTML-Code eines Elements.
	 * @param $html_field ist der HTML-Code des Formularfeldes.
	 * @return HTML-Code eines Elements.
	 */
	public function renderHtmlElement($html_field, $befor=NULL, $after=NULL) {
		$css_class = $this->css_class;
        if ($this->type == 'hidden') {
			if ($css_class != '')
				$css_class .= ' input-hidden';
			else
				$css_class = 'input-hidden';
        }
		if ($this->obligation)
			if ($css_class != "")
				$css_class .= " obligation";
			else
				$css_class = "obligation";
		if ($this->error_empty)
			if ($css_class != "")
				$css_class .= " empty";
			else
				$css_class = "empty";

		if ($this->javascript != '') {
			if ($this->type == 'textarea')
				$html_field = str_replace('<textarea', '<textarea '.$this->javascript.' ', $html_field);
			elseif ($this->type == 'select')
				$html_field = str_replace('<select', '<select '.$this->javascript.' ', $html_field);
			else
				$html_field = str_replace('/>', $this->javascript.' />', $html_field);
		}

		/* Listenzeile <li> */
		$html = $befor."    <li";
		if ($css_class != "")
			$html .= " class=\"".$css_class."\"";
		/* Label */
		$html .= "><label class=\"line";
		if (!$this->label)
			$html .= "_hide";
		$html .= "\"";
		if ((!(isset($this->sublabel) && $this->sublabel)) && $this->label)
			$html .= " for=\"".$this->getFormName()."_".$this->id."\"";
		if ($this->description)
			$html .= " onmouseover=\"Tip('".$this->description."')\" onmouseout=\"UnTip()\"";
		$html .= ">".$this->label."</label>";
		/* HTML Feld */
		if ($this->type == 'checkbox' || $this->type == 'radio')
			$html .= "<span class=\"input_box\">".$html_field."</span>";
		else
			$html .= $html_field;
		/* Sub Label von Checkboxen und Radio-Buttons */
		if (isset($this->sublabel) && $this->sublabel) {
			$html .= "<label for=\"".$this->getFormName()."_".$this->id."\"";
			if ($this->subdescription)
				$html .= " onmouseover=\"Tip('".$this->subdescription."')\" onmouseout=\"UnTip()\"";
			$html .= " class=\"input_box\">".$this->sublabel."</label>";
		}
		/* Ende der Listenzeile <li> */
		$html .= "</li>\r\n".$after;

		/* Rueckgabe */
		return $html;
	}
}

/**
 * Input-Elementen.
 */
class formWizardInput extends formWizardElement {
	/* Parameter die nur Input-Elemente haben */
	protected $customValidation = "";
	protected $customValidationMsg = "";

	/* Funktionen um die speziellen Parameter einzustellen */
	public function setCustomValidation($custom_pattern, $custom_error_message) {
		/* Spezielle Vorlagen fuer Email und Website */
		if ($custom_pattern == "email")
			$this->customValidation = "/^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$/i";
		else if ($custom_pattern == "website")
			$this->customValidation = "/^(http|https|ftp|ftps)\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+/i";
		else
			$this->customValidation = $custom_pattern;
		
		$this->customValidationMsg = $custom_error_message;
	}

	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		/* Inhalt updaten */
		if (isset($_REQUEST[$this->getFormName()][$this->name_id])) {
			if ($this->type != "password")
				$this->setValue(StdString($_REQUEST[$this->getFormName()][$this->name_id]));
			else
				$this->setValue($_REQUEST[$this->getFormName()][$this->name_id]);
		}

		if ($this->obligation && $this->value == "") {
			/*$this->class_form->addEmptyMessage($this->label);*/
			$this->error_empty = true;
			return false;
		}
		if ($this->value != "" && $this->customValidation != ""
				&& !preg_match($this->customValidation, $this->value)) {
			$this->error_empty = true;
			$this->class_form->addEmptyMessage($this->customValidationMsg);
			return false;
		}
		return true;
	}

	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$html = "<input type=\"".$this->type."\" name=\"".$this->getFormName()."[".$this->name_id."]\""
				." id=\"".$this->getFormName()."_".$this->id."\"";
		/* Wert */
		if ($this->type != "password")
			$html .= " value=\"".$this->value."\"";
		/* CSS Klasse */
		if ($temp_css_class = $this->getCssClassField()) {		/* NUR 1x = */
			/* Klasse definiert ($temp_css_class) */
			$html .= " class=\"".$temp_css_class."\"";
		}
		if ($this->readonly) {
			$html .= " disabled=\"disabled\"";
		}
		/* ACP Spezialfunktionen */
		if ($this->class_form->getFormClass() == "form_acp_standard") {
			/* Relogin, Button bei Dateiupload */
			if ($this->type == "submit") {
				if ($this->class_form->getFormUseFiles())
					$html .= " onclick=\"fileuploadStart(); return checkRelogin();\"";
				else
					$html .= " onclick=\"return checkRelogin();\"";
			}
		}
		/* Feldende */
		$html .=  " />";

		/*if ($this->type == 'hidden')
			return $html;*/

		return $this->renderHtmlElement($html);
	}
}

/**
 * File-Elementen.
 */
class formWizardFile extends formWizardElement {
	private $uploaded_file_data;
	private $accept;

	public function getValue() {
		return $this->uploaded_file_data;
	}
	
	public function setAcceptTypes($types) {
		$this->accept = $types;
	}

	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		/* Inhalt updaten */
		if (isset($_FILES[$this->getFormName()."_".$this->name_id])) {
			$this->uploaded_file_data = $_FILES[$this->getFormName()."_".$this->name_id];
			/* Fehlerpruefung */
			if ($this->uploaded_file_data['error']) {
				switch ($this->uploaded_file_data['error']) {
					case 1:
						$this->class_form->addEmptyMessage(ActionReport(REPORT_ERROR, "Fehler",
								"Die hochgeladene Datei überschreitet die in der Anweisung upload_max_filesize in php.ini festgelegte Grösse."));
						break;
					case 2:
						$this->class_form->addEmptyMessage(ActionReport(REPORT_ERROR, "Fehler",
								" Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigrösse."));
						break;
					case 3:
						$this->class_form->addEmptyMessage(ActionReport(REPORT_ERROR, "Fehler",
								"Die Datei wurde nur teilweise hochgeladen."));
						break;
					case 4:
						$this->class_form->addEmptyMessage(ActionReport(REPORT_ERROR, "Fehler",
								"Es wurde keine Datei hochgeladen."));
						break;
				}
				$this->uploaded_file_data = NULL;
				$this->error_empty = true;
				return false;
			}
		}
		else {
			/* Keine Datei uebertragen */
			if ($this->obligation) {
				$this->error_empty = true;
				return false;
			}
		}

		return true;
	}

	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$html = "<input type=\"file\" name=\"".$this->getFormName()."_".$this->name_id."\""
				." id=\"".$this->getFormName()."_".$this->id."\"";
		/* CSS Klasse */
		if ($temp_css_class = $this->getCssClassField()) {		/* NUR 1x = */
			/* Klasse definiert ($temp_css_class) */
			$html .= " class=\"".$temp_css_class."\"";
		}
		if ($this->accept)
			$html .= ' accept="'.$this->accept.'"';
		/* Feldende */
		$html .=  " />";

		return $this->renderHtmlElement($html);
	}
}

/**
 * Textarea-Elementen.
 */
class formWizardTextarea extends formWizardElement {
	/* Parameter die nur Textarea-Elemente haben */
	private $rows=5;
	private $cols=50;
	private $big_area = false;
	private $wysiwyg = false;

	/* Funktionen um die speziellen Parameter einzustellen */
	public function setRowsCols($rows, $cols) {
		if (is_int($rows))
			$this->rows = $rows;
		if (is_int($cols))
			$this->cols = $cols;
	}
	public function setWysiwyg($wysiwyg) {
		$this->wysiwyg = $wysiwyg;
	}
	public function setBigArea($big_area) {
		$this->big_area = $big_area;
	}

	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		/* Inhalt updaten */
		if (isset($_REQUEST[$this->getFormName()][$this->name_id])) {
			if (!$this->wysiwyg)
				$this->setValue(StdString($_REQUEST[$this->getFormName()][$this->name_id]));
			else
				$this->setValue($_REQUEST[$this->getFormName()][$this->name_id]);
		}

		if ($this->obligation && $this->value == "") {
			//$this->class_form->addEmptyMessage($this->label);
			$this->error_empty = true;
			return false;
		}

		return true;
	}

	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$html = "<textarea name=\"".$this->getFormName()."[".$this->name_id."]\""
				." id=\"".$this->getFormName()."_".$this->id."\""
				." rows=\"".$this->rows."\" cols=\"".$this->cols."\"";
		if ($this->css_class_field)
			if ($this->wysiwyg)
				$html .= " class=\"wysiwyg ".$this->css_class_field."\"";
			else
				$html .= " class=\"".$this->css_class_field."\"";
		else
			if ($this->wysiwyg)
				$html .= " class=\"wymeditor\"";
		$html .= ">".$this->value."</textarea>";

		if ($this->big_area || $this->wysiwyg)
			return $this->renderHtmlElement("<div>&nbsp;</div>", NULL, "</ol><p class=\"form-after\"></p>".$html."<ol>");
		else
			return $this->renderHtmlElement($html);
	}
}

/**
 * Auswahl-Elemente (checkbox und radio).
 */
class formWizardChoice extends formWizardElement {
	/* Parameter die nur Auswahl-Elemente haben */
	private $checked=false;
	protected $sublabel;
	protected $subdescription;

	/* Funktionen um die speziellen Parameter einzustellen */
	public function setChecked($checked) {
		$this->checked = $checked;
	}
	public function getValue() {
		//return (int)$this->checked;
		if ($this->checked)
			return $this->value;
		else
			return 0;
	}
	public function getFieldValue() {
		return $this->value;
	}
	public function setSubLabel($sub_label) {
		$this->sublabel = $sub_label;
	}
	public function setSubDescription($sub_description) {
		$this->subdescription = $sub_description;
	}

	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		/* Inhalt aktualisieren (ohne Funktion da hier anders) */
		$this->checked = false;
		
		if (isset($_REQUEST[$this->getFormName()][$this->name_id])) {
			if (is_array($_REQUEST[$this->getFormName()][$this->name_id])) {
				if (in_array($this->value, $_REQUEST[$this->getFormName()][$this->name_id]))
					$this->checked = true;
			}
			else if ($_REQUEST[$this->getFormName()][$this->name_id] == $this->value) {
				$this->checked = true;
			}
		}

		if ($this->obligation && !$this->checked) {
			$this->class_form->addEmptyMessage($this->label);
			return false;
		}

		return true;
	}

	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$html = "<input type=\"".$this->type."\" name=\"".$this->getFormName()."[".$this->name_id."]";
		if ($this->type == 'checkbox')
			$html .= "[]";
		$html .= "\" id=\"".$this->getFormName()."_".$this->id."\" value=\"".$this->value."\"";
		if ($this->checked) {
			$html .= " checked=\"checked\"";
		}
		if ($this->readonly) {
			$html .= " disabled=\"disabled\"";
		}
		if ($this->css_class_field)
			$html .= " class=\"".$this->css_class_field."\"";
		$html .= " />";

		return $this->renderHtmlElement($html);
	}
}


/**
 * Dropdown Menues.
 */
class formWizardDropdown extends formWizardElement {
	/* Parameter die nur Dropdown Menues haben */
	private $use_multiple=false;
	private $size=NULL;

	/* Array mit allen Optionen */
	private $options = array();

	/* Funktionen um die speziellen Parameter einzustellen */
	public function setMultiple($multiple) {
		$this->use_multiple = $multiple;
	}
	public function setSize($size) {
		if (is_int($size))
			$this->size = $size;
	}
	/**
	 * Einen neue Option hinzufuegen.
	 * @param $label ist der Option Titel der angezeigt wird.
	 * @param $value ist der Wert.
	 * @param $selected ist true, wenn die Option vorausgewaehlt sein soll.
	 */
	public function addOption($label, $value, $selected=false) {
		$this->options[] = array('value' => $value, 'label' => $label,
				'selected' => $selected);
	}

	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		/* Inhalt aktualisieren */
		if (isset($_REQUEST[$this->getFormName()][$this->name_id])) {
			/* Eingabe anspeichern */
			$this->value = $_REQUEST[$this->getFormName()][$this->name_id];
			/* Jede Option durchgehen */
			for ($i = sizeof($this->options) - 1; $i >= 0; $i--) {
				if ($this->use_multiple) {
					if (in_array($this->options[$i]['value'], $this->value)) {
						$this->options[$i]['selected'] = true;
					}
				}
				else if ($this->value == $this->options[$i]['value']) {
					$this->options[$i]['selected'] = true;
				}
			}
		}

		if ($this->obligation
				&& (($this->use_multiple && !sizeof($this->value))
				|| (!$this->use_multiple && ($this->value == $this->options[0]['value'])))) {
			//$this->class_form->addEmptyMessage($this->label);
			$this->error_empty = true;
			return false;
		}

		return true;
	}

	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$html_options = "";

		/* Code der Optionen erzeugen */
		foreach ($this->options as $option) {
			$html_options .= "  <option value=\"".$option['value']."\"";
			if ($option['selected'])
				$html_options .= " selected=\"selected\"";
			$html_options .= ">".$option['label']."</option>\r\n";
		}

		$html = "<select name=\"".$this->getFormName()."[".$this->name_id."]";
		if ($this->use_multiple)
			$html .= "[]\" multiple=\"multiple\"";
		else
			$html .= "\"";
		if ($this->size)
			$html .= " size=\"".$this->size."\"";
		$html .= " id=\"".$this->getFormName()."_".$this->id."\"";
		if ($this->css_class_field)
			$html .= " class=\"".$this->css_class_field."\"";
		$html .= ">\r\n".$html_options."</select>";

		return $this->renderHtmlElement($html);
	}
}

/**
 * HTML Objekte in das Formular einfuegen.
 */
class formWizardHtml extends formWizardElement {
	private $html_code="";

	public function insertHtmlCode($html_code) {
		$this->html_code .= $html_code;
	}
	public function setHtmlCode($html_code) {
		$this->html_code = $html_code;
	}

	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		return true;
	}
	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		return $this->html_code;
	}
}

/**
 * Hochladen von Dateien mit dem neuen Drag and Drop
 */
class formWizardFileDrop extends formWizardFile {
	/**
	 * Konstruktor muss zusätzlich ein normales File Element erstellen
	 */
	public function __construct($class_form, $type, $id, $name, $name_id, $label,
			$value, $obligation) {
		$class_form->setFormId('dropzone');
		formWizardFile::__construct($class_form, 'file', $id, $name, $name_id, $label, $value, $obligation);
	}
	
	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$show_new_form = 1;
		if (isset($_REQUEST['showNewForm']) && $_REQUEST['showNewForm'] == 0) {
			$show_new_form = 0;
		}
		$html = '    <li class="input-hidden"><label class="line_hide"></label><input type="hidden" name="showNewForm" id="showNewForm" value="'.$show_new_form.'" /></li>';
		$html .= '    <li id="drop-files" class="'.$this->css_class.'" ondragover="return false;">
	  <p>Alte Version</p>
	  Drop Images Here
    </li>';
    	$html .= formWizardFile::renderHtml();
    	return $html;
	}
	
}

/**
 * Bilder aus dem Dateisystem
 */
class formWizardImage extends formWizardElement {
	private $image_folder;
	private $image_default;
	private $image_size = array('height' => 0, 'width' => 0);
	
	private $new_image;
	
	/**
	 * Einstellungen des Bildes
	 */
	public function imageSettings($folder, $height, $width) {
		$this->image_folder = $folder;
		$this->image_size['height'] = $height;
		$this->image_size['width'] = $width;
	}
	
	/** 
	 * Default Bild
	 */
	public function imageDefault($def) {
		$this->image_default = $def;
	}
	
	/**
	 * Pruefen ob das Bild Vorhanden ist
	 */
	private function imageExists() {
		/* FTP Verbindung erstellen */
		$ftp = new ftp();
		$return = $ftp->fileExists($this->image_folder.$this->value);
		$ftp->close();
		
		return $return;
	}
	
	/**
	 * Ueberpruefung der Eingabe
	 */
	public function checkValue() {
		if (isset($_REQUEST[$this->getFormName()][$this->name_id])) {
			$this->value = StdString($_REQUEST[$this->getFormName()][$this->name_id]);
		}
		
		return true;
	}
	
	/**
	 * Generiert den HTML-Code des Eingabefeldes
	 */
	public function renderHtml() {
		$image_exists = $this->imageExists();
		
		$html = '<div class="images"><div><img id="form_image_'.$this->id.'" src="'.FILESYSTEM_DIR_V21.$this->image_folder;
		if ($this->value != '' && $image_exists)
			$html .= $this->value;
		else
			$html .= $this->image_default;
		$html .= '" alt="" /></div>';
		
		$html .= '<p><img src="img/icons/user/image_add.png" alt="" /> <a href="#" '.
				'onclick="popup(\'frame.php?page=formwizard-image&amp;do=new&amp;'.
				'file='.$this->value.'&amp;ref='.$this->id.'\')">Neues Bild</a></p>';
		
		if ($this->value != '' && $image_exists)
			$html .= '<p id="form_image_url_'.$this->id.'">';
		else
			$html .= '<p id="form_image_url_'.$this->id.'" style="display:none;">';
		$html .= '<img src="img/icons/user/image_delete.png" alt="" /> <a href="#" '.
				'onclick="if(confirm(\'Wollen Sie wirklich dieses Benutzerbild löschen?\'))'.
				'popup(\'frame.php?page=formwizard-image&amp;do=delete&amp;'.
				'file='.$this->value.'&amp;ref='.$this->id.'\');return false;">Bild löschen</a></p></div>';

		$html .= '<input type="hidden" name="'.$this->getFormName().'['.$this->name_id.']" '.
				'value="'.$this->value.'" />';

		return $this->renderHtmlElement($html);
	}
}


////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * SPAM Schutz vor Besuchern und BOTs
 */
class spamProtection {
	private $module_en = true;
	private $id;
	private $form;
	
	private $form_field_empty;
	private $error_message = '';
	
	private $check_result = false;
	
	/**
	 * Initialisiert die Klasse.
	 * @param	$id Eindeutige Identivikation des Formulars (string).
	 * @param	$form Zeiger auf die Klasse des zu schuetzenden Formulars.
	 */
	function __construct($id, $form) {
		/* Relevanz des Modules pruefen */
		if ((!ACP_MODULE_SPAM && !ACP_MODULE_SPAM_CAPTCHA)
			|| (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)) {
			$this->module_en = false;
			return false;
		}
		
		$this->id = 'demo';
		$this->form = $form;
		
		/* Verborgenes Formularfeld hinzufuegen */
		$this->form_field_empty = $this->form->addElement('text', 'spamProtectionEmpty');
		$this->form_field_empty->setCssClass('spam-protect');
		
		/* Session-Daten vorbereiten */
		if (!isset($_SESSION['spamProtection']))
			$_SESSION['spamProtection'] = array();
		
		if (!isset($_SESSION['spamProtection'][$this->id]))
			$_SESSION['spamProtection'][$this->id] = array();
		
		return true;
	}
	
	/**
	 * Formular wird angefordert
	 */
	function printingForm() {
		/* Relevanz des Modules pruefen */
		if (!$this->module_en)
			return false;
		
		/* Erste Anforderung des Formulars */
		if (!$this->form->checkSubmit()) {
			$_SESSION['spamProtection'][$this->id]['t_form'] = TIME_STAMP;
		}
		
		return true;
	}
	
	/**
	 * Captcha im Formular einfuegen.
	 */
	function printCaptcha() {
		/* Relevanz des Modules pruefen */
		if (!$this->module_en || !ACP_MODULE_SPAM_CAPTCHA)
			return false;
		
		/** @todo Captcha generieren */
	}
	
	/**
	 * Auswertung des SPAM Schutzes.
	 * @note	Darf nur einmal Aufgeruft werden.
	 * @see		checkState()
	 */
	function check() {
		/* Relevanz des Modules pruefen */
		if (!$this->module_en)
			return true;
		
		/** @todo Captcha auswerten */
		
		/* Leeres Formularfeld */
		if ($this->form_field_empty->getValue() != '') {
			$this->error_message = 'Das erste Formularfeld muss leer sein.';
			$this->statsSpamDetected();
			return false;
		}
		
		/* 2 Minuten Reload-Sperre */
		if (isset($_SESSION['spamProtection'][$this->id]['t_submit'])
				&& $_SESSION['spamProtection'][$this->id]['t_submit'] + 120 > TIME_STAMP) {
			$this->error_message = 'Sie müssen mindestens zwei Minuten warten bis Sie erneut einen Eintrag schreiben dürfen.';
			$this->statsSpamDetected();
			return false;
		}
		
		/* Formular muss vorher angefordert werden */
		if (!isset($_SESSION['spamProtection'][$this->id]['t_form'])
				|| $_SESSION['spamProtection'][$this->id]['t_form'] == 0) {
			$this->error_message = 'Sie wurden als Spam-Roboter eingeschtuft.';
			$this->statsSpamDetected();
			return false;
		}
		
		/* Min 10 Sekunden zum ausfuellen des Formulars */
		if ($_SESSION['spamProtection'][$this->id]['t_form'] + 10 > TIME_STAMP) {
			$this->error_message = 'Sie waren zu schnell mit dem Ausfüllen des Formulars.';
			$this->statsSpamDetected();
			return false;
		}
		
		/* Alles Korrekt -> Kein SPAM */
		$_SESSION['spamProtection'][$this->id]['t_form'] = 0;
		$_SESSION['spamProtection'][$this->id]['t_submit'] = TIME_STAMP;
		
		$this->check_result = true;
		
		return true;
	}
	
	/**
	 * Status des letzten Checks
	 */
	function checkState() {
		/* Relevanz des Modules pruefen */
		if (!$this->module_en)
			return true;
		
		return $this->check_result;
	}
	
	/**
	 * Ausgabe der ERROR Nachricht
	 */
	function getErrorMessage() {
		return $this->error_message;
	}
	
	/**
	 * Erweiterung: Statistik SPAM-Versuche zaehlen
	 */
	function statsSpamDetected() {
		if (!ACP_MODULE_STATISTIC)
			return true;
		
		mysql_query('UPDATE '.DB_TABLE_ROOT.'cms_register SET number=(number+1)
				WHERE name="Stats_CtrSpamBlock"', DB_CMS)
				OR FatalError(FATAL_ERROR_MYSQL);
		
		return true;
	}
}

?>