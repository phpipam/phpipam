<?php

/***
 *	Generate XLS file for VLANs
 *********************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
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
$worksheet_name = "VLANs";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

# https://pear.php.net/manual/en/package.fileformats.spreadsheet-excel-writer.intro.php
# void Worksheet::write ( integer $row , integer $col , mixed $token , mixed $format=0 )

$curRow = 0;
$curColumn = 0;

//write headers
if ($GET->name == "on") {
	$worksheet->write($curRow, $curColumn, _('Name') ,$format_header);
	$curColumn++;
}
if ($GET->number == "on") {
	$worksheet->write($curRow, $curColumn, _('Number') ,$format_header);
	$curColumn++;
}
if ($GET->domain == "on") {
	$worksheet->write($curRow, $curColumn, _('Domain') ,$format_header);
	$curColumn++;
}
if ($GET->description == "on") {
	$worksheet->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;
}

//custom fields
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//set temp name - replace space with three ___
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

		if( $GET->{$myField['nameTemp']} == "on") {
			$worksheet->write($curRow, $curColumn, $myField['name'] ,$format_header);
			$curColumn++;
		}
	}
}


$curRow++;

//write Subnet entries for the selected sections

foreach ($vlan_domains as $vlan_domain) {
	//cast
	$vlan_domain = (array) $vlan_domain;

    $vldn = str_replace(" ", "_",$vlan_domain['name']);
    $vldn = str_replace(".", "_",$vldn);

	if ($GET->{'exportDomain__'.$vldn} == "on") {
		// get all VLANs in VLAN domain
		$all_vlans = $Admin->fetch_multiple_objects("vlans", "domainId", $vlan_domain['id'], "number");
		$all_vlans = (array) $all_vlans;
		// skip empty domains
		if (sizeof($all_vlans)==0) { continue; }
		//write all VLAN entries
		foreach ($all_vlans as $vlan) {
			//cast
			$vlan = array_merge(['name' => '', 'number' => null, 'description' => ''], (array) $vlan);

			//reset row count
			$curColumn = 0;

			if ($GET->name == "on") {
				$worksheet->write($curRow, $curColumn, $vlan['name'], $format_text);
				$curColumn++;
			}
			if ($GET->number == "on") {
				$worksheet->write($curRow, $curColumn, $vlan['number'], $format_text);
				$curColumn++;
			}
			if ($GET->domain == "on") {
				$worksheet->write($curRow, $curColumn, $vlan_domain['name'], $format_text);
				$curColumn++;
			}
			if ($GET->description == "on") {
				$worksheet->write($curRow, $curColumn, $vlan['description'], $format_text);
				$curColumn++;
			}


			//custom fields, per VLAN
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) {
					//set temp name - replace space with three ___
					$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

					if( $GET->{$myField['nameTemp']} == "on" ) {
						$worksheet->write($curRow, $curColumn, $vlan[$myField['name']], $format_text);
						$curColumn++;
					}
				}
			}

			$curRow++;
		}
	}
}

//new line
$curRow++;

//write domain sheet
if ($GET->exportVLANDomains == "on") {
	// Create a worksheet
	$worksheet_domains =& $workbook->addWorksheet('Domains');
	$worksheet_domains->setInputEncoding("utf-8");

	$curRow = 0;
	$curColumn = 0;

	//write headers
	$worksheet_domains->write($curRow, $curColumn, _('Name') ,$format_header);
	$curColumn++;
	$worksheet_domains->write($curRow, $curColumn, _('Description') ,$format_header);
	$curColumn++;

	$curRow++;
	$curColumn = 0;

	foreach ($vlan_domains as $vlan_domain) {
		//cast
		$vlan_domain = (array) $vlan_domain;

        $vldn = str_replace(" ", "_",$vlan_domain['name']);
        $vldn = str_replace(".", "_",$vldn);

		if( $GET->{'exportDomain__'. $vldn} == "on" ) {
			$worksheet_domains->write($curRow, $curColumn, $vlan_domain['name'], $format_text);
			$curColumn++;
			$worksheet_domains->write($curRow, $curColumn, $vlan_domain['description'], $format_text);
			$curColumn++;
		}

		$curRow++;
		$curColumn = 0;
	}
}

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
