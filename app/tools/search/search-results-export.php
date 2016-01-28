<?php

/**
 *	Export search results
 ****************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Sections	= new Sections ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);

# verify that user is logged in
$User->check_user_session();

# fetch search term
$search_term = $_REQUEST['search_term'];

//initialize Pear IPv6 object
require_once( dirname(__FILE__) . '/../../../functions/PEAR/Net/IPv6.php' );
$Net_IPv6 = new Net_IPv6();

// ipv6 ?
if ($Net_IPv6->checkIPv6($search_term)!=false) {
	$type = "IPv6";
}
// check if mac address or ip address
elseif(strlen($search_term)==17 && substr_count($search_term, ":") == 5) {
    $type = "mac"; //count : -> must be 5
}
else if(strlen($search_term) == 12 && (substr_count($search_term, ":") == 0) && (substr_count($search_term, ".") == 0)){
    $type = "mac"; //no dots or : -> mac without :
}
else {
    $type = $Addresses->identify_address( $search_term ); //identify address type
}

# reformat if IP address for search
if ($type == "IPv4") 		{ $search_term_edited = $Tools->reformat_IPv4_for_search ($search_term); }	//reformat the IPv4 address!
elseif($type == "IPv6") 	{ $search_term_edited = $Tools->reformat_IPv6_for_search ($search_term); }	//reformat the IPv4 address!


# get all custom fields
$custom_address_fields = $Tools->fetch_custom_fields ("ipaddresses");
$custom_subnet_fields  = $Tools->fetch_custom_fields ("subnets");
$custom_vlan_fields    = $Tools->fetch_custom_fields ("vlans");
$custom_vrf_fields     = $Tools->fetch_custom_fields ("vrf");

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);

# set col size
$fieldSize 	= sizeof($selected_ip_fields);
$mySize 	= sizeof($custom_address_fields);
$colSpan 	= $fieldSize + $mySize + 3;


# search addresses
if(@$_REQUEST['addresses']=="on") 	{ $result_addresses = $Tools->search_addresses($search_term, $search_term_edited['high'], $search_term_edited['low']); }
# search subnets
if(@$_REQUEST['subnets']=="on") 	{ $result_subnets   = $Tools->search_subnets($search_term, $search_term_edited['high'], $search_term_edited['low'], $_REQUEST['ip']); }
# search vlans
if(@$_REQUEST['vlans']=="on") 		{ $result_vlans     = $Tools->search_vlans($search_term); }
# search vrf
if(@$_REQUEST['vrf']=="on") 		{ $result_vrf       = $Tools->search_vrfs($search_term); }


/*
 *	Write xls
 *********************/

// Create a workbook
$filename = _("phpipam_search_export_"). $search_term .".xls";
$workbook = new Spreadsheet_Excel_Writer();


//formatting titles
$format_title =& $workbook->addFormat();
$format_title->setColor('black');
$format_title->setFgColor(22);			//light gray
$format_title->setBottom(2);
$format_title->setAlign('left');


$lineCount = 0;		//for line change
$m = 0;				//for section change




