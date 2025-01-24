<?php
/**
 *	Generate XLS template
 *********************************/
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');
# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result;
$type = $GET->type;

# verify that user is logged in
$User->check_user_session();
// Create a workbook
$filename = "phpipam_template_" . $type . ".xls";
$workbook = new Spreadsheet_Excel_Writer();
$lineCount = 0;

// Create a worksheet
$worksheet = $workbook->addWorksheet("template");
$worksheet->setInputEncoding("utf-8");


if ($type == 'subnets'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('subnets');
	// set headers
	$worksheet->write($lineCount, 0, _('Section'));
	$worksheet->write($lineCount, 1, _('Subnet'));
	$worksheet->write($lineCount, 2, _('Mask'));
	$worksheet->write($lineCount, 3, _('Description'));
	$worksheet->write($lineCount, 4, _('VLAN'));
	$worksheet->write($lineCount, 5, _('Domain'));
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
	// "section","ip_addr","hostname","description","vrf","subnet","mac","owner","device","note","tag","gateway"
	$worksheet->write($lineCount, 0, _('Section'));
	$worksheet->write($lineCount, 1, _('IP address'));
	$worksheet->write($lineCount, 2, _('Hostname'));
	$worksheet->write($lineCount, 3, _('Description'));
	$worksheet->write($lineCount, 4, _('VRF'));
	$worksheet->write($lineCount, 5, _('Subnet'));
	$worksheet->write($lineCount, 6, _('MAC'));
	$worksheet->write($lineCount, 7, _('Owner'));
	$worksheet->write($lineCount, 8, _('Device'));
	$worksheet->write($lineCount, 9, _('Note'));
	$worksheet->write($lineCount, 10, _('Tag'));
	$worksheet->write($lineCount, 11, _('Is_Gateway'));
	$fc =12 ;
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
	$worksheet->write($lineCount, 3, _('Domain'));
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
} elseif ($type == 'devices'){

    $curColumn=0;
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('devices');
	// set headers
    $worksheet->write($lineCount, $curColumn++, _('hostname'));
    $worksheet->write($lineCount, $curColumn++, _('ip_addr'));
    $worksheet->write($lineCount, $curColumn++, _('deviceType'));
    $worksheet->write($lineCount, $curColumn++, _('description'));
    $worksheet->write($lineCount, $curColumn++, _('section'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_community'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_version'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_port'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_timeout'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_queries'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_sec_level'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_auth_protocol'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_auth_pass'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_priv_protocol'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_priv_pass'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_ctx_name'));
#    $worksheet->write($lineCount, $curColumn++, _('snmp_v3_ctx_engine_id'));
    $worksheet->write($lineCount, $curColumn++, _('rack'));
    $worksheet->write($lineCount, $curColumn++, _('rack_start'));
    $worksheet->write($lineCount, $curColumn++, _('rack_size'));
    $worksheet->write($lineCount, $curColumn++, _('location'));
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $curColumn, $k);
		$curColumn++;
	}

} elseif ($type == 'devtype'){
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('deviceTypes');
	// set headers
	$worksheet->write($lineCount, 0, _('tName'));
	$worksheet->write($lineCount, 1, _('tDescription'));
	$fc =2 ;
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $fc, $k);
		$fc++;
	}

}

// sending HTTP headers
$workbook->send($filename);
// Let's send the file
$workbook->close();
