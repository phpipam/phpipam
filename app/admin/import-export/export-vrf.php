<?php

/**
 *	Generate XLS file for VRFs
 *********************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Admin	 	= new Admin ($Database);

# verify that user is logged in
$User->check_user_session();

# fetch all vrfs
$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId");
if (!$all_vrfs) { $all_vrfs = array(); }

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_VRF_export.xls";
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
$worksheet_name = "VRFs";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;

//write headers
if( (isset($_GET['name'])) && ($_GET['name'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Name') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['rd'])) && ($_GET['rd'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('RD') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
}

$curRow++;

//write all VRF entries
foreach ($all_vrfs as $vrf) {
	//cast
	$vrf = (array) $vrf;

	//reset row count
	$curColumn = 0;

	if( (isset($_GET['name'])) && ($_GET['name'] == "on") ) {
		$worksheet->write($curRow, $curColumn, $vrf['name'], $format_text);
		$curColumn++;
	}
	if( (isset($_GET['rd'])) && ($_GET['rd'] == "on") ) {
		$worksheet->write($curRow, $curColumn, $vrf['rd'], $format_text);
		$curColumn++;
	}
	if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
		$worksheet->write($curRow, $curColumn, $vrf['description'], $format_text);
		$curColumn++;
	}

	$curRow++;
}

//new line
$curRow++;

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();

?>