/* -- Create a worksheet for addresses -- */
if(sizeof($result_addresses)>0) {
	$worksheet =& $workbook->addWorksheet(_('Addresses'));

	//write headers
	$x = 0;

	$worksheet->write($lineCount, $x, _('ip address') ,$format_title);		$x++;
	# state
	if(in_array('state', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('state') ,$format_title);			$x++;
	}
	# description, note
	$worksheet->write($lineCount, $x, _('description') ,$format_title);		$x++;
	$worksheet->write($lineCount, $x, _('hostname') ,$format_title);		$x++;
	# switch
	if(in_array('switch', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('device') ,$format_title);			$x++;
	}
	# port
	if(in_array('port', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('port') ,$format_title);			$x++;
	}
	# owner
	if(in_array('owner', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('owner') ,$format_title);			$x++;
	}
	# mac
	if(in_array('mac', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('mac') ,$format_title);				$x++;
	}
	# note
	if(in_array('note', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('note') ,$format_title);			$x++;
	}
	//custom
	if(sizeof($custom_address_fields) > 0) {
	foreach($custom_address_fields as $myField) {
	$worksheet->write($lineCount, $x, $myField['name'], $format_title);	$x++;
	}
	}

	//new line
	$lineCount++;

	//Write IP addresses
	foreach ($result_addresses as $ip) {
		//cast
		$ip = (array) $ip;

		# check permission
		$subnet_permission  = $Subnets->check_permission($User->user, $ip['subnetId']);
		if($subnet_permission > 0) {

			//get the Subnet details
			$subnet  = (array) $Subnets->fetch_subnet(null, $ip['subnetId']);
			//get section
			$section = (array) $Sections->fetch_section(null, $subnet['sectionId']);
			//get VLAN for subnet
			$vlan 	 = (array) (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
			//format vlan
			if(sizeof($vlan)>0) {
				if(strlen($vlan['number']) > 0) {
					$vlanText = " (vlan: " . $vlan['number'];
					if(strlen($vlan['name']) > 0) {
						$vlanText .= ' - '. $vlan['name'] . ')';
					}
					else {
							$vlanText .= ")";
					}
				}
			} else {
				$vlanText = "";
			}

			//section change
			if ($result_addresses[$m]->subnetId != $result_addresses[$m-1]->subnetId) {

				//new line
				$lineCount++;

				//subnet details
				$worksheet->write($lineCount, 0, $Subnets->transform_to_dotted($subnet['subnet']) . "/" .$subnet['mask'] . " - " . $subnet['description'] . $vlanText, $format_title );
				$worksheet->mergeCells($lineCount, 0, $lineCount, $colSpan-1);

				//new line
				$lineCount++;
			}
			$m++;

			$x = 0;
			$worksheet->write($lineCount, $x, $Subnets->transform_to_dotted($ip['ip_addr']), $format_left);	$x++;
			# state
			if(in_array('state', $selected_ip_fields)) {
			$worksheet->write($lineCount, $x, _($Addresses->address_type_index_to_type ($ip['state'])) );					$x++;
			}
			$worksheet->write($lineCount, $x, $ip['description']);					$x++;
			$worksheet->write($lineCount, $x, $ip['dns_name']);						$x++;
			# switch
			if(in_array('switch', $selected_ip_fields)) {
				if(strlen($ip['switch'])>0 && $ip['switch']!=0) {
					$device = (array) $Tools->fetch_device(null, $ip['switch']);
					$ip['switch'] = $device!=0 ? $device['hostname'] : "";
				}
				else {
					$ip['switch'] = "";
				}
				$worksheet->write($lineCount, $x, $ip['switch']);					$x++;
			}
			# port
			if(in_array('port', $selected_ip_fields)) {
			$worksheet->write($lineCount, $x, $ip['port']);							$x++;
			}
			# owner
			if(in_array('owner', $selected_ip_fields)) {
			$worksheet->write($lineCount, $x, $ip['owner']);						$x++;
			}
			# mac
			if(in_array('mac', $selected_ip_fields)) {
			$worksheet->write($lineCount, $x, $ip['mac']);							$x++;
			}
			# note
			if(in_array('note', $selected_ip_fields)) {
			$worksheet->write($lineCount, $x, $ip['note']);							$x++;
			}

			#custom
			if(sizeof($custom_address_fields) > 0) {
				foreach($custom_address_fields as $myField) {
					$worksheet->write($lineCount, $x, $ip[$myField['name']]); $x++;
				}
			}

			//new line
			$lineCount++;
		}
	}
}




/* -- Create a worksheet for subnets -- */
if(sizeof($result_subnets)>0) {
	$lineCount = 0;

	$worksheet =& $workbook->addWorksheet(_('Subnets'));

	//write headers
	$worksheet->write($lineCount, 0, _('Section') ,$format_title);
	$worksheet->write($lineCount, 1, _('Subet') ,$format_title);
	$worksheet->write($lineCount, 2, _('Mask') ,$format_title);
	$worksheet->write($lineCount, 3, _('Description') ,$format_title);
	$worksheet->write($lineCount, 4, _('Master subnet') ,$format_title);
	$worksheet->write($lineCount, 5, _('VLAN') ,$format_title);
	$worksheet->write($lineCount, 6, _('IP requests') ,$format_title);
	$c=7;
	if(sizeof($custom_subnet_fields) > 0) {
		foreach($custom_subnet_fields as $field) {
			$worksheet->write($lineCount, $c, $field['name'], $format_title);
			$c++;
		}
	}

	//new line
	$lineCount++;

	foreach($result_subnets as $line) {
		//cast
		$line = (array) $line;

		//get section details
		$section = (array) $Sections->fetch_section (null, $line['sectionId']);

		//format master subnet
		if($line['masterSubnetId'] == 0) { $line['masterSubnetId'] = "/"; }
		else {
			$line['masterSubnetId'] = (array) $Subnets->fetch_subnet (null, $line['masterSubnetId']);
			# folder?
			if($line['masterSubnetId']['isFolder']==1)	{ $line['masterSubnetId'] = $line['masterSubnetId']['description']; }
			else										{ $line['masterSubnetId'] = $Subnets->transform_to_dotted($line['masterSubnetId']['subnet']) .'/'. $line['masterSubnetId']['mask']. "(".$line['masterSubnetId']['description'].")"; }
		}
		//allowRequests
		if($line['allowRequests'] == 1) 	{ $line['allowRequests'] = 'yes'; }
		else 								{ $line['allowRequests'] = ''; }

		//vlan
		//get VLAN for subnet
		$vlan 	 = (array) $Tools->fetch_object("vlans", "vlanId", $line['vlanId']);
		//format vlan
		$line['vlanId'] = is_numeric($vlan['number']) ? $vlan['number'] : "";

		//print subnet
		$worksheet->write($lineCount, 0, $section['name']);
		if($line['isFolder']==1) {
		$worksheet->write($lineCount, 1, _('Folder'));
		$worksheet->write($lineCount, 2, "");
		}
		else {
		$worksheet->write($lineCount, 1, $Subnets->transform_to_dotted($line['subnet']));
		$worksheet->write($lineCount, 2, $line['mask']);
		}
		$worksheet->write($lineCount, 3, $line['description']);
		$worksheet->write($lineCount, 4, $line['masterSubnetId']);
		$worksheet->write($lineCount, 5, $line['vlanId']);
		$worksheet->write($lineCount, 6, $line['allowRequests']);
		//custom
		$c=7;
		if(sizeof($custom_subnet_fields) > 0) {
			foreach($custom_subnet_fields as $field) {
				$worksheet->write($lineCount, $c, $line[$field['name']]);
				$c++;
			}
		}

		//new line
		$lineCount++;
	}
}



/* -- Create a worksheet for VLANs -- */
if(sizeof($result_vlans)>0) {
	$lineCount = 0;

	$worksheet =& $workbook->addWorksheet(_('VLAN search results'));

	//write headers
	$worksheet->write($lineCount, 0, _('Name') ,$format_title);
	$worksheet->write($lineCount, 1, _('Number') ,$format_title);
	$worksheet->write($lineCount, 2, _('Description') ,$format_title);
	$c=3;
	if(sizeof($custom_vlan_fields) > 0) {
		foreach($custom_vlan_fields as $field) {
			$worksheet->write($lineCount, $c, $field['name'], $format_title);
			$c++;
		}
	}

	//new line
	$lineCount++;

	foreach($result_vlans as $line) {
		//cast
		$line = (array) $line;

		//print subnet
		$worksheet->write($lineCount, 0, $line['name'], $format_left);
		$worksheet->write($lineCount, 1, $line['number']);
		$worksheet->write($lineCount, 2, $line['description']);
		//custom
		$c=3;
		if(sizeof($custom_vlan_fields) > 0) {
			foreach($custom_subnet_fields as $field) {
				$worksheet->write($lineCount, $c, $line[$field['name']]);
				$c++;
			}
		}
		//new line
		$lineCount++;
	}
}






/* -- Create a worksheet for VRFs -- */
if(sizeof($result_vrf)>0) {
	$lineCount = 0;

	$worksheet =& $workbook->addWorksheet(_('VRF search results'));

	//write headers
	$worksheet->write($lineCount, 0, _('Name') ,$format_title);
	$worksheet->write($lineCount, 1, _('RD') ,$format_title);
	$worksheet->write($lineCount, 2, _('Description') ,$format_title);
	$c=3;
	if(sizeof($custom_vrf_fields) > 0) {
		foreach($custom_vrf_fields as $field) {
			$worksheet->write($lineCount, $c, $field['name'], $format_title);
			$c++;
		}
	}

	//new line
	$lineCount++;

	foreach($result_vrf as $line) {
		//cast
		$line = (array) $line;

		//print subnet
		$worksheet->write($lineCount, 0, $line['name'], $format_left);
		$worksheet->write($lineCount, 1, $line['rd']);
		$worksheet->write($lineCount, 2, $line['description']);
		//custom
		$c=3;
		if(sizeof($custom_vrf_fields) > 0) {
			foreach($custom_vrf_fields as $field) {
				$worksheet->write($lineCount, $c, $line[$field['name']]);
				$c++;
			}
		}
		//new line
		$lineCount++;
	}
}




// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();

?>
