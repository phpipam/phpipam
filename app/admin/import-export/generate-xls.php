<?php

/**
 *	Generate XLS file
 *********************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');


# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools		= new Tools ($Database);
$Admin		= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();



// Create a workbook
$filename = "phpipam_IP_address_export_". date("Y-m-d") .".xls";
$workbook = new Spreadsheet_Excel_Writer();
$workbook->setVersion(8);

//fetch sections, and for each section write new tab, inside tab write all values!
$sections = $Sections->fetch_sections();

//we need to reformat state!
$ip_types = $Addresses->addresses_types_fetch();
//fetch devices and reorder
$devices = $Tools->fetch_all_objects("devices", "hostname");
$devices_indexed = array();
if ($devices!==false) {
    foreach($devices as $d) {
    	$devices_indexed[$d->id] = $d;
    }
}



//get all custom fields!
# fetch custom fields
$myFields = $Tools->fetch_custom_fields('ipaddresses');
$myFieldsSize = sizeof($myFields);

$colSize = 8 + $myFieldsSize;

//formatting headers
$format_header = $workbook->addFormat();
$format_header->setBold();
$format_header->setColor('white');
$format_header->setFgColor('black');

//formatting titles
$format_title = $workbook->addFormat();
$format_title->setColor('black');
$format_title->setFgColor(22);			//light gray
$format_title->setBottom(2);
$format_title->setLeft(1);
$format_title->setRight(1);
$format_title->setTop(1);
$format_title->setAlign('left');

//formatting content - borders around IP addresses
$format_right = $workbook->addFormat();
$format_right->setRight(1);
$format_left = $workbook->addFormat();
$format_left->setLeft(1);
$format_top = $workbook->addFormat();
$format_top->setTop(1);


foreach ($sections as $section) {
	//cast
	$section = (array) $section;
	// Create a worksheet
	$worksheet_name = $Tools->shorten_text($section['name'], 30);
	$worksheet =& $workbook->addWorksheet($worksheet_name);
	$worksheet->setInputEncoding("utf-8");

	//get all subnets in this section
	$subnets = $Subnets->fetch_section_subnets ($section['id']);

	$lineCount = 0;
	//Write titles
	foreach ($subnets as $subnet) {
		//cast
		$subnet = (array) $subnet;
		//ignore folders!
		if($subnet['isFolder']!="1") {
			//vlan details
			$vlan = (array) $Tools->fetch_object("vlans", "vlanId", $subnet['vlanId']);
			if(!is_blank($vlan['number'])) {
				$vlanText = " (vlan: " . $vlan['number'];
				if(!is_blank($vlan['name'])) {
					$vlanText .= ' - '. $vlan['name'] . ')';
				}
				else {
					$vlanText .= ")";
				}
			}
			else {
				$vlanText = "";
			}

			$worksheet->write($lineCount, 0, $Subnets->transform_to_dotted($subnet['subnet']) . "/" .$subnet['mask'] . " - " . $subnet['description'] . $vlanText, $format_header );
			$worksheet->mergeCells($lineCount, 0, $lineCount, $colSize);

			$lineCount++;

			//IP addresses in subnet
			$ipaddresses = $Addresses->fetch_subnet_addresses ($subnet['id']);

			//write headers
			$worksheet->write($lineCount, 0, _('ip address' ),$format_title);
			$worksheet->write($lineCount, 1, _('ip state' ),$format_title);
			$worksheet->write($lineCount, 2, _('description' ),$format_title);
			$worksheet->write($lineCount, 3, _('hostname' ),$format_title);
			$worksheet->write($lineCount, 4, _('mac' ),$format_title);
			$worksheet->write($lineCount, 5, _('owner' ),$format_title);
			$worksheet->write($lineCount, 6, _('device' ),$format_title);
			$worksheet->write($lineCount, 7, _('port' ),$format_title);
			$worksheet->write($lineCount, 8, _('note' ),$format_title);
			$m = 9;
			//custom
			if(sizeof($myFields) > 0) {
				foreach($myFields as $myField) {
					$worksheet->write($lineCount, $m, $myField['name'] ,$format_title);
					$m++;
				}
			}

			$lineCount++;

			if(is_array($ipaddresses) && sizeof($ipaddresses) > 0) {

			foreach ($ipaddresses as $ip) {
				//cast
				$ip = (array) $ip;

				//reformat state
				if(@$ip_types[$ip['state']]['showtag']==1) 	{ $ip['state'] = $ip_types[$ip['state']]['type']; }
				else										{ $ip['state'] = ""; }

				//change switch ID to name
				$ip['switch'] = is_null($ip['switch'])||is_blank($ip['switch'])||$ip['switch']==0 ? "" : $devices_indexed[$ip['switch']]->hostname;

				$worksheet->write($lineCount, 0, $Subnets->transform_to_dotted($ip['ip_addr']), $format_left);
				$worksheet->write($lineCount, 1, $ip['state']);
				$worksheet->write($lineCount, 2, $ip['description']);
				$worksheet->write($lineCount, 3, $ip['hostname']);
				$worksheet->write($lineCount, 4, $ip['mac']);
				$worksheet->write($lineCount, 5, $ip['owner']);
				$worksheet->write($lineCount, 6, $ip['switch']);
				$worksheet->write($lineCount, 7, $ip['port']);
				$worksheet->write($lineCount, 8, $ip['note']);
				//custom
				$m = 9;
				if(sizeof($myFields) > 0) {
					foreach($myFields as $myField) {
						$worksheet->write($lineCount, $m, $ip[$myField['name']]);
						$m++;
					}
				}

				$lineCount++;
			}

		}
			else {
				$worksheet->write($lineCount, 0, _('No hosts'));
				$lineCount++;
			}

			//new line
			$lineCount++;
		}
	}
}

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();
