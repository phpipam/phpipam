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

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_L2-Domains_export.xls";
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
$worksheet_name = "L2 domains";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;

//write headers
if ($GET->name == "on") {
	$worksheet->write($curRow, $curColumn, _('Name') ,$format_header);
	$curColumn++;
}
if ($GET->description == "on") {
	$worksheet->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
}

$curRow++;
$curColumn = 0;

foreach ($vlan_domains as $vlan_domain) {
	//cast
	$vlan_domain = (array) $vlan_domain;

	if ($GET->name == "on") {
		$worksheet->write($curRow, $curColumn, $vlan_domain['name'], $format_text);
		$curColumn++;
	}
	if ($GET->description == "on") {
		$worksheet->write($curRow, $curColumn, $vlan_domain['description'], $format_text);
		$curColumn++;
	}

	$curRow++;
	$curColumn = 0;
}


// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
