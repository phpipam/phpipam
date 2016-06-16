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
$filename = "phpipam_template_" . $type . ".xls";
$workbook = new Spreadsheet_Excel_Writer();
$lineCount = 0;

// Create a worksheet
$worksheet = $workbook->addWorksheet("template");


if ($type == 'subnets'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('subnets');
	// set headers
	$worksheet->write($lineCount, 0, _('Section'));
	$worksheet->write($lineCount, 1, _('Subnet'));
	$worksheet->write($lineCount, 2, _('Mask'));
	$worksheet->write($lineCount, 3, _('Description'));
	$worksheet->write($lineCount, 4, _('VLAN'));
	$worksheet->write($lineCount, 5, _('VLAN Domain'));
	$worksheet->write($lineCount, 6, _('VRF'));
	$fc =7 ;
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $fc, $k);
		$fc++;
	}
}
elseif ($type == 'ipaddr'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('ipaddresses');
	// set headers
	// "section","ip_addr","dns_name","description","vrf","subnet","mac","owner","device","note","tag","gateway"
	$worksheet->write($lineCount, 0, _('Section'));
	$worksheet->write($lineCount, 1, _('IP address'));
	$worksheet->write($lineCount, 2, _('DNS Hostname'));
	$worksheet->write($lineCount, 3, _('Description'));
	$worksheet->write($lineCount, 4, _('VRF'));
	$worksheet->write($lineCount, 4, _('Subnet'));
	$worksheet->write($lineCount, 5, _('MAC'));
	$worksheet->write($lineCount, 6, _('Owner'));
	$worksheet->write($lineCount, 7, _('Device'));
	$worksheet->write($lineCount, 8, _('Note'));
	$worksheet->write($lineCount, 9, _('Tag'));
	$worksheet->write($lineCount, 10, _('Gateway'));
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
	$worksheet->write($lineCount, 0, _('Name'));
	$worksheet->write($lineCount, 1, _('RD'));
	$worksheet->write($lineCount, 2, _('Description'));
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
	$worksheet->write($lineCount, 0, _('Name'));
	$worksheet->write($lineCount, 1, _('Number'));
	$worksheet->write($lineCount, 2, _('Description'));
	$worksheet->write($lineCount, 3, _('Domain name'));
	$fc =4 ;
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $fc, $k);
		$fc++;
	}
}
elseif ($type == 'l2dom'){
	// set headers
	$worksheet->write($lineCount, 0, _('Name'));
	$worksheet->write($lineCount, 1, _('Description'));
	$fc =2 ;
}
// sending HTTP headers
$workbook->send($filename);
// Let's send the file
$workbook->close();
?>
