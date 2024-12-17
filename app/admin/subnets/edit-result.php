<?php

/**
 * Function to add / edit / delete subnet
 ********************************************/

/* this can come from snmp, so if objects are already initialized print it */
if (!function_exists("create_link")) {
    /* functions */
    require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

    # initialize user object
    $Database 	= new Database_PDO;
    $User 		= new User ($Database);
    $Admin	 	= new Admin ($Database, false);
    $Subnets	= new Subnets ($Database);
    $Sections	= new Sections ($Database);
    $Addresses	= new Addresses ($Database);
    $Tools		= new Tools ($Database);
    $Result 	= new Result ();
}

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
if($POST->action=="add") {
	$User->Crypto->csrf_cookie ("validate", "subnet_add", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
} else {
	$User->Crypto->csrf_cookie ("validate", "subnet_".$POST->subnetId, $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
}

# if show name than description must be set
if($POST->showName==1 && is_blank($POST->description)) 	{ $Result->show("danger", _("Please enter subnet description to show as name!"), true); }

# we need old values for mailing
if($POST->action=="edit" || $POST->action=="delete") {
	$old_subnet_details = $Subnets->fetch_subnet("id", $POST->subnetId);
	if($old_subnet_details===false)								{ $Result->show("danger", _("Invalid subnet Id"), true); }
}

# modify post parameters
$POST->cidr = trim($POST->subnet);
$POST->id   = $POST->subnetId;

# get mask and subnet
$temp = $Subnets->cidr_network_and_mask($POST->subnet);
$POST->mask   = trim($temp[1]);
$POST->subnet = trim($temp[0]);


# errors array
$errors = array ();
# default vrf
if(!isset($POST->vrfId) || $POST->vrfId==null)	{ $POST->vrfId = 0; }
if(!is_numeric($POST->vrfId))						{ $POST->vrfId = 0; }


# get section details
$section = (array) $Sections->fetch_section(null, $POST->sectionId);

# get master subnet details for folder overrides
if($POST->masterSubnetId!=0)	{
	$master_section = (array) $Subnets->fetch_subnet(null, $POST->masterSubnetId);
	if($master_section['isFolder']==1)	{ $parent_is_folder = true; }
	else								{ $parent_is_folder = false; }
}
else 									{ $parent_is_folder = false; }

# If request came from IP address subnet edit and action2 is Delete then change action
if($POST->action2=="delete")        { $POST->action = $POST->action2; }



# new subnet checks
if ($POST->action=="add") {

	# Generic checks

    // ID must be numeric value
	if(!is_numeric($POST->sectionId))									{ $Result->show("danger", _("Invalid ID"), true); }
    // verify that user has permissions to add subnet
    if($Sections->check_permission ($User->user, $POST->sectionId) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true); }

    //verify cidr
    $cidr_check = $Subnets->verify_cidr_address($POST->cidr);
    if(strlen($cidr_check)>5) 												{ $errors[] = $cidr_check; }


    # Set permissions if adding new subnet
	// root
	if($POST->masterSubnetId==0) {
		$POST->permissions = $section['permissions'];
	}
	// nested - inherit parent permissions
	else {
		# get parent
		$parent = $Subnets->fetch_subnet(null, $POST->masterSubnetId);
		$POST->permissions = $parent->permissions;
	}


    // make subnet checks only if strictmode is true
    if($section['strictMode']==1 && !$parent_is_folder ) {
	    // we are adding nested subnet
	    if($POST->masterSubnetId!=0) {
    	    //verify that nested subnet is inside its parent
	        if (!$Subnets->verify_subnet_nesting($POST->masterSubnetId, $POST->cidr)) {
	            $errors[] = _('Nested subnet not in root subnet!');
	        }
	        else {
    	        //check for overlapping against existing subnets under same master
    	        $overlap = $Subnets->verify_nested_subnet_overlapping($POST->cidr, $POST->vrfId, $POST->masterSubnetId);
    			if($overlap!==false) {
    	            $errors[] = $overlap;
    	        }
	        }
        }
	    // root subnet, check overlapping against other root subnets
	    else {
	       $overlap = $Subnets->verify_subnet_overlapping($POST->sectionId, $POST->cidr, $POST->vrfId);
	    	if($overlap!==false) {
	            $errors[] = $overlap;
	        }
	    }
    }
	# parent is folder checks
	elseif($section['strictMode']==1) {
        //check for overlapping against existing subnets under same master
        $overlap = $Subnets->verify_nested_subnet_overlapping($POST->cidr, $POST->vrfId, $POST->masterSubnetId);
		if($overlap!==false) {
            $errors[] = $overlap;
        }
	    // we need to validate against all root subnets inside section
	    $overlap = $Subnets->verify_subnet_overlapping($POST->sectionId, $POST->cidr, $POST->vrfId);
    	if($overlap!==false) {
            $errors[] = $overlap;
        }
	    // we need to check also all other folders for same subnets !
	    $overlap = $Subnets->verify_subnet_interfolder_overlapping($POST->sectionId, $POST->cidr, $POST->vrfId);
    	if($overlap!==false) {
            $errors[] = $overlap;
        }
	}

	# If VRF is defined check for uniqueness globally or if selected !
	if ($POST->vrfId>0 || $User->settings->enforceUnique=="1" && $section['strictMode']==1) {
		# make vrf overlapping check
		$overlap = $Subnets->verify_vrf_overlapping ($POST->cidr, $POST->vrfId, 0, $POST->masterSubnetId);
		if($overlap!==false) {
			$errors[] = $overlap;
		}
	}
}
# edit checks
elseif ($POST->action=="edit") {
    // validate IDs
	if(!is_numeric($POST->subnetId))							{ $Result->show("danger", _("Invalid ID"), true); }
	if(!is_numeric($POST->sectionId))						{ $Result->show("danger", _("Invalid ID"), true); }

    // check subnet permissions
    if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true); }

    // for nesting - MasterId cannot be the same as subnetId
    if ( $POST->masterSubnetId==$POST->subnetId ) {
    	$errors[] = _('Subnet cannot nest behind itself!');
    }

    // If section changes then do checks!
    if ($POST->sectionId != $POST->sectionIdNew) {
    	//reset masterId - we are putting it to root
    	$POST->masterSubnetId = 0;
        //check for overlapping
        $sectionIdNew = (array) $Sections->fetch_section(null, $POST->sectionIdNew);
        if($sectionIdNew['strictMode']==1 && !$parent_is_folder) {
        	/* verify that no overlapping occurs if we are adding root subnet */
        	$overlap=$Subnets->verify_subnet_overlapping ($POST->sectionIdNew, $POST->cidr, $POST->vrfId);
        	if($overlap!==false) {
    	    	$errors[] = $overlap;
    	    }
        }
    }

    // if strict mode is enforced do checks
    if ($section['strictMode']==1) {
    	// not if parent is folder
    	if ($parent_is_folder===false) {
    		/* verify that nested subnet is inside root subnet */
	    	if($POST->masterSubnetId != 0) {
		    	if (!$overlap = $Subnets->verify_subnet_nesting($POST->masterSubnetId, $POST->cidr)) {
		    		$errors[] = _('Nested subnet not in root subnet!');
		    	}
	    	}
    	}
    }

	// parent is folder and folder does not permit overlapping
	if (($POST->vrfId != $POST->vrfIdOld) && $section['strictMode']==1) {
    	$overlap=$Subnets->verify_subnet_overlapping ($POST->sectionId, $POST->cidr, $POST->vrfId, $POST->masterSubnetId);
    	if($overlap!==false) {
	    	$errors[] = $overlap;
	    }
    }

	# If VRF is defined check for uniqueness globally or if selected !
	if ($POST->vrfId>0 || ($User->settings->enforceUnique=="1" && $section['strictMode']==1)) {
		# make vrf overlapping check only if vrfId changes
		if($POST->vrfId!=$old_subnet_details->vrfId) {
			$overlap = $Subnets->verify_vrf_overlapping ($POST->cidr, $POST->vrfId, $old_subnet_details->id, $POST->masterSubnetId);
			if($overlap!==false) {
				$errors[] = $overlap;
			}
		}
	}
}
# delete checks
else {
    // validate IDs
	if(!is_numeric($POST->subnetId))							{ $Result->show("danger", _("Invalid ID"), true); }
	if(!is_numeric($POST->sectionId))						{ $Result->show("danger", _("Invalid ID"), true); }
    // check subnet permissions
    if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true); }
}

