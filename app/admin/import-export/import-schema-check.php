<?php

/*
 * Data import load
 *************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Admin)) { $Admin = new Admin ($Database); }
if (!isset($Tools)) { $Tools = new Tools ($Database); }


# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# check which sections we need to care about
$used_section = array();


# get all addresses in all subnets in all sections
$edata = array();

$schema = $Tools->fetch_all_objects("schemasubnets", "id");
$locsizes = $Tools->fetch_all_objects("locationsizes", "id");
$addtypes = $Tools->fetch_all_objects("addresstypes", "id");
$vrfs = $Tools->fetch_all_objects("vrf", "vrfId");
$vlandefs = $Tools->fetch_all_objects("vlandef", "id");

foreach ($schema as $d) {
    $d = (array) $d;
    $edata['schema'][strtolower($d['locationSize'])][strtolower($d['description'])] = $d;
}

foreach ($locsizes as $d) {
    $d = (array) $d;
    $edata['locsizes'][strtolower($d['locationSize'])] = $d;
}

foreach ($addtypes as $d) {
    $d = (array) $d;
    $edata['addtypes'][strtolower($d['addressType'])] = $d;
}

foreach ($vrfs as $d) {
    $d = (array) $d;
    $edata['vrfs'][strtolower($d['name'])] = $d;
}

foreach ($vlandefs as $d) {
    $d = (array) $d;
    $edata['vlandefs'][strtolower($d['vlanName'])] = $d;
}


$rows = "";
$counters = array();
$ndata = array(); # store new addresses in a similar format with edata for easier processing

# check the fields
foreach ($data as &$cdata) {
	$msg = ""; $action = ""; $cfieldtds = "";

	# check if required fields are present and not empty
	foreach($reqfields as $creq) {
		if ((!isset($cdata[$creq]) or ($cdata[$creq] == ""))) { $msg.= "Required field ".$creq." missing or empty."; $action = "error"; }
	}

	# Check if location size is provided and valid and link it if it is
	if (!isset($edata['locsizes'][strtolower($cdata['locationSize'])])
	    ) {
		$msg.= "Invalid location size."; $action = "error";
	} else {
		$cdata['locationSize'] = $edata['locsizes'][strtolower($cdata['locationSize'])]['id'];
	}
	
	# Check if address type is provided and valid and link it if it is
	if (!isset($edata['addtypes'][strtolower($cdata['addressType'])])
	    ) {
		$msg.= "Invalid address type."; $action = "error";
	} else {
		$cdata['addressType'] = $edata['addtypes'][strtolower($cdata['addressType'])]['id'];
	}
	
	# Check if vrf is provided and valid and link it if it is
	if (!isset($edata['vrfs'][strtolower($cdata['vrf'])])
	    ) {
		$cdata['vrf']=NULL;
	} else {
		$cdata['vrf'] = $edata['vrfs'][strtolower($cdata['vrf'])]['vrfId'];
	}
	
	# Check if vlan definition is provided and valid and link it if it is
	if (!isset($edata['vlandefs'][strtolower($cdata['vlanDef'])])
	    ) {
		$cdata['vlanDef'] =NULL;
	} else {
		$cdata['vlanDef'] = $edata['vlandefs'][strtolower($cdata['vlanDef'])]['id'];
	}

	# Check if isSummary is provided and valid and link it if it is
	if (strtolower($cdata['isSummary'])== "yes"){$cdata['isSummary']=1;}
	else {$cdata['isSummary'] =0;}

	# check if existing in database
	if ($action != "error") {
		if (isset($edata['schema'][strtolower($cdata['locationSize'])][strtolower($cdata['description'])]) ) {
    		$cdata['id'] = $edata['schema'][strtolower($cdata['locationSize'])][strtolower($cdata['description'])]['id'];
			# copy content to a variable for easier checks
			$cedata = $edata['schema'][strtolower($cdata['locationSize'])][strtolower($cdata['description'])];
			if (strtolower($cdata['parent'])=="none"){$cdata['parent']=0;}
			else {
				$schemas=$Tools->fetch_multiple_objects("schemasubnets","locationSize",$cdata['locationSize']);
				foreach ($schemas as $schema){
					if ($schema->description==$cdata['parent']){
						$cdata['parent']=$schema->id;
						break;
					}
				}
			}
			# Check if we need to change any fields
			$action = "skip"; # skip duplicate fields if identical, update if different
			# Should we just let the database decided to update or not?  Nice for UI, but alot of 
			# code maintaince here.
			if ($cdata['addressType'] != $cedata['addressType']) { $msg.= " AddressType will be updated."; $action = "edit"; }
			if ($cdata['vrf'] != $cedata['vrf']) { $msg.= " VRF will be updated."; $action = "edit"; }
			if ($cdata['isSummary'] != $cedata['isSummary']) { $msg.= " isSummary will be updated."; $action = "edit"; }
			if ($cdata['description'] != $cedata['description']) { $msg.= " description will be updated."; $action = "edit"; }
			if ($cdata['parent'] != $cedata['parent']) { $msg.= " Parent subnet will be updated."; $action = "edit"; }
			if ($cdata['mask'] != $cedata['mask']) { $msg.= " mask will be updated."; $action = "edit"; }
			if ($cdata['offset'] != $cedata['offset']) { $msg.= " offset will be updated."; $action = "edit"; }
			if ($cdata['base'] != $cedata['base']) { $msg.= " base will be updated."; $action = "edit"; }
			if ($cdata['vlanDef'] != $cedata['vlanDef']) { $msg.= " VLAN Definition will be updated."; $action = "edit"; }


			if ($action == "skip") { $msg.= "Duplicate, will skip."; }
		} else {
			$msg.="New entry, will be added."; $action = "add";
		}
	}

	$cdata['msg'].= $msg;
	$cdata['action'] = $action;
	$counters[$action]++;


	$rows.="<tr class='".$colors[$action]."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
	foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
	$rows.= "<td>"._($cdata['msg'])."</td></tr>";

}
