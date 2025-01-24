<?php

/***
 *	Generate XLS file for L2 domains
 *********************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize required objects
$Database	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Admin		= new Admin ($Database);
$Tools		= new Tools ($Database);
if (!isset($Devtype)) { $Devtype = new Devtype ($Database); }

# verify that user is logged in
$User->check_user_session();

# fetch all devtypes domains
$devtypes =  $Devtype->fetch_all_objects("deviceTypes", "tid");

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_deviceTypes_export.xls";
$workbook = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8);

//formatting headers
$format_header =& $workbook->addFormat();
$format_header->setBold();
$format_header->setColor('black');
$format_header->setSize(12);
$format_header->setAlign('left');

//formatting content
$format_text =& $workbook->addFormat();

// Create a worksheet
$worksheet_name = "devtypes domains";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;

//write headers
if ($GET->tid == "on") {
	$worksheet->write($curRow, $curColumn, _('id') ,$format_header);
	$curColumn++;
}
if ($GET->tname == "on") {
	$worksheet->write($curRow, $curColumn, _('Name') ,$format_header);
	$curColumn++;
}
if ($GET->tdescription == "on") {
	$worksheet->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
}

$curRow++;
$curColumn = 0;

foreach ($devtypes as $dt) {
	//cast
	$dt = (array) $dt;

	if ($GET->tid == "on") {
		$worksheet->write($curRow, $curColumn, $dt['tid'], $format_text);
		$curColumn++;
	}
	if ($GET->tname == "on") {
		$worksheet->write($curRow, $curColumn, $dt['tname'], $format_text);
		$curColumn++;
	}
	if ($GET->tdescription == "on") {
		$worksheet->write($curRow, $curColumn, $dt['tdescription'], $format_text);
		$curColumn++;
	}

	$curRow++;
	$curColumn = 0;
}


// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
