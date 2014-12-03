<?php

 /*
 =====================================================
 Name ........: Filter Gesetzesartikel
 Projekt .....: Linkverzeichnis
 Datiename ...: filter.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 03.08.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :

 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined("ACP_CHECK_SUM"))	die();
///////////////////////////////////////////////////////
if (!ACP_MODULE_LAWDB_EN)		die();
ACP_AdminAccess(ACP_ACCESS_M_LAWDB, true);
///////////////////////////////////////////////////////
$ACP_ApplicationInfo['categorie'] = 20;
$ACP_ApplicationInfo['menu_search'] = "style=\"display:none\" id=\"secondmenu_lawdb\"";
$ACP_ApplicationInfo['menu_replace'] = "id=\"secondmenu_lawdb\"";
///////////////////////////////////////////////////////

echo '<h1 class="first">Gesetzesartikel</h1>';

$sql_filter = 'WHERE 1';
$sql_order = '';
$message = '';

$form = new formWizard('form', "?".$_SERVER["QUERY_STRING"], 'post', 'form_acp_standard');
$search = $form->addElement('text', 'search', 'Suche');
/* Liste mit allen Kategorien */
$result = mysql_query('SELECT * FROM '.DB_TABLE_PLUGIN.'lawdb_categorie ORDER BY name ASC', DB_CMS)
		OR FatalError(FATAL_ERROR_MYSQL);
/* Demonstrationsmodus */
$categorie = $form->addElement('checkbox', 'categorie', 'Kategorien', 0);
$categorie->setSubLabel('Beispiel');
while ($row = mysql_fetch_array($result)) {
	$categorie = $form->addElement('checkbox', 'categorie', NULL, $row['id']);
	$categorie->setSubLabel($row['name']);
}
/* Sortierung nach */
$sort = $form->addElement('radio', 'sort', 'Sortierung', 'abbr', true);
$sort->setSubLabel('Abkürzung');
$sort->setChecked(true);
$sort = $form->addElement('radio', 'sort', NULL, 'caption');
$sort->setSubLabel('Titel');
$sort = $form->addElement('radio', 'sort', NULL, 'timestamp');
$sort->setSubLabel('Bearbeitungszeitpunkt');
/* Reihenfolge der Sortierung */
$order = $form->addElement('radio', 'order', 'Sortierung', 'ASC', true);
$order->setSubLabel('Absteigend');
$order->setChecked(true);
$order = $form->addElement('radio', 'order', NULL, 'DESC');
$order->setSubLabel('Aufsteigend');

/* Button */
$submit = $form->addElement('submit', 'btn', NULL, 'Filter anwenden');

/* Auswertung */
if ($form->checkForm()) {
	/* Kategorien vorbereiten */
	$categorie_bitflag = 0;
	if (is_array($categorie->getRequest())) {
		foreach ($categorie->getRequest() as $e) {
			$categorie_bitflag |= (1<<$e);
		}
	}
	else {
		$categorie_bitflag = $categorie->getRequest();
	}
	
	/* Filter Kategorien und Suchergebnisse */
	if ($search->getValue() != '') {
		$sql_filter .= ' && (abbr LIKE "%'.StdSqlSafety($search->getValue()).'%")';
		$message .= 'Suche nach &quot;'.$search->getValue().'&quot; ';
	}
	if ($categorie_bitflag > 0x00) {
		$sql_filter .= ' && (categorie & '.(int)$categorie_bitflag.')';
		$message .= 'Es werden nicht alle Kategorien angezeigt.';
	}
	
	/* Sortierung */
	$sql_order .= 'ORDER BY '.StdSqlSafety($sort->getRequest()).' '.StdSqlSafety($order->getRequest());
	
	if ($message != '')
		$message .= '<br />';
	$message .= '<a href="?page=lawdb-filter">Filter bearbeiten</a>';
	
	echo ActionReport(REPORT_INFO, 'Filter aktiv', $message);
	include('list.php');
}
else {
	/* Formular ausgeben */
	echo $form->getForm();
}

?>