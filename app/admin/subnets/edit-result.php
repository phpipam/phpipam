<?php

/**
 * Function to add / edit / delete subnet
 ********************************************/

/* this can come from snmp, so if objects are already initialized print it */
if (!function_exists("create_link")) {
    /* functions */
    require( dirname(__FILE__) . '/../../../functions/functions.php');

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

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "subnet", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# if show name than description must be set
if(@$_POST['showName']==1 && strlen($_POST['description'])==0) 	{ $Result->show("danger", _("Please enter subnet description to show as name!"), true); }

# we need old values for mailing
if($_POST['action']=="edit" || $_POST['action']=="delete") {
	$old_subnet_details = (array) $Subnets->fetch_subnet("id", $_POST['subnetId']);
}

# modify post parameters
$_POST['cidr'] = trim($_POST['subnet']);
$_POST['id']   = $_POST['subnetId'];

# get mask and subnet
$temp = explode("/", $_POST['subnet']);
$_POST['mask']   = trim($temp[1]);
$_POST['subnet'] = trim($temp[0]);



# get section details
$section = (array) $Sections->fetch_section(null, $_POST['sectionId']);
# fetch custom fields
$custom = $Tools->fetch_custom_fields('subnets');

# get master subnet details for folder overrides
if($_POST['masterSubnetId']!=0)	{
	$master_section = (array) $Subnets->fetch_subnet(null, $_POST['masterSubnetId']);
	if($master_section['isFolder']==1)	{ $parent_is_folder = true; }
	else								{ $parent_is_folder = false; }
}
else 									{ $parent_is_folder = false; }


# If request came from IP address subnet edit and action2 is Delete then change action
if(@$_POST['action2']=="delete")        { $_POST['action'] = $_POST['action2']; }



# new subnet checks
if ($_POST['action']=="add") {
    // ID must be numberic value
	if(!is_numeric($_POST['sectionId']))						{ $Result->show("danger", _("Invalid ID"), true); }

    // verify that user has permissions to add subnet
    if($Sections->check_permission ($User->user, $_POST['sectionId']) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true); }

    //verify cidr
    $cidr_check = $Subnets->verify_cidr_address($_POST['cidr']);
    if(strlen($cidr_check)>5) {
	    $errors[] = $cidr_check;
	}
    //disable checks for folders and if strict check enabled
    if($section['strictMode']==1 && !$parent_is_folder ) {

	    // we are adding nested subnet
	    if($_POST['masterSubnetId']!=0) {
    	    //verify that nested subnet is inside its parent
	        if (!$Subnets->verify_subnet_nesting($_POST['masterSubnetId'], $_POST['cidr'])) {
	            $errors[] = _('Nested subnet not in root subnet!');
	        }
	        else {
    	        //check for overlapping against existing subnets under same master
    	        $overlap = $Subnets->verify_nested_subnet_overlapping($_POST['sectionId'], $_POST['cidr'], $_POST['vrfId'], $_POST['masterSubnetId']);
    			if($overlap!==false) {
    	            $errors[] = $overlap;
    	        }
	        }
        }
	    // root subnet, check overlapping against other root subnetss
	    else {
	       $overlap = $Subnets->verify_subnet_overlapping($_POST['sectionId'], $_POST['cidr'], $_POST['vrfId']);
	    	if($overlap!==false) {
	            $errors[] = $overlap;
	        }
	    }
    }

    # Set permissions if adding new subnet
	// root
	if($_POST['masterSubnetId']==0) {
		$_POST['permissions'] = $section['permissions'];
	}
	// nested - inherit parent permissions
	else {
		# get parent
		$parent = $Subnets->fetch_subnet(null, $_POST['masterSubnetId']);
		$_POST['permissions'] = $parent->permissions;
	}
}
# edit checks
elseif ($_POST['action']=="edit") {
    // validate IDs
	if(!is_numeric($_POST['subnetId']))							{ $Result->show("danger", _("Invalid ID"), true); }
	if(!is_numeric($_POST['sectionId']))						{ $Result->show("danger", _("Invalid ID"), true); }

    // check subnet permissions
    if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true); }

    // If section changes then do checks!
    if ($_POST['sectionId'] != @$_POST['sectionIdNew']) {
    	//reset masterId - we are putting it to root
    	$_POST['masterSubnetId'] = 0;

        //check for overlapping
        $sectionIdNew = (array) $Sections->fetch_section(null, $_POST['sectionIdNew']);
        if($sectionIdNew['strictMode']==1 && !$parent_is_folder) {
        	/* verify that no overlapping occurs if we are adding root subnet */
        	$overlap=$Subnets->verify_subnet_overlapping ($_POST['sectionIdNew'], $_POST['cidr'], $_POST['vrfId']);
        	if($overlap!==false) {
    	    	$errors[] = $overlap;
    	    }
        }
    }

    // If VRF changes then do checks!
    elseif ($_POST['vrfId'] != @$_POST['vrfIdOld']) {
    	if($section['strictMode']==1 && !$parent_is_folder) {
        	/* verify that no overlapping occurs if we are adding root subnet
    	       only check for overlapping if vrf is empty or not exists!
        	*/
        	$overlap=$Subnets->verify_subnet_overlapping ($_POST['sectionId'], $_POST['cidr'], $_POST['vrfId']);
        	if($overlap!==false) {
    	    	$errors[] = $overlap;
    	    }
        }
    }

    // normal edit check
    else {
        if($section['strictMode']==1 && !$parent_is_folder) {
        	/* verify that nested subnet is inside root subnet */
        	if($_POST['masterSubnetId'] != 0) {
    	    	if (!$overlap = $Subnets->verify_subnet_nesting($_POST['masterSubnetId'], $_POST['cidr'])) {
    	    		$errors[] = _('Nested subnet not in root subnet!');
    	    	}
        	}
        }
        /* for nesting - MasterId cannot be the same as subnetId! */
        if ( $_POST['masterSubnetId']==$_POST['subnetId'] ) {
        	$errors[] = _('Subnet cannot nest behind itself!');
        }
    }
}
# delete checks
else {
    // validate IDs
	if(!is_numeric($_POST['subnetId']))							{ $Result->show("danger", _("Invalid ID"), true); }
	if(!is_numeric($_POST['sectionId']))						{ $Result->show("danger", _("Invalid ID"), true); }
    // check subnet permissions
    if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true); }
}



