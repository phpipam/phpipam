<?php

/**
 *	Generate XLS file for IP Addresses
 ************************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );
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

$lineCount = 0;
$rowCount = 0;

//write headers
if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Section') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['ip_addr'])) && ($_GET['ip_addr'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('IP Address') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['dns_name'])) && ($_GET['dns_name'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Hostname') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Description') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Subnet') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['mac'])) && ($_GET['mac'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('MAC') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['owner'])) && ($_GET['owner'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Owner') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['device'])) && ($_GET['device'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Device') ,$format_header);
	$rowCount++;
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
	$worksheet->write($lineCount, $rowCount, _('Note') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['tag'])) && ($_GET['tag'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Tag') ,$format_header);
	$rowCount++;
	# get IP address types
	$ip_types = $Addresses->addresses_types_fetch();
}
if( (isset($_GET['gateway'])) && ($_GET['gateway'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Gateway') ,$format_header);
	$rowCount++;
}

//custom fields
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//set temp name - replace space with three ___
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

		if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
			$worksheet->write($lineCount, $rowCount, $myField['name'] ,$format_header);
			$rowCount++;
		}
	}
}

$rowCount = 0;
$lineCount++;

//write Subnet entries for the selected sections
if($all_sections!==false) {
	foreach ($all_sections as $section) {
		//cast
		$section = (array) $section;

		if( (isset($_GET['exportSection__'.$section['name']])) && ($_GET['exportSection__'.$section['name']] == "on") ) {
			// get all subnets in section
			$section_subnets = $Subnets->fetch_section_subnets($section['id']);

			if (sizeof($section_subnets)==0) { continue; }

			foreach ($section_subnets as $subnet) {

				$subnet = (array) $subnet;

				// ignore folders
				if($subnet['isFolder']) { continue; }

				// grab IP addresses
				$ipaddresses = $Addresses->fetch_subnet_addresses ($subnet['id']);

				if (sizeof($ipaddresses)==0) { continue; }

				foreach ($ipaddresses as $ip) {

					//cast
					$ip = (array) $ip;


					if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $section['name'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['ip_addr'])) && ($_GET['ip_addr'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $Subnets->transform_to_dotted($ip['ip_addr']), $format_text);
						$rowCount++;
					}

					if( (isset($_GET['dns_name'])) && ($_GET['dns_name'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $ip['dns_name'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $ip['description'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $subnet['ip']."/".$subnet['mask'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['state'])) && ($_GET['state'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $ip['state'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['mac'])) && ($_GET['mac'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $ip['mac'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['owner'])) && ($_GET['owner'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $ip['owner'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['device'])) && ($_GET['device'] == "on") ) {
						//change device to name
						$ip['device'] = is_null($ip['switch'])||strlen($ip['switch'])==0||$ip['switch']==0 ? "" : $devices_indexed[$ip['switch']]->hostname;
						$worksheet->write($lineCount, $rowCount, $ip['device'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['note'])) && ($_GET['note'] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $ip['note'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['tag'])) && ($_GET['tag'] == "on") ) {
						//reformat tag
						$ip['tag'] = (@$ip_types[$ip['state']]['showtag']) ? $ip_types[$ip['state']]['type'] : "";
						$worksheet->write($lineCount, $rowCount, $ip['tag'], $format_text);
						$rowCount++;
					}

					if( (isset($_GET['gateway'])) && ($_GET['gateway'] == "on") ) {
						$ip['gateway'] = ($ip['is_gateway']) ? _("Yes") : _("No");
						$worksheet->write($lineCount, $rowCount, $ip['gateway'], $format_text);
						$rowCount++;
					}

					//custom fields, per subnet
					if(sizeof($custom_fields) > 0) {
						foreach($custom_fields as $myField) {
							//set temp name - replace space with three ___
							$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

							if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
								$worksheet->write($lineCount, $rowCount, $ip[$myField['name']], $format_text);
								$rowCount++;
							}
						}
					}

				//reset row count
				$rowCount = 0;
				$lineCount++;
				}
			}
		}
	}
}
//new line
$lineCount++;

//write section sheet
if( (isset($_GET['exportSections'])) && ($_GET['exportSections'] == "on") ) {
	// Create a worksheet
	$worksheet_sections =& $workbook->addWorksheet('Sections');

	$lineCount = 0;
	$rowCount = 0;

	//write headers
	$worksheet_sections->write($lineCount, $rowCount, _('Name') ,$format_header);
	$rowCount++;
	$worksheet_sections->write($lineCount, $rowCount, _('Description') ,$format_header);
	$rowCount++;
	$worksheet_sections->write($lineCount, $rowCount, _('Parent') ,$format_header);
	$rowCount++;

	$lineCount++;
	$rowCount = 0;

	foreach ($sections_sorted as $section) {
		//cast
		$section = (array) $section;

		if( (isset($_GET['exportSection__'.str_replace(" ", "_", $section['name'])])) && ($_GET['exportSection__'.str_replace(" ", "_", $section['name'])] == "on") ) {
			$worksheet_sections->write($lineCount, $rowCount, $section['name'], $format_text);
			$rowCount++;
			$worksheet_sections->write($lineCount, $rowCount, $section['description'], $format_text);
			$rowCount++;
			//master Section
			if($section['masterSection']!=0) {
				# get section details
				$ssec = $Admin->fetch_object("sections", "id", $section['masterSection']);
				$worksheet_sections->write($lineCount, $rowCount, $ssec->name, $format_text);
				$rowCount++;
			} else {
				$worksheet_sections->write($lineCount, $rowCount, "/", $format_text);
				$rowCount++;
			}
		}

		$lineCount++;
		$rowCount = 0;
	}
}

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();

?>
