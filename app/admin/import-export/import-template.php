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
	$worksheet->write($lineCount, 5, _('Domain'));
	$worksheet->write($lineCount, 6, _('VRF'));
	$worksheet->write($lineCount, 7, _('Location'));
	$fc =8 ;
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
	$worksheet->write($lineCount, 12, _('Location'));
	$fc =13 ;
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

} 
elseif ($type == 'devices'){

    $curColumn=0;
	//get all custom fields!
	$custom_address_fields = $Tools->fetch_custom_fields('devices');
	// set headers
    $worksheet->write($lineCount, $curColumn++, _('hostname'));
    $worksheet->write($lineCount, $curColumn++, _('ip_addr'));
    $worksheet->write($lineCount, $curColumn++, _('deviceType'));
    $worksheet->write($lineCount, $curColumn++, _('description'));
    $worksheet->write($lineCount, $curColumn++, _('section'));
    $worksheet->write($lineCount, $curColumn++, _('location'));
	foreach($custom_address_fields as $k=>$f) {
		$worksheet->write($lineCount, $curColumn, $k);
		$curColumn++;
	}

} 
elseif ($type == 'devtype'){
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
elseif ($type == 'hardware'){

    $curColumn=0;
	// set headers
    $worksheet->write($lineCount, $curColumn++, _('serialNumber'));
    $worksheet->write($lineCount, $curColumn++, _('model'));
    $worksheet->write($lineCount, $curColumn++, _('status'));
    $worksheet->write($lineCount, $curColumn++, _('dateRecived'));
    $worksheet->write($lineCount, $curColumn++, _('ownedBy'));
    $worksheet->write($lineCount, $curColumn++, _('managedBy'));
    $worksheet->write($lineCount, $curColumn++, _('device'));
    $worksheet->write($lineCount, $curColumn++, _('deviceMember'));
    $worksheet->write($lineCount, $curColumn++, _('comment'));
    $worksheet->write($lineCount, $curColumn++, _('rack'));
    $worksheet->write($lineCount, $curColumn++, _('rack_start'));
    $worksheet->write($lineCount, $curColumn++, _('halfUnit'));
	$lineCount++;
    $curColumn=0;
    $worksheet->write($lineCount, $curColumn++, _('<serialNumber>'));
    $worksheet->write($lineCount, $curColumn++, _('<model name>'));
    $worksheet->write($lineCount, $curColumn++, _('usually <deployed> or <staged>'));
    $worksheet->write($lineCount, $curColumn++, _('<YYYY-MM-DD> if not known leave blank'));
    $worksheet->write($lineCount, $curColumn++, _('usually <UMG>'));
    $worksheet->write($lineCount, $curColumn++, _('usually <UMG>'));
    $worksheet->write($lineCount, $curColumn++, _('<device name>'));
    $worksheet->write($lineCount, $curColumn++, _('<a> - <h>'));
    $worksheet->write($lineCount, $curColumn++, _('<text>'));
    $worksheet->write($lineCount, $curColumn++, _('<rack name>'));
    $worksheet->write($lineCount, $curColumn++, _('<1> - <63>'));
    $worksheet->write($lineCount, $curColumn++, _('If half rack device <left> or <right> otherwise leave blank'));

}
elseif ($type == 'schema'){

    $curColumn=0;
	// set headers
    $worksheet->write($lineCount, $curColumn++, _('locationSize'));
    $worksheet->write($lineCount, $curColumn++, _('addressType'));
    $worksheet->write($lineCount, $curColumn++, _('vrf'));
    $worksheet->write($lineCount, $curColumn++, _('description'));
    $worksheet->write($lineCount, $curColumn++, _('isSummary'));
    $worksheet->write($lineCount, $curColumn++, _('parent'));
    $worksheet->write($lineCount, $curColumn++, _('mask'));
    $worksheet->write($lineCount, $curColumn++, _('offset'));
    $worksheet->write($lineCount, $curColumn++, _('base'));
    $worksheet->write($lineCount, $curColumn++, _('vlanDef'));
	$lineCount++;
    $curColumn=0;
    $worksheet->write($lineCount, $curColumn++, _('<location size>'));
    $worksheet->write($lineCount, $curColumn++, _('<address type>'));
    $worksheet->write($lineCount, $curColumn++, _('<vrf> leave blank if Summary for multiple VRFs'));
    $worksheet->write($lineCount, $curColumn++, _('<text>'));
    $worksheet->write($lineCount, $curColumn++, _('<Yes> if Summary otherwise <No>'));
    $worksheet->write($lineCount, $curColumn++, _('<parent description> or <None> if this is Top Level Summary'));
    $worksheet->write($lineCount, $curColumn++, _('<##>'));
    $worksheet->write($lineCount, $curColumn++, _('<0> - <255>'));
    $worksheet->write($lineCount, $curColumn++, _('<0> - <255>'));
    $worksheet->write($lineCount, $curColumn++, _('<1>-<4092> leave blank if not a VLAN'));

}

// sending HTTP headers
$workbook->send($filename);
// Let's send the file
$workbook->close();
?>
