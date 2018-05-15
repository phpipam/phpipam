<?php

/**
 *	Export search results
 ****************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
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

# we dont need any errors!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

# get requested params
if(isset($_GET['ip'])) {
    // remove chars
    $search_term =  htmlspecialchars(trim($_GET['ip']));
}
else {
    $search_term = "";
}

# change * to % for database wildchar
$search_term = trim($search_term);
$search_term = str_replace("*", "%", $search_term);

# parse parameters from cookie
if (isset($_COOKIE['search_parameters'])) {
    $params = json_decode($_COOKIE['search_parameters'], true);
    if($params) {
        foreach ($params as $k=>$p) {
            if ($p=="on") {
                $_REQUEST[$k] = $p;
            }
        }
    }
}

// IP address low/high reformat
if ($Tools->validate_mac ($search_term)===false) {
    // identify
    $type = $Addresses->identify_address( $search_term ); //identify address type

    # reformat if IP address for search
    if ($type == "IPv4") 		{ $search_term_edited = $Tools->reformat_IPv4_for_search ($search_term); }	//reformat the IPv4 address!
    elseif($type == "IPv6") 	{ $search_term_edited = $Tools->reformat_IPv6_for_search ($search_term); }	//reformat the IPv4 address!
}

# get all custom fields
$custom_address_fields   = $_REQUEST['addresses']=="on" ? $Tools->fetch_custom_fields ("ipaddresses") : array();
$custom_subnet_fields    = $_REQUEST['subnets']=="on"   ? $Tools->fetch_custom_fields ("subnets") : array();
$custom_vlan_fields      = $_REQUEST['vlans']=="on"     ? $Tools->fetch_custom_fields ("vlans") : array();
$custom_vrf_fields       = $_REQUEST['vrf']=="on"       ? $Tools->fetch_custom_fields ("vrf") : array();
$custom_circuit_fields   = $_REQUEST['circuits']=="on"  ? $Tools->fetch_custom_fields ("circuits") : array();
$custom_circuit_p_fields = $_REQUEST['circuits']=="on"  ? $Tools->fetch_custom_fields ("circuitProviders") : array();


# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);

# set col size
$fieldSize 	= sizeof($selected_ip_fields);
$mySize 	= sizeof($custom_address_fields);
$colSpan 	= $fieldSize + $mySize + 3;


# search addresses
if(@$_REQUEST['addresses']=="on") 	{ $result_addresses = $Tools->search_addresses($search_term, $search_term_edited['high'], $search_term_edited['low'], $custom_address_fields); }
# search subnets
if(@$_REQUEST['subnets']=="on") 	{ $result_subnets   = $Tools->search_subnets($search_term, $search_term_edited['high'], $search_term_edited['low'], $_REQUEST['ip'], $custom_subnet_fields); }
# search vlans
if(@$_REQUEST['vlans']=="on") 		{ $result_vlans     = $Tools->search_vlans($search_term, $custom_vlan_fields); }
# search vrf
if(@$_REQUEST['vrf']=="on") 		{ $result_vrf       = $Tools->search_vrfs($search_term, $custom_vrf_fields); }
# search circuits
if(@$_REQUEST['circuits']=="on") 	{ $result_circuits   = $Tools->search_circuits($search_term, $custom_circuit_fields); }
if(@$_REQUEST['circuits']=="on") 	{ $result_circuits_p = $Tools->search_circuit_providers($search_term, $custom_circuit_p_fields); }

/*
 *	Write xls
 *********************/