//custom fields check
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($_POST[$myField['name']]>1) {
				$_POST[$myField['name']] = "";
			}
		}
		//not empty
		if($myField['Null']=="NO" && strlen($_POST[$myField['name']])==0) {
			$errors[] = "Field \"$myField[name]\" cannot be empty!";
		}
	}
}



/* If no errors are present execute request */
if (sizeof(@$errors)>0) {
    print '<div class="alert alert-danger"><strong>'._('Please fix following problems').'</strong>:';
    foreach ($errors as $error) { print "<br>".$error; }
    print '</div>';
    die();
}
/* delete confirmation */
elseif ($_POST['action']=="delete" && !isset($_POST['deleteconfirm'])) {
	# for ajax to prevent reload
	print "<div style='display:none'>alert alert-danger</div>";
	# result
	print "<div class='alert alert-warning'>";

	# print what will be deleted
	//fetch all slave subnets
	$Subnets->fetch_subnet_slaves_recursive ($_POST['subnetId']);
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
	if (!isset($_POST['pingSubnet'])&&!isset($_POST['discoverSubnet']))	{ $_POST['scanAgent']=0; }

	# create array of default update values
	$values = array("id"=>@$_POST['subnetId'],
					"isFolder"=>0,
					"masterSubnetId"=>$_POST['masterSubnetId'],
					"subnet"=>$Subnets->transform_to_decimal($_POST['subnet']),
					"mask"=>$_POST['mask'],
					"description"=>@$_POST['description'],
					"vlanId"=>$_POST['vlanId'],
					"allowRequests"=>$Admin->verify_checkbox(@$_POST['allowRequests']),
					"showName"=>$Admin->verify_checkbox(@$_POST['showName']),
					"discoverSubnet"=>$Admin->verify_checkbox(@$_POST['discoverSubnet']),
					"pingSubnet"=>$Admin->verify_checkbox(@$_POST['pingSubnet']),
					"scanAgent"=>@$_POST['scanAgent'],
					"DNSrecursive"=>$Admin->verify_checkbox(@$_POST['DNSrecursive']),
					"DNSrecords"=>$Admin->verify_checkbox(@$_POST['DNSrecords']),
					"nameserverId"=>$_POST['nameserverId'],
					"device"=>$_POST['device'],
                    "isFull"=>$Admin->verify_checkbox($_POST['isFull'])
					);
    # location
    if (isset($_POST['location_item'])) {
        if (!is_numeric($_POST['location_item'])) {
            $Result->show("danger", _("Invalid location value"), true);
        }
        $values['location'] = $_POST['location_item'];
    }
    # threshold
    if (isset($_POST['threshold'])) {
        if (!is_numeric($_POST['threshold'])) {
            $Result->show("danger", _("Invalid threshold value"), true);
        }
        $values['threshold'] = $_POST['threshold'];
    }
	# for new subnets we add permissions
	if($_POST['action']=="add") {
		$values['permissions']=$_POST['permissions'];
		$values['sectionId']=$_POST['sectionId'];
		// add vrf
		$values['vrfId']=$_POST['vrfId'];
	}
	else {
		# if section change
		if(@$_POST['sectionId'] != @$_POST['sectionIdNew']) {
			$values['sectionId']=$_POST['sectionIdNew'];
		}
		# if vrf change
		if(@$_POST['vrfId'] != @$_POST['vrfIdOld']) {
			$values['vrfId']=$_POST['vrfId'];
		}
	}
	# append custom fields
	$custom = $Tools->fetch_custom_fields('subnets');
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {

			//replace possible ___ back to spaces
			$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
			if(isset($_POST[$myField['nameTest']])) { $_POST[$myField['name']] = $_POST[$myField['nameTest']];}

			//booleans can be only 0 and 1!
			if($myField['type']=="tinyint(1)") {
				if($_POST[$myField['name']]>1) {
					$_POST[$myField['name']] = 0;
				}
			}
			//not null!
			if($myField['Null']=="NO" && strlen($_POST[$myField['name']])==0) { $Result->show("danger", $myField['name'].'" can not be empty!', true); }

			# save to update array
			$values[$myField['name']] = $_POST[$myField['name']];
		}
	}

	# execute
	if (!$Subnets->modify_subnet ($_POST['action'], $values))	{ $Result->show("danger", _('Error editing subnet'), true); }
	else {
		# if add save id !
		if ($_POST['action']=="add") { $new_subnet_id = $Subnets->lastInsertId; }
		# update also all slave subnets if section changes!
		if( (isset($values['sectionId']) && $_POST['action']=="edit") || ($_POST['action']=="delete")) {
			$Subnets->reset_subnet_slaves_recursive();
			$Subnets->fetch_subnet_slaves_recursive($_POST['subnetId']);
			$Subnets->remove_subnet_slaves_master($_POST['subnetId']);
			if(sizeof($Subnets->slaves)>0) {
				foreach($Subnets->slaves as $slaveId) {
					if ($_POST['action']=="edit") 	{ $Admin->object_modify ("subnets", "edit", "id", array("id"=>$slaveId, "sectionId"=>$_POST['sectionIdNew'])); }
					else							{ $Admin->object_modify ("subnets", "delete", "id", array("id"=>$slaveId)); }
				}
			}
		}

		# edit success
		if($_POST['action']=="delete")	{ $Result->show("success", _('Subnet, IP addresses and all belonging subnets deleted successfully').'!', false); }
		# create - for redirect
		elseif ($_POST['action']=="add"){ $Result->show("success", _("Subnet $_POST[action] successfull").'!<div class="hidden subnet_id_new">'.$new_subnet_id.'</div><div class="hidden section_id_new">'.$values['sectionId'].'</div>', false); }
		#
		else							{ $Result->show("success", _("Subnet $_POST[action] successfull").'!', false); }
	}

	# propagate to slaves
	if (@$_POST['set_inheritance']=="Yes" && $_POST['action']=="edit") {
        # reset slaves
        if ($Subnets->slaves===NULL) {
    		$Subnets->reset_subnet_slaves_recursive();
    		$Subnets->fetch_subnet_slaves_recursive($_POST['subnetId']);
    		$Subnets->remove_subnet_slaves_master($_POST['subnetId']);
		}
    	# set what to update
    	$values = array(
					"vlanId"=>$_POST['vlanId'],
					"vrfId"=>$_POST['vrfId'],
					"nameserverId"=>$_POST['nameserverId'],
					"scanAgent"=>@$_POST['scanAgent'],
					"device"=>$_POST['device']);
        # optional values
        if(isset($_POST['allowRequests']))  $values['allowRequests']  = $Admin->verify_checkbox(@$_POST['allowRequests']);
        if(isset($_POST['showName']))       $values['showName']       = $Admin->verify_checkbox(@$_POST['showName']);
        if(isset($_POST['discoverSubnet'])) $values['discoverSubnet'] = $Admin->verify_checkbox(@$_POST['discoverSubnet']);
        if(isset($_POST['pingSubnet']))     $values['pingSubnet']     = $Admin->verify_checkbox(@$_POST['pingSubnet']);

        # propagate changes
		if(sizeof($Subnets->slaves)>0) {
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
		$zone = $_POST['action']=="add" ? $PowerDNS->get_ptr_zone_name ($_POST['subnet'], $_POST['mask']) : $PowerDNS->get_ptr_zone_name ($old_subnet_details['ip'], $old_subnet_details['mask']);
		// try to fetch domain
		$domain = $PowerDNS->fetch_domain_by_name ($zone);

		// POST DNSrecursive not set, fake it if old is also 0
		if (!isset($_POST['DNSrecursive']) && @$old_subnet_details['DNSrecursive']==0) { $_POST['DNSrecursive'] = 0; }

		// recreate csrf cookie
        $csrf = $User->csrf_cookie ("create", "domain");

		//delete
		if ($_POST['action']=="delete") {
			// if zone exists
			if ($domain!==false) {
				print "<hr><p class='hidden alert-danger'></p>";
				print "<div class='alert alert-warning'>";

				print "	<div class='btn-group pull-right'>";
				print "	<a class='btn btn-danger btn-xs' id='editDomainSubmit'>"._('Yes')."</a>";
				print "	<a class='btn btn-default btn-xs hidePopupsReload'>"._('No')."</a>";
				print "	</div>";

				print _('Do you wish to delete DNS zone and all records')."?<br>";
				print "	&nbsp;&nbsp; DNS zone <strong>$domain->name</strong></li>";
				print " <form name='domainEdit' id='domainEdit'><input type='hidden' name='action' value='delete'><input type='hidden' name='id' value='$domain->id'><input type='hidden' name='csrf_cookie' value='$csrf'></form>";
				print "	<div class='domain-edit-result'></div>";
				print "</div>";
			}
		}
		//create
		elseif ($_POST['action']=="add" && @$_POST['DNSrecursive']=="1") {
			// if zone exists do nothing, otherwise create zone
			if ($domain===false) {
				// use default values
				$values = json_decode($User->settings->powerDNS, true);
				$values['name'] = $zone;
				// create domain
				$PowerDNS->domain_edit ("add", array("name"=>$zone,"type"=>"NATIVE"));
				// create default records
				$PowerDNS->create_default_records ($values);
			}
		}
		// update
		elseif ($_POST['action']=="edit" && $_POST['DNSrecursive']!=$old_subnet_details['DNSrecursive']) {
			// remove domain
			if (!isset($_POST['DNSrecursive']) && $domain!==false) {
				print "<hr><p class='hidden alert-danger'></p>";
				print "<div class='alert alert-warning'>";

				print "	<div class='btn-group pull-right'>";
				print "	<a class='btn btn-danger btn-xs' id='editDomainSubmit'>"._('Yes')."</a>";
				print "	<a class='btn btn-default btn-xs hidePopupsReload'>"._('No')."</a>";
				print "	</div>";

				print _('Do you wish to delete DNS zone and all records')."?<br>";
				print "	&nbsp;&nbsp; DNS zone <strong>$domain->name</strong></li>";
				print " <form name='domainEdit' id='domainEdit'><input type='hidden' name='action' value='delete'><input type='hidden' name='id' value='$domain->id'><input type='hidden' name='csrf_cookie' value='$csrf'></form>";
				print "	<div class='domain-edit-result'></div>";
				print "</div>";
			}
			// create domain
			elseif (isset($_POST['DNSrecursive']) && $domain===false) {
				// use default values
				$values = json_decode($User->settings->powerDNS, true);
				$values['name'] = $zone;
				// create domain
				$PowerDNS->domain_edit ("add", array("name"=>$zone,"type"=>"NATIVE"));
				// save id
				$domain_id = $PowerDNS->get_last_db_id ();
				// create default records
				$PowerDNS->create_default_records ($values);

				// create PTR records
				$Addresses->pdns_validate_connection ();
				$hosts = $Addresses->fetch_subnet_addresses ($old_subnet_details['id'], "ip_addr", "asc");
				// loop
				if (sizeof($hosts)>0) {
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
					$Result->show ("success", "$cnt PTR records created");
					// error
					if ($err!=0) {
					$Result->show ("warning", "$err invalid hostnames");
					}
				}
			}
		}
	}
}
?>

