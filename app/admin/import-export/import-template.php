<?php
/**
 *	Generate XLS template
 *********************************/
/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');
# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result;
$type = $_GET['type'];

# verify that user is logged in
$User->check_user_session();
// Create a workbook
$filename = "phpipam_template_" . $type . "_". date("Y-m-d") .".xls";
$workbook = new Spreadsheet_Excel_Writer();
$lineCount = 0;

// Create a worksheet
$worksheet = $workbook->addWorksheet("template");


if ($type == 'subnets'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('subnets');
	// set headers
	$worksheet->write($lineCount, 0, _('section name'));
	$worksheet->write($lineCount, 1, _('subnet'));
	$worksheet->write($lineCount, 2, _('mask'));
	$worksheet->write($lineCount, 3, _('description'));
	$worksheet->write($lineCount, 4, _('vlan name'));
	$worksheet->write($lineCount, 5, _('domain name'));
	$worksheet->write($lineCount, 6, _('vrf name'));
	$fc =7 ;
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $fc, $k);
		$fc++;
	}
}
elseif ($type == 'vrf'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('vrf');
	// set headers
	$worksheet->write($lineCount, 0, _('name'));
	$worksheet->write($lineCount, 1, _('rd'));
	$worksheet->write($lineCount, 2, _('description'));
	$fc =3 ;
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $fc, $k);
		$fc++;
	}
}
elseif ($type == 'vlans'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('vlans');
	// set headers
	$worksheet->write($lineCount, 0, _('name'));
	$worksheet->write($lineCount, 1, _('number'));
	$worksheet->write($lineCount, 2, _('description'));
	$worksheet->write($lineCount, 3, _('domain name'));
	$fc =4 ;
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $fc, $k);
		$fc++;
	}
}
elseif ($type == 'l2dom'){
	// set headers
	$worksheet->write($lineCount, 0, _('name'));
	$worksheet->write($lineCount, 1, _('description'));
	$fc =2 ;
}
// sending HTTP headers
$workbook->send($filename);
// Let's send the file
$workbook->close();
?>