// Create a workbook
$filename = _("phpipam_search_export_"). $search_term .".xls";
$workbook = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8);

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
	$worksheet->setInputEncoding("utf-8");

	//write headers
	$x = 0;

	$worksheet->write($lineCount, $x, _('ip address') ,$format_title);		$x++;
	# state
	if(in_array('state', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('state') ,$format_title);			$x++;
	} else { $colSpan--; }
	# description, note
	$worksheet->write($lineCount, $x, _('description') ,$format_title);		$x++;
	$worksheet->write($lineCount, $x, _('hostname') ,$format_title);		$x++;
	# switch
	if(in_array('switch', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('device') ,$format_title);			$x++;
	} else { $colSpan--; }
	# port
	if(in_array('port', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('port') ,$format_title);			$x++;
	} else { $colSpan--; }
	# owner
	if(in_array('owner', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('owner') ,$format_title);			$x++;
	} else { $colSpan--; }
	# mac
	if(in_array('mac', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('mac') ,$format_title);				$x++;
	}else { $colSpan--; }
	# note
	if(in_array('note', $selected_ip_fields)) {
	$worksheet->write($lineCount, $x, _('note') ,$format_title);			$x++;
	}else { $colSpan--; }
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
			if (@$result_addresses[$m]->subnetId != @$result_addresses[$m-1]->subnetId) {

				//new line
				$lineCount++;

				//subnet details
				$worksheet->mergeCells($lineCount, 0, $lineCount, $colSpan-1);
				$worksheet->write($lineCount, 0, $Subnets->transform_to_dotted($subnet['subnet']) . "/" .$subnet['mask'] . " - " . $subnet['description'] . $vlanText, $format_title );

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
			$worksheet->write($lineCount, $x, $ip['hostname']);						$x++;
			# switch
			if(in_array('switch', $selected_ip_fields)) {
				if(strlen($ip['switch'])>0 && $ip['switch']!=0) {
					$device = (array) $Tools->fetch_object("devices", "id", $ip['switch']);
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
	$worksheet->setInputEncoding("utf-8");

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

	$worksheet =& $workbook->addWorksheet(_('VLANs'));
	$worksheet->setInputEncoding("utf-8");

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

	$worksheet =& $workbook->addWorksheet(_('VRFs'));
	$worksheet->setInputEncoding("utf-8");

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






/* -- Create a worksheet for Circuits -- */
if(sizeof($result_circuits)>0) {
	$lineCount = 0;

	$worksheet =& $workbook->addWorksheet(_('Circuits'));
	$worksheet->setInputEncoding("utf-8");

	//write headers
	$worksheet->write($lineCount, 0, _('Circuit Id') ,$format_title);
	$worksheet->write($lineCount, 1, _('Provider') ,$format_title);
	$worksheet->write($lineCount, 2, _('Type') ,$format_title);
	$worksheet->write($lineCount, 3, _('Capacity') ,$format_title);
	$worksheet->write($lineCount, 4, _('Status') ,$format_title);

	$c=5;
	if(sizeof($custom_circuit_fields) > 0) {
		foreach($custom_circuit_fields as $field) {
			$worksheet->write($lineCount, $c, $field['name'], $format_title);
			$c++;
		}
	}

	//new line
	$lineCount++;

	foreach($result_circuits as $line) {
		//cast
		$line = (array) $line;

		//print subnet
		$worksheet->write($lineCount, 0, $line['cid'], $format_left);
		$worksheet->write($lineCount, 1, $line['name']);
		$worksheet->write($lineCount, 2, $line['type']);
		$worksheet->write($lineCount, 3, $line['Capacity']);
		$worksheet->write($lineCount, 4, $line['status']);

		//custom
		$c=5;
		if(sizeof($custom_circuit_fields) > 0) {
			foreach($custom_circuit_fields as $field) {
				$worksheet->write($lineCount, $c, $line[$field['name']]);
				$c++;
			}
		}
		//new line
		$lineCount++;
	}
}






/* -- Create a worksheet for Circuit providers -- */
if(sizeof($result_circuits_p)>0) {
	$lineCount = 0;

	$worksheet =& $workbook->addWorksheet(_('Circuit providers'));
	$worksheet->setInputEncoding("utf-8");

	//write headers
	$worksheet->write($lineCount, 0, _('Name') ,$format_title);
	$worksheet->write($lineCount, 1, _('Description') ,$format_title);
	$worksheet->write($lineCount, 2, _('Contact') ,$format_title);

	$c=3;
	if(sizeof($custom_circuit_p_fields) > 0) {
		foreach($custom_circuit_p_fields as $field) {
			$worksheet->write($lineCount, $c, $field['name'], $format_title);
			$c++;
		}
	}

	//new line
	$lineCount++;

	foreach($result_circuits_p as $line) {
		//cast
		$line = (array) $line;

		//print subnet
		$worksheet->write($lineCount, 0, $line['name'], $format_left);
		$worksheet->write($lineCount, 1, $line['description']);
		$worksheet->write($lineCount, 2, $line['contact']);

		//custom
		$c=3;
		if(sizeof($custom_circuit_p_fields) > 0) {
			foreach($custom_circuit_p_fields as $field) {
				$worksheet->write($lineCount, $c, $line[$field['name']]);
				$c++;
			}
		}
		//new line
		$lineCount++;
	}
}





$lineCount++;

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
