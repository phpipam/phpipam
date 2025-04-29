<?php

/**
 *	Generate XLS file for subnet
 *********************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# Don't corrupt output with php errors!
disable_php_errors();

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

# fetch subnet details
$subnet = $Tools->fetch_object("subnets", "id", $GET->subnetId);
if (!is_object($subnet) || $Subnets->check_permission($User->user, $GET->subnetId, $subnet) == User::ACCESS_NONE) {
	$Result->fatal_http_error(404, _("Subnet not found"));
}
$subnet = (array) $subnet;

# fetch all IP addresses in subnet
$addresses = $Addresses->fetch_subnet_addresses ($GET->subnetId, "ip_addr", "asc") ? : [];

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');


# Create a workbook
$filename = isset($GET->filename)&&!is_blank($GET->filename) ? $GET->filename : "phpipam_subnet_export.xls";
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
	$vlan_text = !is_blank($vlan['name']) ? "vlan: $vlan[number] - $vlan[name]" : "vlan: $vlan[number]";

	$worksheet->write($lineCount, $rowCount, $vlan_text, $format_vlan );
	$lineCount++;
}
$lineCount++;

//set row count
$rowCount = 0;

//write headers
if( (isset($GET->ip_addr)) && ($GET->ip_addr == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('ip address') ,$format_title);
	$rowCount++;
}
if( (isset($GET->state)) && ($GET->state == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('ip state') ,$format_title);
	$rowCount++;
}
if( (isset($GET->description)) && ($GET->description == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('description') ,$format_title);
	$rowCount++;
}
if( (isset($GET->hostname)) && ($GET->hostname == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('hostname') ,$format_title);
	$rowCount++;
}
if( (isset($GET->firewallAddressObject)) && ($GET->firewallAddressObject == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('fw object') ,$format_title);
	$rowCount++;
}
if( (isset($GET->mac)) && ($GET->mac == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('mac') ,$format_title);
	$rowCount++;
}
if( (isset($GET->owner)) && ($GET->owner == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('owner') ,$format_title);
	$rowCount++;
}
if( (isset($GET->switch)) && ($GET->switch == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('device') ,$format_title);
	$rowCount++;
}
if( (isset($GET->port)) && ($GET->port == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('port') ,$format_title);
	$rowCount++;
}
if( (isset($GET->note)) && ($GET->note == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('note') ,$format_title);
	$rowCount++;
}
if( (isset($GET->location)) && ($GET->location == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('location') ,$format_title);
	$rowCount++;
}

//custom
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//set temp name - replace space with three ___
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

		if( (isset($GET->{$myField['nameTemp']})) && ($GET->{$myField['nameTemp']} == "on") ) {
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

//fetch locations and reorder
$locations = $Tools->fetch_all_objects("locations", "id");
$locations_indexed = array();
if ($locations!==false) {
	foreach($locations as $d) {
		$locations_indexed[$d->id] = (object) $d;
	}
}
//add blank
$locations_indexed[0] = new StdClass ();
$locations_indexed[0]->name = 0;

//write all IP addresses
foreach ($addresses as $ip) {
	$ip = (array) $ip;

	//reset row count
	$rowCount = 0;

	//change switch ID to name
	$ip['switch']   = is_null($ip['switch'])||is_blank($ip['switch'])||$ip['switch']==0||!isset($devices_indexed[$ip['switch']]) ? "" : $devices_indexed[$ip['switch']]->hostname;
	$ip['location'] = is_null($ip['location'])||is_blank($ip['location'])||$ip['location']==0||!isset($locations_indexed[$ip['location']]) ? "" : $locations_indexed[$ip['location']]->name;

	if( (isset($GET->ip_addr)) && ($GET->ip_addr == "on") ) {
		$worksheet->write($lineCount, $rowCount, $Subnets->transform_address($ip['ip_addr'],"dotted"), $format_left);
		$rowCount++;
	}
	if( (isset($GET->state)) && ($GET->state == "on") ) {
		if(@$ip_types[$ip['state']]['showtag']==1) {
		$worksheet->write($lineCount, $rowCount, $ip_types[$ip['state']]['type']);
		}
		$rowCount++;
	}
	if( (isset($GET->description)) && ($GET->description == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['description']);
		$rowCount++;
	}
	if( (isset($GET->hostname)) && ($GET->hostname == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['hostname']);
		$rowCount++;
	}
	if( (isset($GET->firewallAddressObject)) && ($GET->firewallAddressObject == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['firewallAddressObject']);
		$rowCount++;
	}
	if( (isset($GET->mac)) && ($GET->mac == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['mac']);
		$rowCount++;
	}
	if( (isset($GET->owner)) && ($GET->owner == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['owner']);
		$rowCount++;
	}
	if( (isset($GET->switch)) && ($GET->switch == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['switch']);
		$rowCount++;
	}
	if( (isset($GET->port)) && ($GET->port == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['port']);
		$rowCount++;
	}
	if( (isset($GET->note)) && ($GET->note == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['note']);
		$rowCount++;
	}
	if( (isset($GET->location)) && ($GET->location == "on") ) {
		$worksheet->write($lineCount, $rowCount, $ip['location']);
		$rowCount++;
	}

	//custom
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $myField) {
			//set temp name - replace space with three ___
			$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

			if( (isset($GET->{$myField['nameTemp']})) && ($GET->{$myField['nameTemp']} == "on") ) {
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
