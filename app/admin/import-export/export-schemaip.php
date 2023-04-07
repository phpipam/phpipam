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

# prepare HTML variables
$section_ids = array();
$fields = array ( "locationSize","deviceType","deviceNumber","offset" );

$hardware = $Tools->fetch_all_objects("schemamgmtips", "id");


# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_schemamgmtips_export.xls";
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
$worksheet_name = "schemaip";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;



foreach ($fields as $k) {
    if( ($_GET[$k] == "on") ) {
        $worksheet->write($curRow, $curColumn, _($k) ,$format_header);
        $curColumn++;
    }
}

$curRow++;
$curColumn = 0;

foreach ($hardware as $d) {
	//cast
	$d = (array) $d;

    foreach ($fields as $k) {
        if( (isset($_GET[$k])) && ($_GET[$k] == "on") ) {
			switch ($k){
				case "locationSize":
					$locsizes = $Tools->fetch_object("locationsizes", "id",$d[$k]);
					$d[$k]=$locsizes->locationSize;
					break;
				case "deviceType":
					$devtypes = $Tools->fetch_object("deviceTypes", "tid",$d[$k]);
					$d[$k]=$devtypes->tname;
					break;
			}
            $worksheet->write($curRow, $curColumn, $d[$k], $format_text);
            $curColumn++;
        }
    }

	$curRow++;
	$curColumn = 0;
}


// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();

?>