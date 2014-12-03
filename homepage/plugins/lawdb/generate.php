<?php

 /*
 =====================================================
 Name ........: Excel Gesetzesartikel
 Projekt .....: Linkverzeichnis
 Datiename ...: generate.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 16.07.2012 | Programm erstellt
 -----------------------------------------------------
 Beschreibung :

 =====================================================
 */

///////////////////////////////////////////////////////
if (!defined('SWISS_WEBDESIGN'))	die();
///////////////////////////////////////////////////////

if (!isset($mysql_categorie, $mysql_office, $mysql_result))
	die('Loading Error Excel Generator');

/** Include PHPExcel */
require_once 'Classes/PHPExcel.php';

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator('MISO GmbH')
							 ->setLastModifiedBy('MISO GmbH')
							 ->setTitle("Office 2007 XLSX Test Document")
							 ->setSubject("Office 2007 XLSX Test Document")
							 ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
							 ->setKeywords("office 2007 openxml php")
							 ->setCategory("Test result file");

// Print settings
$objPHPExcel->getActiveSheet()->getPageSetup()
		->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$objPHPExcel->getActiveSheet()->getPageSetup()
		->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A3);
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(80);

// Style definitionen
$style_table_haeder = array(
	'font'    => array(
		'bold'      => true,
		'color' => array('argb' => 'FFFFFFFF')
	),
	'borders' => array(
		'outline'     => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF000000')
		),
		'vertical'     => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF000000')
		)
	),		
	'fill' => array(
		'type'       => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => array(
			'argb' => 'FF8064A2'
		),
	),
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
	)
);
$style_table_body = array(
	'borders' => array(
		'horizontal' => array(
			'style' => PHPExcel_Style_Border::BORDER_DOTTED,
			'color' => array('argb' => 'FF999999')
		),
		'vertical' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF000000')
		),
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF000000')
		)
	),
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP,
		'wrap' => true
	)
);

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Gesetze und Verordnungen');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Title of the document
$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Gesetze und Verordnungen');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(16);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

// Table Header
$column = ord('B');
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(6);
$objPHPExcel->getActiveSheet()->setCellValue('A3', 'Nummer');
$objPHPExcel->getActiveSheet()->getStyle('A3')->getAlignment()->setTextRotation(90);
// Categories
for ($i=0; $i<sizeof($mysql_categorie); $i++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(6);
	$objPHPExcel->getActiveSheet()->getStyle(chr($column).'3')->getAlignment()->setTextRotation(90);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', $mysql_categorie[$i][1]);
}
// Table Header
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(18);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Abkürzung');
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(28);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Titel');
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(28);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Quelle');
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(15);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Ausgabestand');
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(15);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Letzte Kontrolle');
// Concerned division
if (sizeof($mysql_office)>0) {
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column).'2', 'Betroffene Unternehmensbereiche');
	$objPHPExcel->getActiveSheet()->getStyle(chr($column).'2')->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->mergeCells(chr($column).'2:'.chr($column + sizeof($mysql_office) - 1).'2');
	for ($i=0; $i<sizeof($mysql_office); $i++) {
		$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(6);
		$objPHPExcel->getActiveSheet()->getStyle(chr($column).'3')->getAlignment()->setTextRotation(90);
		$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', $mysql_office[$i][1]);
	}
}
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(40);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Inhalt');
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(30);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).'3', 'Verpflichtungen');
$objPHPExcel->getActiveSheet()->getColumnDimension(chr($column))->setWidth(30);
$objPHPExcel->getActiveSheet()->setCellValue(chr($column).'3', 'Auswirkungen von Änderungen');

// Table header style
$objPHPExcel->getActiveSheet()->getStyle('A2:'.chr($column).'3')->applyFromArray($style_table_haeder);
// Filter
$objPHPExcel->getActiveSheet()->setAutoFilter('A3:'.chr($column).'3');
// Repeat table header on each page
$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 3);
// Block table header when scrolling
$objPHPExcel->getActiveSheet()->freezePane('A4');


// Article rows
$num = 4;
while ($row = mysql_fetch_array($mysql_result)) {
	$column = ord('B');
	// Number
	$objPHPExcel->getActiveSheet()->setCellValue('A'.$num, $num - 3);
	// Categories
	for ($i=0; $i<sizeof($mysql_categorie); $i++) {
		if ($row['categorie'] & $mysql_categorie[$i][0]) {
			$objPHPExcel->getActiveSheet()->setCellValue(chr($column).$num, 'X');
		}
		$column++;
	}
	$objPHPExcel->getActiveSheet()->getCell(chr($column).$num)->getHyperlink()->setUrl($row['url']);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).$num, $row['abbr']);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).$num, $row['caption']);
	// Source
	$objPHPExcel->getActiveSheet()->getCell(chr($column).$num)->getHyperlink()
			->setUrl($mysql_source[$row['source']][1]);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).$num, $mysql_source[$row['source']][0]);
	// Date
	$objPHPExcel->getActiveSheet()->getStyle(chr($column).$num)->getNumberFormat()
			->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).$num,
			PHPExcel_Shared_Date::PHPToExcel($row['date']));
	$objPHPExcel->getActiveSheet()->getStyle(chr($column++).$num)->getNumberFormat()
			->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);	// Last controll is free
	// Concerned division
	for ($i=0; $i<sizeof($mysql_office); $i++) {
		if ($row['office'] & $mysql_office[$i][0]) {
			$objPHPExcel->getActiveSheet()->setCellValue(chr($column).$num, 'X');
		}
		$column++;
	}
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).$num, $row['content']);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column++).$num, $row['commitment']);
	$objPHPExcel->getActiveSheet()->setCellValue(chr($column).$num, $row['amendment']);
	if ($row['hint']) {
		$objPHPExcel->getActiveSheet()->getStyle(chr($column).$num)->getFont()
				->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
	}
	
	// New line
	$num++;
}

// Formatting of the Text
$objPHPExcel->getActiveSheet()->getStyle('A4:'.chr($column).($num-1))->applyFromArray($style_table_body);
$objPHPExcel->getActiveSheet()->getStyle('B4:'.chr(ord('B') + sizeof($mysql_categorie) - 1).($num-1))
		->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle(chr(ord('B') + sizeof($mysql_categorie) + 3).'4:'
		.chr(ord('B') + sizeof($mysql_categorie) + 4 + sizeof($mysql_office)).($num-1))
		->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$outputfile = 'output'.mt_rand().'.xlsx';
//$objWriter->save('/home/httpd/vhosts/swiss-webdesign.ch/subdomains/miso/httpdocs/excel/output.xlsx');
$objWriter->save('/home/httpd/vhosts/swiss-webdesign.ch/subdomains/miso/httpdocs/excel/'.$outputfile);

?>