<?php

/**
 *	Generate XLS file for IP Addresses
 ************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Tools	    = new Tools ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Sections	= new Sections ($Database);

# verify that user is logged in
$User->check_user_session();

# Won't check per subnet/section rights since this is an admin section, where the admin user has full access

# fetch all sections
$all_sections = $Sections->fetch_all_sections();

# Lets do some reordering to show slaves!
if($all_sections !== false) {
	foreach($all_sections as $s) {
		if($s->masterSection=="0") {
			# it is master
			$s->class = "master";
			$sectionssorted[] = $s;
			# check for slaves
			foreach($all_sections as $ss) {
				if($ss->masterSection==$s->id) {
					$ss->class = "slave";
					$sectionssorted[] = $ss;
				}
			}
		}
	}
	# set new array
	$sections_sorted = @$sectionssorted;
}


# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('ipaddresses');

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_ip_address_export.xls";
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
$worksheet_name = "IP Addresses";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;

//write headers
if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Section') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['ip_addr'])) && ($_GET['ip_addr'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('IP Address') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['hostname'])) && ($_GET['hostname'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Hostname') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['vrf'])) && ($_GET['vrf'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('VRF') ,$format_header);
	$curColumn++;
	# fetch all VRFs
	$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId");
	if (!$all_vrfs) { $all_vrfs = array(); }
	# prepare list for easy processing
	$vrfs = array(); $vrfs[0] = "default";
	foreach ($all_vrfs as $vrf) { $vrf = (array) $vrf; $vrfs[$vrf['vrfId']] = $vrf['name']; }
}
if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Subnet') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['mac'])) && ($_GET['mac'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('MAC') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['owner'])) && ($_GET['owner'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Owner') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['device'])) && ($_GET['device'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Device') ,$format_header);
	$curColumn++;
	# get Devices and reorder
	$devices = $Tools->fetch_all_objects ("devices", "hostname");
	$devices_indexed = array();
	if ($devices!==false) {
	foreach($devices as $d) {
    		$devices_indexed[$d->id] = $d;
    	}
	}
}
if( (isset($_GET['note'])) && ($_GET['note'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Note') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['tag'])) && ($_GET['tag'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Tag') ,$format_header);
	$curColumn++;
	# get IP address types
	$ip_types = $Addresses->addresses_types_fetch();
}
if( (isset($_GET['gateway'])) && ($_GET['gateway'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Gateway') ,$format_header);
	$curColumn++;
}

//custom fields
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//set temp name - replace space with three ___
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

		if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
			$worksheet->write($curRow, $curColumn, $myField['name'] ,$format_header);
			$curColumn++;
		}
	}
}

$curColumn = 0;
$curRow++;

//write Subnet entries for the selected sections
if($all_sections!==false) {
	foreach ($all_sections as $section) {
		//cast
		$section = (array) $section;
		$section['url_name'] = urlencode($section['id']);

		if( (isset($_GET['exportSection__'.$section['url_name']])) && ($_GET['exportSection__'.$section['url_name']] == "on") ) {
			// get all subnets in section
			$section_subnets = $Subnets->fetch_section_subnets($section['id']);

			if (!is_array($section_subnets)) { continue; }

			foreach ($section_subnets as $subnet) {

				$subnet = (array) $subnet;

				// ignore folders
				if($subnet['isFolder']) { continue; }

				// grab IP addresses
				$ipaddresses = $Addresses->fetch_subnet_addresses ($subnet['id']);

				if (!is_array($ipaddresses) || sizeof($ipaddresses)==0) { continue; }

				foreach ($ipaddresses as $ip) {

					//cast
					$ip = (array) $ip;


					if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $section['name'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['ip_addr'])) && ($_GET['ip_addr'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $Subnets->transform_to_dotted($ip['ip_addr']), $format_text);
						$curColumn++;
					}

					if( (isset($_GET['hostname'])) && ($_GET['hostname'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $ip['hostname'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $ip['description'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['vrf'])) && ($_GET['vrf'] == "on") ) {
						if (!isset($vrfs[$subnet['vrfId']])) {
							$vrfs[$subnet['vrfId']] = "";
						}
						$worksheet->write($curRow, $curColumn, $vrfs[$subnet['vrfId']], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $subnet['ip']."/".$subnet['mask'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['state'])) && ($_GET['state'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $ip['state'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['mac'])) && ($_GET['mac'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $ip['mac'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['owner'])) && ($_GET['owner'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $ip['owner'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['device'])) && ($_GET['device'] == "on") ) {
						//change device to name
						$ip['device'] = is_null($ip['switch'])||is_blank($ip['switch'])||$ip['switch']==0 ? "" : $devices_indexed[$ip['switch']]->hostname;
						$worksheet->write($curRow, $curColumn, $ip['device'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['note'])) && ($_GET['note'] == "on") ) {
						$worksheet->write($curRow, $curColumn, $ip['note'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['tag'])) && ($_GET['tag'] == "on") ) {
						//reformat tag
						$ip['tag'] = (@$ip_types[$ip['state']]['showtag']) ? $ip_types[$ip['state']]['type'] : "";
						$worksheet->write($curRow, $curColumn, $ip['tag'], $format_text);
						$curColumn++;
					}

					if( (isset($_GET['gateway'])) && ($_GET['gateway'] == "on") ) {
						$ip['gateway'] = ($ip['is_gateway']) ? _("Yes") : _("No");
						$worksheet->write($curRow, $curColumn, $ip['gateway'], $format_text);
						$curColumn++;
					}

					//custom fields, per subnet
					if(sizeof($custom_fields) > 0) {
						foreach($custom_fields as $myField) {
							//set temp name - replace space with three ___
							$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

							if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
								$worksheet->write($curRow, $curColumn, $ip[$myField['name']], $format_text);
								$curColumn++;
							}
						}
					}

				//reset row count
				$curColumn = 0;
				$curRow++;
				}
			}
		}
	}
}
//new line
$curRow++;

//write section sheet
if( (isset($_GET['exportSections'])) && ($_GET['exportSections'] == "on") ) {
	// Create a worksheet
	$worksheet_sections =& $workbook->addWorksheet('Sections');

	$curRow = 0;
	$curColumn = 0;

	//write headers
	$worksheet_sections->write($curRow, $curColumn, _('Name') ,$format_header);
	$curColumn++;
	$worksheet_sections->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
	$worksheet_sections->write($curRow, $curColumn, _('Parent') ,$format_header);
	$curColumn++;

	$curRow++;
	$curColumn = 0;

	foreach ($sections_sorted as $section) {
		//cast
		$section = (array) $section;
		$section['url_name'] = urlencode($section['id']);

		if( (isset($_GET['exportSection__'.$section['url_name']])) && ($_GET['exportSection__'.$section['url_name']] == "on") ) {
			$worksheet_sections->write($curRow, $curColumn, $section['name'], $format_text);
			$curColumn++;
			$worksheet_sections->write($curRow, $curColumn, $section['description'], $format_text);
			$curColumn++;
			//master Section
			if($section['masterSection']!=0) {
				# get section details
				$ssec = $Admin->fetch_object("sections", "id", $section['masterSection']);
				$worksheet_sections->write($curRow, $curColumn, $ssec->name, $format_text);
				$curColumn++;
			} else {
				$worksheet_sections->write($curRow, $curColumn, "/", $format_text);
				$curColumn++;
			}
		}

		$curRow++;
		$curColumn = 0;
	}
}

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
