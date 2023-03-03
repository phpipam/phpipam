<?php

/**
 * Script to check edited / deleted / new IP addresses
 * If all is ok write to database
 *************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Log 		= new Logging ($Database, $User->settings);
$Zones 		= new FirewallZones($Database);
$Ping		= new Scan ($Database);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
if($_POST['action']=="add") {
	$User->Crypto->csrf_cookie ("validate", "address_add", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
}
else {
	$User->Crypto->csrf_cookie ("validate", "address_".$_POST['id'], $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
}

# validate action
$Tools->validate_action ($_POST['action']);
$action = $_POST['action'];
//reset delete action form visual visual
if(isset($_POST['action-visual'])) {
	if(@$_POST['action-visual'] == "delete") { $action = "delete"; }
}

# save $_POST to $address
$address = $_POST;


// set selected address and required addresses fields array
$selected_ip_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);
$required_ip_fields = $Tools->explode_filtered(";", $User->settings->IPrequired);
// append one missing from selected
$selected_ip_fields[] = "description";
$selected_ip_fields[] = "hostname";
// if field is present in required fields but not in selected remove it !
foreach ($required_ip_fields as $k=>$f) {
	if (!in_array($f, $selected_ip_fields)) {
		unset ($required_ip_fields[$k]);
	}
}
// checks
if(is_array($required_ip_fields) && $action!="delete") {
	// remove modules not enabled from required fields
	if($User->settings->enableLocations=="0") { unset($required_ip_fields['location']); }

	// set default array
	$required_field_errors = array();
	// Check that all required fields are present
	foreach ($required_ip_fields as $required_field) {
		if (!isset($address[$required_field]) || is_blank($address[$required_field])) {
			$required_field_errors[] = ucwords($required_field)." "._("is required");
		}
	}
	// check
	if(sizeof($required_field_errors)>0) {
		array_unshift($required_field_errors, _("Please fix following errors:"));
		$Result->show("danger", implode("<br> - ", $required_field_errors), true);
	}
}


# remove all spaces in hostname
if (!is_blank($address['hostname'])) { $address['hostname'] = str_replace(" ", "", $address['hostname']); }

# required fields
isset($address['action']) ?:		$Result->show("danger", _("Missing required fields"). " action", true);
isset($address['subnet']) ?:		$Result->show("danger", _("Missing required fields"). " subnet", true);
isset($address['subnetId']) ?:		$Result->show("danger", _("Missing required fields"). " subnetId", true);
isset($address['id']) ?:			$Result->show("danger", _("Missing required fields"). " id", true);

# ptr
if(!isset($address['PTRignore']))	$address['PTRignore']=0;

# generate firewall address object name
$firewallZoneSettings = pf_json_decode($User->settings->firewallZoneSettings, true);
if (isset($firewallZoneSettings['autogen']) && $firewallZoneSettings['autogen'] == 'on') {
	if ($address['action'] == 'add' ) {
		$address['firewallAddressObject'] = $Zones->generate_address_object($address['subnetId'],$address['hostname']);
	} else {
		if ($_POST['firewallAddressObject']) {
			$address['firewallAddressObject'] = $_POST['firewallAddressObject'];
		} else {
			$address['firewallAddressObject'] = NULL ;
		}
	}
}

# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $address['subnetId']);
$subnet_permission > 1 ?:		$Result->show("danger", _('Cannot edit IP address'), true);

# fetch subnet
$subnet = (array) $Subnets->fetch_subnet(null, $address['subnetId']);
if (@$_POST['verifydatabase']!=="yes")
sizeof($subnet)>0 ?:			$Result->show("danger", _("Invalid subnet"), true);

# replace empty fields with nulls
$address = $Addresses->reformat_empty_array_fields ($address, null);

# custom fields and checks
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
if(sizeof($custom_fields) > 0 && $action!="delete") {
	foreach($custom_fields as $field) {
		# replace possible ___ back to spaces!
		$field['nameTest']      = str_replace(" ", "___", $field['name']);

		if(isset($address[$field['nameTest']])) { $address[$field['name']] = $address[$field['nameTest']];}
		# booleans can be only 0 and 1
		if($field['type']=="tinyint(1)") {
			if($address[$field['name']]>1) {
				$address[$field['name']] = "";
			}
		}
		# null custom fields not permitted
		if($field['Null']=="NO" && is_blank($address[$field['name']])) {
			$Result->show("danger", $field['name']." "._("can not be empty!"), true);
		}
	}
}

# we need old address details for mailing or if we are editing address
if($action=="edit" || $action=="delete" || $action=="move") {
	$address_old = (array) $Addresses->fetch_address(null, $address['id']);
}

# set excludePing value
$address['excludePing'] = @$address['excludePing']==1 ? 1 : 0;
$address['is_gateway'] = @$address['is_gateway']==1 ? 1 : 0;

# check if subnet is multicast
$subnet_is_multicast = $Subnets->is_multicast ($subnet['subnet']);

# are we adding/editing range?
if (!is_blank(strstr($address['ip_addr'],"-"))) {

	# set flag for updating
	$address['type'] = "series";

	# remove possible spaces
	$address['ip_addr'] = str_replace(" ", "", $address['ip_addr']);

	# get start and stop of range
	$range		 = pf_explode("-", $address['ip_addr']);
	$address['start'] = $range[0];
	$address['stop']  = $range[1];

	# verify both IP addresses
	if ($subnet['isFolder']=="1") {
    	if($Addresses->validate_ip( $address['start'])===false)     { $Result->show("danger", _("Invalid IP address")."!", true); }
    	if($Addresses->validate_ip( $address['stop'])===false)      { $Result->show("danger", _("Invalid IP address")."!", true); }
	}
	else {
		$Addresses->address_within_subnet($address['start'], $subnet, true);
		$Addresses->address_within_subnet($address['stop'],  $subnet, true);
	}

	# go from start to stop and insert / update / delete IPs
	$start = $Subnets->transform_to_decimal($address['start']);
	$stop  = $Subnets->transform_to_decimal($address['stop']);

	# start cannot be higher than stop!
	if($start>$stop)									{ $Result->show("danger", _("Invalid address range")."!", true); }

	# we can manage only 4096 IP's at once!
	if(gmp_strval(gmp_sub($stop,$start)) > 4096) 		{ $Result->show("danger", _("Only 4096 IP addresses at once")."!", true); }

	# set limits
	$m = gmp_strval($start);
	$n = gmp_strval(gmp_add($stop,1));

    # check if delete is confirmed
    if ($action=="delete" && !isset($address['deleteconfirm'])) {
	    $range = str_replace("-", " - ", $address['ip_addr']);
		# for ajax to prevent reload
		print "<div style='display:none'>alert alert-danger</div>";
		# result
		print "<div class='alert alert-warning'>";
		print "<strong>"._("Warning")."</strong>: "._("Are you sure you want to delete IP address range")."?";
		print "<hr>$range<div style='text-align:right'>";
		print "<div class='btn-group'>";
		print "	<a class='btn btn-sm btn-danger editIPSubmitDelete' id='editIPSubmitDelete'>"._("Confirm")."</a>";
		print "</div>";
		print "</div>";
		print "</div>";
	}
	# ok, edit
	else {
    	$c = 0;
		# for each IP in range modify
		while (gmp_cmp($m, $n) != 0) {

    		# remove gateway if not 0
    		if ($c!=0)  { unset($address['is_gateway']); }
            $c++;

			# reset IP address field
			$address['ip_addr'] = $m;

			# set multicast MAC
			if ($User->settings->enableMulticast==1) {
                if ($Subnets->is_multicast ($address['ip_addr'])) {
                    $address['mac'] = $Subnets->create_multicast_mac ($Subnets->transform_address($address['ip_addr'],"dotted"));
                }
            }

    	    # multicast check
    	    if ($User->settings->enableMulticast==1) {
        	    if ($Subnets->is_multicast ($address['ip_addr'])) {
            	    if (!$User->is_admin(false)) {
                	    $mtest = $Subnets->validate_multicast_mac ($address['mac'], $subnet['sectionId'], $subnet['vlanId'], MCUNIQUE);
                	    if ($mtest !== true)                                                        { $Result->show("danger", _($mtest), true); }
            	    }
        	    }
    	    }

        	# validate and normalize MAC address
        	if($action!=="delete") {
            	if(!is_blank(@$address['mac'])) {
                	if($User->validate_mac ($address['mac'])===false) {
                    	$errors[] = _('Invalid MAC address')."!";
                	}
                	// normalize
                	else {
                    	$address['mac'] = $User->reformat_mac_address ($address['mac'], 1);
                	}
            	}
        	}


			# if it already exist for add skip it !
			if($Addresses->address_exists ($m, $address['subnetId']) && $action=="add") {
				# Add Warnings if it exists
				$Result->show("warning", _('IP address')." ".$Addresses->transform_address($m, "dotted")." "._('already existing in selected network').'!', false);
			}
			else {
				# if it fails set error log
				if (!$Addresses->modify_address($address, false)) {
			        $errors[] = _('Cannot').' '. $address['action']. ' '._('IP address').' '. $Addresses->transform_to_dotted($m);
			    }
			}
			# next IP
			$m = gmp_strval(gmp_add($m,1));
		}

		# print errors if they exist
		if(isset($errors)) {
			$log = $Log->array_to_log ($errors);
			$Result->show("danger", $log, false);
			$Log->write( _("IP address modification"), _("Error")." ".$action." "._("range")." ".$address["start"]." - ".$address["stop"]."<br> $log", 2);
		}
		else {
			# reset IP for mailing
			$address['ip_addr'] = $address['start'] .' - '. $address['stop'];
			# log and changelog
			$Result->show("success", _("Range")." $address[start] - $address[stop] "._($action)." "._("successful")."!", false);
			$Log->write( _("IP address modification"), _("Range")." ".$address["start"]." - ".$address["stop"]." ".$action." "._("successful")."!", 0);

			# send changelog mail
			$Log->object_action = $action;
			$Log->object_type   = _("address range");
			$Log->object_result = _("success");
			$Log->user 			= $User->user;

			$Log->changelog_send_mail ( _("Address range")." ".$address["start"]." - "."$address[stop] $action");
		}
	}
}
/* no range, single IP address */
else {

	# unique hostname requested?
	if(isset($address['unique'])) {
		if($address['unique'] == 1 && !is_blank($address['hostname'])) {
			# check if unique
			if(!$Addresses->is_hostname_unique($address['hostname'])) 						{ $Result->show("danger", _('Hostname is not unique')."!", true); }
		}
	}

	# validate and normalize MAC address
	if($action!=="delete") {
    	if(!is_blank(@$address['mac'])) {
    		$address['mac'] = trim($address['mac']);
        	if($User->validate_mac ($address['mac'])===false) {
            	$Result->show("danger", _('Invalid MAC address')."!", true);
        	}
        	// normalize
        	else {
            	$address['mac'] = $User->reformat_mac_address ($address['mac'], 1);
        	}
    	}
	}

	# reset subnet if move
	if($action == "move")	{
		$subnet = (array) $Subnets->fetch_subnet(null, $address['newSubnet']);
		$address['ip_addr'] = $address_old['ip'];
	}
	# if errors are present print them, else execute query!
	if(0 && $verify) 				{ $Result->show("danger", _('Error').": $verify ($address[ip_addr])", true); }  // TODO: Set undefined variable $verify
	else {
		# set update type for update to single
		$address['type'] = "single";

		# check for duplicate entryon adding new address
	    if ($action == "add") {
	        if ($Addresses->address_exists ($address['ip_addr'], $address['subnetId'])) 	{ $Result->show("danger", _('IP address')." $address[ip_addr] "._('already existing in selected network').'!', true); }
	    }

		# check for duplicate entry on edit!
	    if ($action == "edit") {	    	# if IP is the same than it can already exist!
	    	if($Addresses->transform_address($address['ip_addr'],"decimal") != $address['ip_addr_old']) {
	        	if ($Addresses->address_exists ($address['ip_addr'], $address['subnetId'])) { $Result->show("danger", _('IP address')." $address[ip_addr] "._('already existing in selected network').'!', true); }
	    	}
	    }
	    # move checks
	    if($action == "move") {
		    # check if not already used in new subnet
	        if ($Addresses->address_exists ($address['ip_addr'], $address['newSubnet'])) 	{ $Result->show("danger", _('IP address')." $address[ip_addr] "._('already existing in selected network').'!', true); }
	    }
	    # multicast check
	    if ($User->settings->enableMulticast==1 && $subnet_is_multicast) {
    	    if (!$User->is_admin(false)) {
        	    $mtest = $Subnets->validate_multicast_mac ($address['mac'], $subnet['sectionId'], $subnet['vlanId'], MCUNIQUE);
        	    if ($mtest !== true)                                                        { $Result->show("danger", _($mtest), true); }
    	    }
	    }

	    # for delete actions check if delete was confirmed
	    if ($action=="delete" && !isset($address['deleteconfirm'])) {
			# for ajax to prevent reload
			print "<div style='display:none'>alert alert-danger</div>";
			# result
			print "<div class='alert alert-warning'>";
			print "<strong>"._("Warning")."</strong>: "._("Are you sure you want to delete IP address")."?";
			print "<hr><div style='text-align:right'>";
			print "<div class='btn-group'>";
			print "	<a class='btn btn-sm btn-danger editIPSubmitDelete' id='editIPSubmitDelete'>"._("Confirm")."</a>";
			print "</div>";
			print "</div>";
			print "</div>";
		}
		# ok, execute
		else {
			//fail
		    if (!$Addresses->modify_address($address)) {
		        $Result->show("danger", _('Error inserting IP address')."!", false);
		    }
		    //success, save log file and send email
		    else {
		        $Result->show("success", _("IP $action successful"),false);
		        // try to ping
		        if ($subnet['pingSubnet']=="1" && $action=="add") {
    		        $pingRes = $Ping->ping_address($Subnets->transform_address($address['ip_addr'], "dotted"));
    		        // update status
    		        if($pingRes==0) {
        		        // print alive
        		        $Result->show("success", _("IP address")." ".$Subnets->transform_address($address['ip_addr'], "dotted")." "._("is alive"), false);
        		        // update status
        		        @$Ping->ping_update_lastseen($Addresses->lastId);
                    }
		        }
		    }
		}
	}
}
