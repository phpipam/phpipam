<?php

/***
 *	Generate XLS file for VLANs
 *********************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	    = new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('vlans');

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_VLAN_export.xls";
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
$worksheet_name = "VLANs";
$worksheet =& $workbook->addWorksheet($worksheet_name);

$lineCount = 0;
$rowCount = 0;

//write headers
if( (isset($_GET['name'])) && ($_GET['name'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Name') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['number'])) && ($_GET['number'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Number') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['domain'])) && ($_GET['domain'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Domain') ,$format_header);
	$rowCount++;
}
if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
	$worksheet->write($lineCount, $rowCount, _('Description') ,$format_header);
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


$lineCount++;

//write Subnet entries for the selected sections
foreach ($vlan_domains as $vlan_domain) {
	//cast
	$vlan_domain = (array) $vlan_domain;

	if( (isset($_GET['exportDomain__'.str_replace(" ", "_",$vlan_domain['name'])])) && ($_GET['exportDomain__'.str_replace(" ", "_",$vlan_domain['name'])] == "on") ) {
		// get all VLANs in VLAN domain
		$all_vlans = $Admin->fetch_multiple_objects("vlans", "domainId", $vlan_domain['id'], "number");
		$all_vlans = (array) $all_vlans;
		// skip empty domains
		if (sizeof($all_vlans)==0) { continue; }
		//write all VLAN entries
		foreach ($all_vlans as $vlan) {
			//cast
			$vlan = (array) $vlan;

			//reset row count
			$rowCount = 0;

			if( (isset($_GET['name'])) && ($_GET['name'] == "on") ) {
				$worksheet->write($lineCount, $rowCount, $vlan['name'], $format_text);
				$rowCount++;
			}
			if( (isset($_GET['number'])) && ($_GET['number'] == "on") ) {
				$worksheet->write($lineCount, $rowCount, $vlan['number'], $format_text);
				$rowCount++;
			}
			if( (isset($_GET['domain'])) && ($_GET['domain'] == "on") ) {
				$worksheet->write($lineCount, $rowCount, $vlan_domain['name'], $format_text);
				$rowCount++;
			}
			if( (isset($_GET['description'])) && ($_GET['description'] == "on") ) {
				$worksheet->write($lineCount, $rowCount, $vlan['description'], $format_text);
				$rowCount++;
			}

			//custom fields, per VLAN
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) {
					//set temp name - replace space with three ___
					$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

					if( (isset($_GET[$myField['nameTemp']])) && ($_GET[$myField['nameTemp']] == "on") ) {
						$worksheet->write($lineCount, $rowCount, $vlan[$myField['name']], $format_text);
						$rowCount++;
					}
				}
			}

			$lineCount++;
		}
	}
}

//new line
$lineCount++;

//write domain sheet
if( (isset($_GET['exportVLANDomains'])) && ($_GET['exportVLANDomains'] == "on") ) {
	// Create a worksheet
	$worksheet_domains =& $workbook->addWorksheet('Domains');

	$lineCount = 0;
	$rowCount = 0;

	//write headers
	$worksheet_domains->write($lineCount, $rowCount, _('Name') ,$format_header);
	$rowCount++;
	$worksheet_domains->write($lineCount, $rowCount, _('Description') ,$format_header);
	$rowCount++;

	$lineCount++;
	$rowCount = 0;

	foreach ($vlan_domains as $vlan_domain) {
		//cast
		$vlan_domain = (array) $vlan_domain;

		if( (isset($_GET['exportDomain__'.str_replace(" ", "_",$vlan_domain['name'])])) && ($_GET['exportDomain__'.str_replace(" ", "_",$vlan_domain['name'])] == "on") ) {
			$worksheet_domains->write($lineCount, $rowCount, $vlan_domain['name'], $format_text);
			$rowCount++;
			$worksheet_domains->write($lineCount, $rowCount, $vlan_domain['description'], $format_text);
			$rowCount++;
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