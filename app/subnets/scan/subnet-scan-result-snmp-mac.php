<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

/*
 * insert new hosts to database
 *******************************/

# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true); }

# subnet Id must be a integer
if(!is_numeric($POST->subnetId) || $POST->subnetId==0)			{ $Result->show("danger", _("Invalid ID"), true); }
# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true); }

// fetch custom fields and check for required
$required_fields = $Tools->fetch_custom_fields ('ipaddresses');
if($required_fields!==false) {
    foreach ($required_fields as $k=>$f) {
        if ($f['Null']!="NO") {
            unset($required_fields[$k]);
        }
    }
}

# ok, lets get results form post array!
foreach($POST as $key=>$line) {
	// IP address
	if(substr($key, 0,2)=="ip") 			    { $res[substr($key, 2)]['ip_addr']  	= $line; }
	// mac
	elseif(substr($key, 0,3)=="mac") 		    { $res[substr($key, 3)]['mac']  	    = $line; }
	// device
	elseif(substr($key, 0,6)=="device") 	    { $res[substr($key, 6)]['switch']       = $line; }
	// description
	elseif(substr($key, 0,11)=="description") 	{ $res[substr($key, 11)]['description'] = $line; }
	// description
	elseif(substr($key, 0,4)=="port") 	        { $res[substr($key, 4)]['port']         = $line; }
	// dns name
	elseif(substr($key, 0,8)=="hostname") 		{ $res[substr($key, 8)]['hostname']  	= $line; }
	// custom fields
	elseif (isset($required_fields)) {
    	foreach ($required_fields as $k=>$f) {
        	if((strpos($key, $f['name'])) !== false) {
                                                { $res[substr($key, strlen($f['name']))][$f['name']] = $line; }
        	}
    	}
	}

	//verify that it is not already in table!
	if(substr($key, 0,2)=="ip") {
		if($Addresses->address_exists ($line, $POST->subnetId) === true) {
			//$Result->show("danger", "IP address $line already exists!", true);
		}
	}
}

# loop and make sure that we do not have any duplicate IPs !
$unique_ips = array();
foreach ($res as $r) {
    if(in_array($r['ip_addr'], $unique_ips)) {
        $Result->show("danger", "Duplicated IP address $r[ip_addr] cannot be imported!", true);
    }
    else {
        $unique_ips[] = $r['ip_addr'];
    }
}

# insert entries
if(sizeof($res)>0) {
	$errors = 0;
	foreach($res as $r) {
		# set insert values
		$values = array("ip_addr"=>$Subnets->transform_to_decimal($r['ip_addr']),
						"hostname"=>$r['hostname'],
						"subnetId"=>$POST->subnetId,
						"description"=>$r['description'],
						"switch"=>$r['switch'],
						"mac"=>$r['mac'],
						"state"=>2,
						"lastSeen"=>date("Y-m-d H:i:s"),
						"action"=>"add"
						);
        # port
        if(isset($r['port']))   { $values['port'] = $r['port']; }
        # custom fields
		if (isset($required_fields)) {
			foreach ($required_fields as $k=>$f) {
				$values[$f['name']] = $r[$f['name']];
			}
		}
		# insert
		if(!$Addresses->modify_address($values))	{ $Result->show("danger", _("Failed to import entry")." ".$r['ip_addr'], false); $errors++; }
	}

	# success if no errors
	if($errors==0) {  $Result->show("success", _("Scan results added to database")."!", true); }
}
# error
else { $Result->show("danger", _("No entries available"), true); }
?>