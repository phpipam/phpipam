<?php

/**
 *	Generate XLS file for Subnets
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
$custom_fields = $Tools->fetch_custom_fields('subnets');

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_subnets_export.xls";
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
$worksheet_name = "Subnets";
$worksheet =& $workbook->addWorksheet($worksheet_name);

$lineCount = 0;
$rowCount = 0;

//write headers
if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Section') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Subnet') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Description') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['VLAN'])) && ($_GET['VLAN'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('VLAN') ,$format_header);
	$rowCount++;
	$worksheet->write($lineCount, $rowCount, _('VLAN Domain') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['VRF'])) && ($_GET['VRF'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('VRF') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['master'])) && ($_GET['master'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Master Subnet') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['requests'])) && ($_GET['requests'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Requests') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['hostscheck'])) && ($_GET['hostscheck'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Host check') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['discover'])) && ($_GET['discover'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Discover') ,$format_header);
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

				if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
					$worksheet->write($lineCount, $rowCount, $section['name'], $format_text);
					$rowCount++;
				}

				if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
					$subnet_text = '';
                                        if ($subnet['isFolder']) {
						$subnet_text = $subnet['description']." (folder)";	
					} else {
						$subnet_text = $subnet['ip']."/".$subnet['mask'];
					}
					$worksheet->write($lineCount, $rowCount, $subnet_text, $format_text);
					$rowCount++;
				}

				if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
					$worksheet->write($lineCount, $rowCount, $subnet['description'], $format_text);
					$rowCount++;
				}

				if( (isset($_GET['VLAN'])) && ($_GET['VLAN'] == "on") ) {
					// get VLAN
					$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
					/* if(@$vlan[0]===false) 	{ $vlan['number'] = "NA"; $vlan['name'] = "NA"; }			# no VLAN
					$worksheet->write($lineCount, $rowCount, $vlan['number']." [".$vlan['name']."]", $format_text); */
					if(@$vlan[0]===false) 	{ $vlan['number'] = "NA"; }			# no VLAN
					$worksheet->write($lineCount, $rowCount, $vlan['number'], $format_text);
					$rowCount++;
					// VLAN Domain
					$vlan_domain = (array) $Tools->fetch_object("vlanDomains", "id", $vlan['domainId']);
					$worksheet->write($lineCount, $rowCount, $vlan_domain['name'], $format_text);
					$rowCount++;
				}

				if( (isset($_GET['VRF'])) && ($_GET['VRF'] == "on") ) {
					// get vrf
					if (!empty($subnet['vrfId'])) {
						$vrf = (array) $Tools->fetch_vrf(null, $subnet['vrfId']);
						$worksheet->write($lineCount, $rowCount, $vrf['name'], $format_text);
					} else {
						$worksheet->write($lineCount, $rowCount, '', $format_text);
					}
					$rowCount++;
				}

				if( (isset($_GET['master'])) && ($_GET['master'] == "on") ) {
					// get master subnet
					// zet - could optimize here and reference the already loaded subnets, with the help of a dictionary variable
					$masterSubnet = ( $subnet['masterSubnetId']==0 || empty($subnet['masterSubnetId']) ) ? false : true;
					if($masterSubnet) {
						$master = (array) $Subnets->fetch_subnet (null, $subnet['masterSubnetId']);
						if($master['isFolder']) {
							$worksheet->write($lineCount, $rowCount, $master['description']." [folder]", $format_text);
						} else {
							$worksheet->write($lineCount, $rowCount, $master['ip']."/".$master['mask'], $format_text);
						}
					} else {
						$worksheet->write($lineCount, $rowCount, "/", $format_text);
					}
					$rowCount++;
				}

				if( (isset($_GET['requests'])) && ($_GET['requests'] == "on") ) {
					$worksheet->write($lineCount, $rowCount, $subnet['allowRequests'], $format_text);
					$rowCount++;
				}

				if( (isset($_GET['hostscheck'])) && ($_GET['hostscheck'] == "on") ) {
					$worksheet->write($lineCount, $rowCount, $subnet['pingSubnet'], $format_text);
					$rowCount++;
				}

				if( (isset($_GET['discover'])) && ($_GET['discover'] == "on") ) {
					$worksheet->write($lineCount, $rowCount, $subnet['discoverSubnet'], $format_text);
					$rowCount++;
				}

				//custom fields, per subnet
				if(sizeof($custom_fields) > 0) {
					foreach($custom_fields as $myField) {
						//set temp name - replace space with three ___
						$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

						if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
							$worksheet->write($lineCount, $rowCount, $subnet[$myField['name']], $format_text);
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
