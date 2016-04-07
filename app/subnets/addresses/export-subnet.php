<?php

/**
 *	Generate XLS file for subnet
 *********************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);

# verify that user is logged in
$User->check_user_session();

# we dont need any errors!
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

# fetch subnet details
$subnet = (array) $Tools->fetch_object ("subnets", "id", $_GET['subnetId']);
# fetch all IP addresses in subnet
$addresses = $Addresses->fetch_subnet_addresses ($_GET['subnetId'], "ip_addr", "asc");
# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');


# Create a workbook
$filename = isset($_GET['filename'])&&strlen(@$_GET['filename'])>0 ? $_GET['filename'] : "phpipam_subnet_export.xls";
$workbook = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8);

//formatting headers
$format_header =& $workbook->addFormat();
$format_header->setBold();
$format_header->setColor('black');
$format_header->setSize(12);

//format vlan
$format_vlan =& $workbook->addFormat();
$format_vlan->setColor('black');
$format_vlan->setSize(11);


//formatting titles
$format_title =& $workbook->addFormat();
$format_title->setColor('black');
$format_title->setFgColor(22);			//light gray
$format_title->setBottom(1);
$format_title->setTop(1);
$format_title->setAlign('left');

//formatting content - borders around IP addresses
$format_right =& $workbook->addFormat();
$format_right->setRight(1);
$format_left =& $workbook->addFormat();
$format_left->setLeft(1);
$format_top =& $workbook->addFormat();
$format_top->setTop(1);


// Create a worksheet
$worksheet_name = strlen($subnet['description']) > 30 ? substr($subnet['description'],0,27).'...' : $subnet['description'];
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$lineCount = 0;
$rowCount  = 0;

# Write title - subnet details
$worksheet->write($lineCount, $rowCount, $subnet['description'], $format_header );
$lineCount++;
$worksheet->write($lineCount, $rowCount, $Subnets->transform_address($subnet['subnet'],"dotted") . "/" .$subnet['mask'], $format_header );
$lineCount++;

# write VLAN details
$vlan = $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
if($vlan!=false) {
	$vlan = (array) $vlan;
	$vlan_text = strlen($vlan['name'])>0 ? "vlan: $vlan[number] - $vlan[name]" : "vlan: $vlan[number]";

	$worksheet->write($lineCount, $rowCount, $vlan_text, $format_vlan );
	$lineCount++;
}
$lineCount++;

//set row count
$rowCount = 0;

//write headers
if( (isset($_GET['ip_addr'])) && ($_GET['ip_addr'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('ip address') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['state'])) && ($_GET['state'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('ip state') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('description') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['dns_name'])) && ($_GET['dns_name'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('hostname') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['firewallAddressObject'])) && ($_GET['firewallAddressObject'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('fw object') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['mac'])) && ($_GET['mac'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('mac') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['owner'])) && ($_GET['owner'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('owner') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['switch'])) && ($_GET['switch'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('device') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['port'])) && ($_GET['port'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('port') ,$format_title);
	$rowCount++;
}
if( (isset($_GET['note'])) && ($_GET['note'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('note') ,$format_title);
	$rowCount++;
}

//custom
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//set temp name - replace space with three ___
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

		if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
			$worksheet->write($lineCount, $rowCount, $myField['name'] ,$format_title);
			$rowCount++;
		}
	}
}


$lineCount++;


//we need to reformat state!
$ip_types = $Addresses->addresses_types_fetch();
//fetch devices and reorder
$devices = $Tools->fetch_all_objects("devices", "hostname");
$devices_indexed = array();
if ($devices!==false) {
	foreach($devices as $d) {
		$devices_indexed[$d->id] = (object) $d;
	}
}
//add blank
$devices_indexed[0] = new StdClass ();
$devices_indexed[0]->hostname = 0;

//write all IP addresses
foreach ($addresses as $ip) {
	$ip = (array) $ip;

	//reset row count
	$rowCount = 0;

	//change switch ID to name
	$ip['switch'] = is_null($ip['switch'])||strlen($ip['switch'])==0||$ip['switch']==0||!isset($devices_indexed[$ip['switch']]) ? "" : $devices_indexed[$ip['switch']]->hostname;

	if( (isset($_GET['ip_addr'])) && ($_GET['ip_addr'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $Subnets->transform_address($ip['ip_addr'],"dotted"), $format_left);
		$rowCount++;
	}
	if( (isset($_GET['state'])) && ($_GET['state'] == "on") ) {
		if(@$ip_types[$ip['state']]['showtag']==1) {
		$worksheet->write($lineCount, $rowCount, $ip_types[$ip['state']]['type']);
		}
		$rowCount++;
	}
	if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['description']);
		$rowCount++;
	}
	if( (isset($_GET['dns_name'])) && ($_GET['dns_name'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['dns_name']);
		$rowCount++;
	}
	if( (isset($_GET['firewallAddressObject'])) && ($_GET['firewallAddressObject'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['firewallAddressObject']);
		$rowCount++;
	}
	if( (isset($_GET['mac'])) && ($_GET['mac'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['mac']);
		$rowCount++;
	}
	if( (isset($_GET['owner'])) && ($_GET['owner'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['owner']);
		$rowCount++;
	}
	if( (isset($_GET['switch'])) && ($_GET['switch'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['switch']);
		$rowCount++;
	}
	if( (isset($_GET['port'])) && ($_GET['port'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['port']);
		$rowCount++;
	}
	if( (isset($_GET['note'])) && ($_GET['note'] == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['note']);
		$rowCount++;
	}

	//custom
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $myField) {
			//set temp name - replace space with three ___
			$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

			if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
				$worksheet->write($lineCount, $rowCount, $ip[$myField['name']]);
				$rowCount++;
			}
		}
	}

	$lineCount++;
}


//new line
$lineCount++;

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();

?>