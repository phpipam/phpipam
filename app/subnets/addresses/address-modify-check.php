<?php

/**
 * Script to check edited / deleted / new IP addresses
 * If all is ok write to database
 *************************************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

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

# validate csrf cookie
$_POST['csrf_cookie']==$_SESSION['csrf_cookie'] ? :                      $Result->show("danger", _("Invalid CSRF cookie"), true);

# validate action
$Tools->validate_action ($_POST['action']);
$action = $_POST['action'];
//reset delete action form visual visual
if(isset($_POST['action-visual'])) {
	if(@$_POST['action-visual'] == "delete") { $action = "delete"; }
}

# save $_POST to $address
$address = $_POST;

# required fields
isset($address['action']) ?:		$Result->show("danger", _("Missing required fields"). " action", true);
isset($address['subnet']) ?:		$Result->show("danger", _("Missing required fields"). " subnet", true);
isset($address['subnetId']) ?:		$Result->show("danger", _("Missing required fields"). " subnetId", true);
isset($address['id']) ?:			$Result->show("danger", _("Missing required fields"). " id", true);

# ptr
if(!isset($address['PTRignore']))	$address['PTRignore']=0;

# generate firewall address object name
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);
if ($firewallZoneSettings->autogen == 'on') {
	if ($address['action'] == 'add' ) {
		$address['firewallAddressObject'] = $Zones->generate_address_object($address['subnetId'],$address['dns_name']);
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
if(sizeof($custom_fields) > 0) {
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
		if($field['Null']=="NO" && strlen($address[$field['name']])==0) {
			$Result->show("danger", $field['name']._(" can not be empty!"), true);
		}
	}
}

# we need old address details for mailing or if we are editing address
if($action=="edit" || $action=="delete" || $action=="move") {
	$address_old = (array) $Addresses->fetch_address(null, $address['id']);
}

# set excludePing value
$address['excludePing'] = @$address['excludePing']==1 ? 1 : 0;

# no strict checks flag - for range networks and /31, /32
$not_strict = @$address['nostrict']=="yes" ? true : false;



# are we adding/editing range?
if (strlen(strstr($address['ip_addr'],"-")) > 0) {

	# set flag for updating
	$address['type'] = "series";

	# remove possible spaces
	$address['ip_addr'] = str_replace(" ", "", $address['ip_addr']);

	# get start and stop of range
	$range		 = explode("-", $address['ip_addr']);
	$address['start'] = $range[0];
	$address['stop']  = $range[1];

	# verify both IP addresses
	$Addresses->verify_address( $address['start'], "$subnet[ip]/$subnet[mask]", $not_strict );
	$Addresses->verify_address( $address['stop'] , "$subnet[ip]/$subnet[mask]", $not_strict );

	# go from start to stop and insert / update / delete IPs
	$start = $Subnets->transform_to_decimal($address['start']);
	$stop  = $Subnets->transform_to_decimal($address['stop']);

	# start cannot be higher than stop!
	if($start>$stop)									{ $Result->show("danger", _("Invalid address range")."!", true); }

	# we can manage only 255 IP's at once!
	if(gmp_strval(gmp_sub($stop,$start)) > 255) 		{ $Result->show("danger", _("Only 255 IP addresses at once")."!", true); }

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

			# modify action - if delete ok, dynamically reset add / edit -> if IP already exists set edit
			if($action != "delete") {
				$address['action'] = $Addresses->address_exists ($m, $address['subnetId'])===true ? "edit" : "add";
			}
			# if it fails set error log
			if (!$Addresses->modify_address($address, false)) {
		        $errors[] = _('Cannot').' '. $address['action']. ' '._('IP address').' '. $Addresses->transform_to_dotted($m);
		    }
			# next IP
			$m = gmp_strval(gmp_add($m,1));
		}

		# print errors if they exist
		if(isset($errors)) {
			$log = $Result->array_to_log ($errors);
			$Result->show("danger", $log, false);
			$Log->write( "IP address modification", "'Error $action range $address[start] - $address[stop]<br> $log", 2);
		}
		else {
			# reset IP for mailing
			$address['ip_addr'] = $address['start'] .' - '. $address['stop'];
			# log and changelog
			$Result->show("success", _("Range")." $address[start] - $address[stop] "._($action)." "._("successfull")."!", false);
			$Log->write( "IP address modification", "Range $address[start] - $address[stop] $action successfull!", 0);

			# send changelog mail
			$Log->object_action = $action;
			$Log->object_type   = "address range";
			$Log->object_result = "success";
			$Log->user 			= $User->user;

			$Log->changelog_send_mail ("Address range $address[start] - $address[stop] $action", null);
		}
	}
}
/* no range, single IP address */
else {

	# unique hostname requested?
	if(isset($address['unique'])) {
		if($address['unique'] == 1 && strlen($address['dns_name'])>0) {
			# check if unique
			if(!$Addresses->is_hostname_unique($address['dns_name'])) 						{ $Result->show("danger", _('Hostname is not unique')."!", true); }
		}
	}

	# reset subnet if move
	if($action == "move")	{
		$subnet = (array) $Subnets->fetch_subnet(null, $address['newSubnet']);
		$address['ip_addr'] = $address_old['ip'];
	}
	# verify address
	if($action!=="delete")
	$verify = $Addresses->verify_address( $address['ip_addr'], "$subnet[ip]/$subnet[mask]", $not_strict );

	# if errors are present print them, else execute query!
	if($verify) 				{ $Result->show("danger", _('Error').": $verify ($address[ip_addr])", true); }
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
    		        $Ping->ping_address($Subnets->transform_address($address['ip_addr'], "dotted"));
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
?>