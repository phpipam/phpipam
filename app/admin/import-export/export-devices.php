<?php

/***
 *	Generate XLS file for L2 domains
 *********************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize required objects
$Database	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Admin		= new Admin ($Database);
$Tools		= new Tools ($Database);
if (!isset($Sections)) { $Sections	= new Sections ($Database); }
if (!isset($Devtype)) { $Devtype = new Devtype ($Database); }
if (!isset($Devices)) { $Devices = new Devtype ($Database); }

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# prepare HTML variables
$custom_fields_names = "";
$custom_fields_boxes = "";
$section_ids = array();
$fields = array ( 'id', 'hostname', 'ip_addr', 'type', 'description', 'sections', 'rack', 'rack_start', 'rack_size', 'location' );

if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//change spaces to "___" so it can be used as element id
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);
		array_push ( $fields, $myField['nameTemp'] );
	}
}

$section_ids = array ();
$devtypes =  $Devtype->fetch_all_objects("deviceTypes", "tid");
$deviceTypes = [];
$devices = $Devices->fetch_all_objects("devices", "id");
$all_sections = $Sections->fetch_all_sections();

if (is_array($all_sections)) {
	foreach ($all_sections as $section) {
		$section = (array) $section;
		$section_ids[$section['id']] = $section;
	}
}

if (is_array($devtypes)) {
	foreach ($devtypes as $d) {
	    $d = (array) $d;
	    $deviceTypes[$d['tid']] = $d;
	}
}


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



foreach ($fields as $k) {
	if ((!isset($_GET[$k])) || ($_GET[$k] != "on"))
		continue;

	$worksheet->write($curRow, $curColumn, _($k), $format_header);
	$curColumn++;
}

$curRow++;
$curColumn = 0;

foreach ($devices as $d) {
	//cast
	$d = (array) $d;

    foreach ($fields as $k) {
		if ((!isset($_GET[$k])) || ($_GET[$k] != "on"))
			continue;

		if (!isset($d[$k])) {
			$d[$k] = '';
		}
		if ($k == "type" && isset($deviceTypes[$d[$k]])) {
			$d[$k] = $deviceTypes[$d[$k]]['tname'];
		}

		$worksheet->write($curRow, $curColumn, $d[$k], $format_text);
		$curColumn++;
    }

	$curRow++;
	$curColumn = 0;
}


// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
