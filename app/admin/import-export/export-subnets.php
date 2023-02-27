<?php

/**
 *	Generate XLS file for Subnets
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
$worksheet_name = "Subnets";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;

//write headers
if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Section') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Subnet') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['VLAN'])) && ($_GET['VLAN'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('VLAN') ,$format_header);
	$curColumn++;
	$worksheet->write($curRow, $curColumn, _('VLAN Domain') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['VRF'])) && ($_GET['VRF'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('VRF') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['master'])) && ($_GET['master'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Master Subnet') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['requests'])) && ($_GET['requests'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Requests') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['hostscheck'])) && ($_GET['hostscheck'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Host check') ,$format_header);
	$curColumn++;
}
if( (isset($_GET['discover'])) && ($_GET['discover'] == "on") ) {
	$worksheet->write($curRow, $curColumn, _('Discover') ,$format_header);
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

				if( (isset($_GET['section'])) && ($_GET['section'] == "on") ) {
					$worksheet->write($curRow, $curColumn, $section['name'], $format_text);
					$curColumn++;
				}

				if( (isset($_GET['subnet'])) && ($_GET['subnet'] == "on") ) {
					$subnet_text = '';
                                        if ($subnet['isFolder']) {
						$subnet_text = $subnet['description']." (folder)";
					} else {
						$subnet_text = $subnet['ip']."/".$subnet['mask'];
					}
					$worksheet->write($curRow, $curColumn, $subnet_text, $format_text);
					$curColumn++;
				}

				if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
					$worksheet->write($curRow, $curColumn, $subnet['description'], $format_text);
					$curColumn++;
				}

				if( (isset($_GET['VLAN'])) && ($_GET['VLAN'] == "on") ) {
					// get VLAN
					$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
					$vlan = array_merge(['number' => "NA", 'domainId' => null], $vlan);

					$worksheet->write($curRow, $curColumn, $vlan['number'], $format_text);
					$curColumn++;
					// VLAN Domain
					$vlan_domain = (array) $Tools->fetch_object("vlanDomains", "id", $vlan['domainId']);
					$vlan_domain = array_merge(['name' => ""], $vlan_domain);

					$worksheet->write($curRow, $curColumn, $vlan_domain['name'], $format_text);
					$curColumn++;
				}

				if( (isset($_GET['VRF'])) && ($_GET['VRF'] == "on") ) {
					// get vrf
					if (!empty($subnet['vrfId'])) {
						$vrf = (array) $Tools->fetch_object("vrf", "vrfId", $subnet['vrfId']);
						$worksheet->write($curRow, $curColumn, $vrf['name'], $format_text);
					} else {
						$worksheet->write($curRow, $curColumn, '', $format_text);
					}
					$curColumn++;
				}

				if( (isset($_GET['master'])) && ($_GET['master'] == "on") ) {
					// get master subnet
					// zet - could optimize here and reference the already loaded subnets, with the help of a dictionary variable
					$masterSubnet = ( $subnet['masterSubnetId']==0 || empty($subnet['masterSubnetId']) ) ? false : true;
					if($masterSubnet) {
						$master = (array) $Subnets->fetch_subnet (null, $subnet['masterSubnetId']);
						if($master['isFolder']) {
							$worksheet->write($curRow, $curColumn, $master['description']." [folder]", $format_text);
						} else {
							$worksheet->write($curRow, $curColumn, $master['ip']."/".$master['mask'], $format_text);
						}
					} else {
						$worksheet->write($curRow, $curColumn, "/", $format_text);
					}
					$curColumn++;
				}

				if( (isset($_GET['requests'])) && ($_GET['requests'] == "on") ) {
					$worksheet->write($curRow, $curColumn, $subnet['allowRequests'], $format_text);
					$curColumn++;
				}

				if( (isset($_GET['hostscheck'])) && ($_GET['hostscheck'] == "on") ) {
					$worksheet->write($curRow, $curColumn, $subnet['pingSubnet'], $format_text);
					$curColumn++;
				}

				if( (isset($_GET['discover'])) && ($_GET['discover'] == "on") ) {
					$worksheet->write($curRow, $curColumn, $subnet['discoverSubnet'], $format_text);
					$curColumn++;
				}

				//custom fields, per subnet
				if(sizeof($custom_fields) > 0) {
					foreach($custom_fields as $myField) {
						//set temp name - replace space with three ___
						$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

						if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
							$worksheet->write($curRow, $curColumn, $subnet[$myField['name']], $format_text);
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
