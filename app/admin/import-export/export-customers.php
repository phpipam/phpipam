<?php

/***
 *	Generate XLS file for Customers
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
$Customers	= new Customers ($Database);

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('customers');
# prepare HTML variables
$custom_fields_names = "";
$custom_fields_boxes = "";
$fields = array ( 'id', 'title', 'address', 'postcode', 'city', 'state', 'lat', 'long', 'contact_person', 'contact_phone', 'contact_mail', 'note' );

if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//change spaces to "___" so it can be used as element id
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);
		array_push ( $fields, $myField['nameTemp'] );
	}
}

$customers = $Customers->fetch_all_objects("customers", "id");

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
    if( ($_GET[$k] == "on") ) {
        $worksheet->write($curRow, $curColumn, _($k) ,$format_header);
        $curColumn++;
    }
}

$curRow++;
$curColumn = 0;

foreach ($devices as $d) {
	//cast
	$d = (array) $d;

    foreach ($fields as $k) {
        if( (isset($_GET[$k])) && ($_GET[$k] == "on") ) {
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