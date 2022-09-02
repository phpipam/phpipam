<?php

/***
 *	Generate XLS file for L2 domains
 *********************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require( dirname(__FILE__) . '/../../../functions/PEAR/Spreadsheet/Excel/Writer.php');

# initialize required objects
$Database	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Admin		= new Admin ($Database);
$Tools		= new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# get all data
$all_bgp_entries      = $Tools->fetch_all_objects ("routing_bgp", "peer_name", true);
$all_vrf_entries      = $Tools->fetch_all_objects ("vrf", "vrfId", true);
$all_curcuit_entries  = $Tools->fetch_all_objects ("circuits", "cid", true);
$all_customer_entries = $Tools->fetch_all_objects ("customers", "id", true);

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('routing_bgp');
# prepare HTML variables
$fields = array ( 'id', 'local_as', 'local_address', 'peer_name', 'peer_address', 'bgp_type', 'vrf', 'circuit', 'customer', 'description' );

if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//change spaces to "___" so it can be used as element id
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);
		array_push ( $fields, $myField['nameTemp'] );
	}
}

# reindex
if ($all_vrf_entries!==false) {
	foreach ($all_vrf_entries as $d) {
		$d = (array) $d;
		$vrf_ids[$d['vrfId']] = $d;
	}
}
if ($all_curcuit_entries!==false) {
	foreach ($all_curcuit_entries as $d) {
	    $d = (array) $d;
	    $circuit_ids[$d['id']] = $d;
	}
}
if ($all_customer_entries!==false) {
	foreach ($all_customer_entries as $d) {
	    $d = (array) $d;
	    $customer_ids[$d['id']] = $d;
	}
}

# Create a workbook
$today = date("Ymd");
$filename = $today."_phpipam_bgp_export.xls";
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
$worksheet_name = "BGP routing";
$worksheet =& $workbook->addWorksheet($worksheet_name);
$worksheet->setInputEncoding("utf-8");

$curRow = 0;
$curColumn = 0;

foreach ($fields as $k) {
    $worksheet->write($curRow, $curColumn, ucwords(str_replace("_"," ",_($k))) ,$format_header);
    $curColumn++;
}

$curRow++;
$curColumn = 0;

foreach ($all_bgp_entries as $d) {
	//cast
	$d = (array) $d;
	// replace ids
	$d['vrf']     = $vrf_ids[$d['vrf_id']]['name'];
	$d['circuit'] = $circuit_ids[$d['circuit_id']]['cid'];
	$d['customer'] = $customer_ids[$d['customer_id']]['title'];

    foreach ($fields as $k) {
        $worksheet->write($curRow, $curColumn, $d[$k], $format_text);
        $curColumn++;
    }

	$curRow++;
	$curColumn = 0;
}

// sending HTTP headers
$workbook->send($filename);

// Let's send the file
$workbook->close();