/* If no errors are present execute request */
if (!empty($errors)) {
	//unique
	$errors = array_unique($errors);
    print '<div class="alert alert-danger"><strong>'._('Please fix following problems').'</strong>:';
    foreach ($errors as $error) { print "<br>".$error; }
    print '</div>';
    die();
}
/* delete confirmation */
elseif ($POST->action=="delete" && !isset($POST->deleteconfirm)) {
	# for ajax to prevent reload
	print "<div style='display:none'>alert alert-danger</div>";
	# result
	print "<div class='alert alert-warning'>";

	# print what will be deleted
	//fetch all slave subnets
	$Subnets->fetch_subnet_slaves_recursive ($POST->subnetId);
	$subcnt = sizeof($Subnets->slaves);
	foreach($Subnets->slaves as $s) {
		$slave_array[$s] = $s;
	}
	$ipcnt = $Addresses->count_addresses_in_multiple_subnets($slave_array);

	print "<strong>"._("Warning")."</strong>: "._("I will delete").":<ul>";
	print "	<li>$subcnt "._("subnets")."</li>";
	if($ipcnt>0) {
	print "	<li>$ipcnt "._("IP addresses")."</li>";
	}
	print "</ul>";

	print "<hr><div style='text-align:right'>";
	print _("Are you sure you want to delete above items?")." ";
	print "<div class='btn-group'>";
	print "	<a class='btn btn-sm btn-danger editSubnetSubmitDelete' id='editSubnetSubmitDelete'>"._("Confirm")."</a>";
	print "</div>";
	print "</div>";
	print "</div>";
}
/* execute */
else {

	# remove scanagent if not needed
	if (!isset($POST->pingSubnet)&&!isset($POST->discoverSubnet)&&!isset($POST->resolveDNS))	{ $POST->scanAgent=0; }

	# create array of default update values
	$values = array(
					"id"             => $POST->subnetId,
					"isFolder"       => 0,
					"masterSubnetId" => $POST->masterSubnetId,
					"subnet"         => $Subnets->transform_to_decimal($POST->subnet),
					"mask"           => $POST->mask,
					"description"    => $POST->description,
					"vlanId"         => $POST->vlanId,
					"allowRequests"  => $Admin->verify_checkbox($POST->allowRequests),
					"showName"       => $Admin->verify_checkbox($POST->showName),
					"discoverSubnet" => $Admin->verify_checkbox($POST->discoverSubnet),
					"pingSubnet"     => $Admin->verify_checkbox($POST->pingSubnet),
					"resolveDNS"     => $Admin->verify_checkbox($POST->resolveDNS),
					"scanAgent"      => $POST->scanAgent,
					"DNSrecursive"   => $Admin->verify_checkbox($POST->DNSrecursive),
					"DNSrecords"     => $Admin->verify_checkbox($POST->DNSrecords),
					"nameserverId"   => $POST->nameserverId,
					"device"         => $POST->device,
					"isFull"         => $Admin->verify_checkbox($POST->isFull),
					"isPool"         => $Admin->verify_checkbox($POST->isPool)
					);
    # location
    if (isset($POST->location)) {
        if (!is_numeric($POST->location)) {
            $Result->show("danger", _("Invalid location value"), true);
        }
        $values['location'] = $POST->location;
    }
	# append customerId
	if($User->settings->enableCustomers=="1") {
		if (is_numeric($POST->customer_id)) {
			if ($POST->customer_id>0) {
				$values['customer_id'] = $POST->customer_id;
			}
			else {
				$values['customer_id'] = NULL;
			}
		}
	}
    # threshold
    if (isset($POST->threshold)) {
        if (!is_numeric($POST->threshold)) {
            $Result->show("danger", _("Invalid threshold value"), true);
        }
        $values['threshold'] = $POST->threshold;
    }
	# for new subnets we add permissions
	if($POST->action=="add") {
		$values['permissions']=$POST->permissions;
		$values['sectionId']=$POST->sectionId;
		// add vrf
		$values['vrfId']=$POST->vrfId;
	}
	else {
		# if section change
		if($POST->sectionId != $POST->sectionIdNew) {
			$values['sectionId']=$POST->sectionIdNew;
		}
		# if vrf change
		if($POST->vrfId != $POST->vrfIdOld) {
			$values['vrfId']=$POST->vrfId;
		}
	}

	# fetch custom fields
	$update = $Tools->update_POST_custom_fields('subnets', $POST->action, $POST);
	$values = array_merge($values, $update);

	# execute
	if (!$Subnets->modify_subnet ($POST->action, $values))	{ $Result->show("danger", _('Error editing subnet'), true); }
	else {
		# if add save id !
		if ($POST->action=="add") { $new_subnet_id = $Subnets->lastInsertId; }
		# update also all slave subnets if section changes!
		if (isset($values['sectionId']) && $POST->action=="edit") {
			$Subnets->reset_subnet_slaves_recursive();
			$Subnets->fetch_subnet_slaves_recursive($POST->subnetId);
			$Subnets->remove_subnet_slaves_master($POST->subnetId);
			if(sizeof($Subnets->slaves)>0) {
				foreach($Subnets->slaves as $slaveId) {
					$Admin->object_modify ("subnets", "edit", "id", array("id"=>$slaveId, "sectionId"=>$POST->sectionIdNew));
				}
			}
		}

		# edit success
		if($POST->action=="delete")	{ $Result->show("success", _('Subnet, IP addresses and all belonging subnets deleted successfully').'!', false); }
		# create - for redirect
		elseif ($POST->action=="add") { $Result->show("success", _("Subnet")." " . $User->get_post_action() . " "._("successful").'!<div class="hidden subnet_id_new">'.$new_subnet_id.'</div><div class="hidden section_id_new">'.$values['sectionId'].'</div>', false); }
		#
		else { $Result->show("success", _("Subnet")." ".$User->get_post_action()." "._("successful").'!', false); }
	}

	# propagate to slaves
	if ($POST->set_inheritance=="Yes" && $POST->action=="edit") {
        # reset slaves
        if ($Subnets->slaves===null) {
    		$Subnets->reset_subnet_slaves_recursive();
    		$Subnets->fetch_subnet_slaves_recursive($POST->subnetId);
    		$Subnets->remove_subnet_slaves_master($POST->subnetId);
		}
    	# set what to update
    	$values = array(
					"vlanId"       =>$POST->vlanId,
					"vrfId"        =>$POST->vrfId,
					"nameserverId" =>$POST->nameserverId,
					"scanAgent"    =>$POST->scanAgent,
					"device"       =>$POST->device,
					"isFull"       =>$Admin->verify_checkbox($POST->isFull),
					"isPool"       =>$Admin->verify_checkbox($POST->isPool)
					);
        # optional values
        if(isset($POST->allowRequests))  $values['allowRequests']  = $Admin->verify_checkbox($POST->allowRequests);
        if(isset($POST->showName))       $values['showName']       = $Admin->verify_checkbox($POST->showName);
        if(isset($POST->discoverSubnet)) $values['discoverSubnet'] = $Admin->verify_checkbox($POST->discoverSubnet);
        if(isset($POST->pingSubnet))     $values['pingSubnet']     = $Admin->verify_checkbox($POST->pingSubnet);
	if(isset($POST->resolveDNS))     $values['resolveDNS']     = $Admin->verify_checkbox($POST->resolveDNS);

        # propagate changes
		if(is_array($Subnets->slaves) && sizeof($Subnets->slaves)>0) {
			foreach($Subnets->slaves as $slaveId) {
				 $Admin->object_modify ("subnets", "edit", "id", array_merge(array("id"=>$slaveId), $values));
			}
        }
	}


	# powerDNS
	if ($User->settings->enablePowerDNS==1) {
		# powerDNS class
		$PowerDNS = new PowerDNS ($Database);
		if($PowerDNS->db_check()===false) { $Result->show("danger", _("Cannot connect to powerDNS database"), true); }

		// set zone
		$zone = $POST->action=="add" ? $PowerDNS->get_ptr_zone_name ($POST->subnet, $POST->mask) : $PowerDNS->get_ptr_zone_name ($old_subnet_details->ip, $old_subnet_details->mask);
		// try to fetch domain
		$domain = $PowerDNS->fetch_domain_by_name ($zone);

		// POST DNSrecursive not set, fake it if old is also 0
		if (!isset($POST->DNSrecursive) && @$old_subnet_details->DNSrecursive==0) { $POST->DNSrecursive = 0; }

		// recreate csrf cookie
        $csrf = $User->Crypto->csrf_cookie ("create", "domain");

		//delete
		if ($POST->action=="delete") {
			// if zone exists
			if ($domain!==false) {
				print "<hr><p class='hidden alert-danger'></p>";
				print "<div class='alert alert-warning'>";

				print "	<div class='btn-group pull-right'>";
				print "	<a class='btn btn-danger btn-xs' id='editDomainSubmit'>"._('Yes')."</a>";
				print "	<a class='btn btn-default btn-xs hidePopupsReload'>"._('No')."</a>";
				print "	</div>";

				print _('Do you wish to delete DNS zone and all records')."?<br>";
				print "	&nbsp;&nbsp; "._("DNS zone")." <strong>$domain->name</strong></li>";
				print " <form name='domainEdit' id='domainEdit'><input type='hidden' name='action' value='delete'><input type='hidden' name='id' value='$domain->id'><input type='hidden' name='csrf_cookie' value='$csrf'></form>";
				print "	<div class='domain-edit-result'></div>";
				print "</div>";
			}
		}
		//create
		elseif ($POST->action=="add" && $POST->DNSrecursive=="1") {
			// if zone exists do nothing, otherwise create zone
			if ($domain===false) {
				// use default values
				$values = db_json_decode($User->settings->powerDNS, true);
				$values['name'] = $zone;
				// create domain
				$PowerDNS->domain_edit ("add", array("name"=>$zone,"type"=>"NATIVE"));
				// create default records
				$PowerDNS->create_default_records ($values);
			}
		}
		// update
		elseif ($POST->action=="edit" && $POST->DNSrecursive!=$old_subnet_details->DNSrecursive) {
			// remove domain
			if (!isset($POST->DNSrecursive) && $domain!==false) {
				print "<hr><p class='hidden alert-danger'></p>";
				print "<div class='alert alert-warning'>";

				print "	<div class='btn-group pull-right'>";
				print "	<a class='btn btn-danger btn-xs' id='editDomainSubmit'>"._('Yes')."</a>";
				print "	<a class='btn btn-default btn-xs hidePopupsReload'>"._('No')."</a>";
				print "	</div>";

				print _('Do you wish to delete DNS zone and all records')."?<br>";
				print "	&nbsp;&nbsp; "._("DNS zone")." <strong>$domain->name</strong></li>";
				print " <form name='domainEdit' id='domainEdit'><input type='hidden' name='action' value='delete'><input type='hidden' name='id' value='$domain->id'><input type='hidden' name='csrf_cookie' value='$csrf'></form>";
				print "	<div class='domain-edit-result'></div>";
				print "</div>";
			}
			// create domain
			elseif (isset($POST->DNSrecursive) && $domain===false) {
				// use default values
				$values = db_json_decode($User->settings->powerDNS, true);
				$values['name'] = $zone;
				// create domain
				$PowerDNS->domain_edit ("add", array("name"=>$zone,"type"=>"NATIVE"));
				// save id
				$domain_id = $PowerDNS->get_last_db_id ();
				// create default records
				$PowerDNS->create_default_records ($values);

				// create PTR records
				$Addresses->pdns_validate_connection ();
				$hosts = $Addresses->fetch_subnet_addresses ($old_subnet_details->id, "ip_addr", "asc");
				// loop
				if (is_array($hosts) && sizeof($hosts)>0) {
					$cnt = 0;
					$err = 0;
					$ski = 0;
					// loop
					foreach ($hosts as $h) {
						if ($h->PTRignore=="1") {
							$ski++;
						}
						elseif ($Addresses->ptr_add ($h, false, $h->id) !== false) {
							$cnt++;
						}
						else {
							$err++;
						}
					}
					// print
					$Result->show ("success", "$cnt "._("PTR records created"));
					// error
					if ($err!=0) {
					$Result->show ("warning", "$err "._("invalid hostnames"));
					}
				}
			}
		}
	}
}
