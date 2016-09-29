<?php

/*
 *	Script to inserte imported file to database!
 **********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result;

# verify that user is logged in
$User->check_user_session();

# permissions
$permission = $Subnets->check_permission ($User->user, $_POST['subnetId']);

# die if write not permitted
if($permission < 2) 			   $Result->show("danger", _('You cannot write to this subnet'), true);
# check integer
is_numeric($_POST['subnetId']) ? : $Result->show("danger", _("Invalid subnet ID") ,true);

# set filetype
$filetype = explode(".", $_POST['filetype']);
$filetype = end($filetype);

# get custom fields
$custom_address_fields = $Tools->fetch_custom_fields('ipaddresses');

# fetch subnet
$subnet = $Subnets->fetch_subnet("id",$_POST['subnetId']);
if($subnet===false)                $Result->show("danger", _("Invalid subnet ID") ,true);

# Parse file
$outFile = $Tools->parse_import_file ($filetype, $subnet, $custom_address_fields);

# Fetch all devices
$devices = $Tools->fetch_all_objects("devices", "hostname");

# cnt
$edit = 0;
$add  = 0;
$invalid_lines = array();
$errors = 0;

# import each value
foreach($outFile as $k=>$line) {

    // if not error
    if ($line['class']!="danger" || ($line['class']=="danger" && @$_POST['ignoreError']=="1")) {

		// reformat IP state from name to id
		$line[1] = $Addresses->address_type_type_to_index($line[1]);

		// reformat device from name to id
		if(strlen($line[7])>0) {
    		if ($devices!==false) {
        		foreach($devices as $d) {
        			if($d->hostname==$line[7])	{ $line[7] = $d->id; }
        		}
    		}
    		else {
        		$line[7] = 0;
    		}
		}
		else {
    		$line[7] = 0;
		}

		// set action
		if($id = $Addresses->address_exists ($line[0], $_POST['subnetId'], false))	{ $action = "edit"; }
		else																		{ $action = "add"; }

		// set insert / update values
		$address_insert = array("action"=>$action,
								"subnetId"=>$_POST['subnetId'],
								"ip_addr"=>$line[0],
								"state"=>$Addresses->address_type_type_to_index($line[1]),
								"description"=>$line[2],
								"dns_name"=>$line[3],
								"firewallAddressObject"=>$line[4],
								"mac"=>$line[5],
								"owner"=>$line[6],
								"switch"=>$line[7],
								"port"=>$line[8],
								"note"=>$line[9]
								);
		// add id
		if ($action=="edit")	{ $address_insert["id"] = $id; }
        // custom fields
        // Incorrect Value for $currIndex = 10;
        $currIndex = 9;
        if(sizeof($custom_address_fields) > 0) {
        	foreach($custom_address_fields as $field) {
            	$currIndex++;
        		$address_insert[$field['name']] = $line[$currIndex];
        	}
        }

		// insert
		if($Addresses->modify_address ($address_insert)===false)	{ $errors++; }
		else {
			if ($action=="edit")	{ $edit++; }
			else 					{ $add++; }
		}
    }
    else {
        $invalid_lines[] = $line;
    }
}

# print success if no errors
if($errors==0)	{
	$Result->show("success", _('Import successfull'), false);
	# erase file on success
	unlink('upload/import.'.$filetype);
}

# print
$Result->show("success", _("Created $add addresses, skipped ".sizeof($invalid_lines)." entries and edited $edit addresses"), false);

print "<br><br>";
?>
