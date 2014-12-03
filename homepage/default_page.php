<?php

 /*
 =====================================================
 Name ........: Standart Seite
 Projekt .....: CMS 2.0 Kosmos
 Datiename ...: index.php
 -----------------------------------------------------
 Firma .......: swiss-webdesign.ch
 Author ......: Kevin Gerber
 Kontakt .....: kevin.gerber@swiss-webdesign.ch
 Internet ....: http://www.swiss-webdesign.ch
 -----------------------------------------------------
 |Version | Datum      | Aenderung
 |--------|------------|--------------------
 |1.0     | 30.08.2012 | Erstellt
 -----------------------------------------------------
 Beschreibung :
 Standartseite alle Projekte, wenn das CMS noch nicht
 vollstaendig installiert wurde.

 (c) by Kevin Gerber
 =====================================================
 */

/* Homepage ist offline */
header('HTTP/1.1 503 Service Unavailable');
header('Last-Modified: '.date(DATE_RFC822, filemtime(__FILE__)));
header('Cache-Control: post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');

$tpl = array();

$tpl['root_images'] = '/img/offline/';

$tpl['company'] = 'Kevin Gerber';
$tpl['header'] = 'Meine kleine Welt im World Wide Web';

$tpl['offlinetitle'] = 'Neuer Internetauftritt';
$tpl['offlinemessage'] = 'Hier entsteht in der nächsten Zeit der neue Internetauftritt von Kevin Gerber.';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">

<head>
  <meta http-equiv="content-type" content="application/xhtml+xml;charset=utf-8" />
  <title><?php echo $tpl['header']; ?> - <?php echo $tpl['company']; ?></title>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <style type="text/css">
	body {
	 	background-color:#edede8;
		font-size:10pt;
		color:#615d5a;
		font-family:Verdana;
	}
	#webdesign {
	   position:absolute;
	   right:10px;
	   bottom:10px;
	   margin:0;
	   padding:0;
	   height:40px;
       font-size:14pt;
	   font-weight:bold;
	   line-height:40px;
	   color:#bbb;
    }
    #webdesign img {
        float:right;
        margin-left:15px;
        border:none;
        height:40px;
    }    
	#center {
		position: absolute;
		top:50%;
		width:99%;
		text-align:center;
	}
	#content {
		position: absolute;
		left:50%;
		width:600px;
		height:280px;
		margin-top:-140px;
		margin-left:-300px;
		background-color:#fff;
		background-image:url(<?php echo $tpl['root_images']; ?>bg_border.gif);
		background-position:top left;
		background-repeat:no-repeat;
		text-align:center;
	}
	#content img {
		display:block;
		margin:20px auto;
		height:150px;
		width:150px;
	}
	#content h1 {
		margin-top:20px;
		font-size:16pt;
	}
  </style>
</head>

<body>

<div id="webdesign">
  <a href="http://www.swiss-webdesign.ch/"><img src="<?php echo $tpl['root_images']; ?>swiss-webdesign.png"></a>
</div>

<div id="center">
  <div id="content">
    <img src="<?php echo $tpl['root_images']; ?>in_work.jpg" alt="" />
    <h1><?php echo $tpl['offlinetitle']; ?></h1>
    <p><?php echo $tpl['offlinemessage']; ?></p>
  </div>
</div>

</body>
</html>
