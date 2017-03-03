<?php
/*
 * insert new hosts to database
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools      = new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# validate csrf cookie
$User->csrf_cookie ("validate", "scan", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# check for number of input values
$max = ini_get("max_input_vars");
if(sizeof($_POST)>=ini_get("max_input_vars")) 							{ $Result->show("danger", _("Number of discovered hosts exceed maximum possible defined by php.ini - set to ")." $max <hr>"._("Please adjust your php.ini settings for value `max_input_vars`"), true); }
# subnet Id must be a integer
if(!is_numeric($_POST['subnetId']) || $_POST['subnetId']==0)			{ $Result->show("danger", _("Invalid ID"), true); }
# verify that user has write permissionss for subnet
if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to modify hosts in this subnet')."!", true); }

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
foreach($_POST as $key=>$line) {
	// IP address
	if(substr($key, 0,2)=="ip") 			    { $res[substr($key, 2)]['ip_addr']  	= $line; }
	// description
	elseif(substr($key, 0,11)=="description") 	{ $res[substr($key, 11)]['description'] = $line; }
	// dns name
	elseif(substr($key, 0,8)=="dns_name") 		{ $res[substr($key, 8)]['dns_name']  	= $line; }
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
		if($Addresses->address_exists ($line, $_POST['subnetId']) === true) {
			$Result->show("danger", "IP address $line already exists!", true);
		}
	}
}

# insert entries
if(sizeof($res)>0) {
	$errors = 0;
	foreach($res as $r) {
		# set insert values
		$values = array("ip_addr"=>$Subnets->transform_to_decimal($r['ip_addr']),
						"dns_name"=>$r['dns_name'],
						"subnetId"=>$_POST['subnetId'],
						"description"=>$r['description'],
						"state"=>2,
						"lastSeen"=>date("Y-m-d H:i:s"),
						"action"=>"add"
						);
        # custom fields
		if (isset($required_fields)) {
			foreach ($required_fields as $k=>$f) {
				$values[$f['name']] = $r[$f['name']];
			}
		}
		# insert
		if(!$Addresses->modify_address($values))	{ $Result->show("danger", "Failed to import entry ".$r['ip_addr'], false); $errors++; }
	}

	# success if no errors
	if($errors==0) {  $Result->show("success", _("Scan results added to database")."!", true); }
}
# error
else { $Result->show("danger", _("No entries available"), true); }
?>