<?php
/*
 * insert new hosts to database
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Tools      = new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# check for number of input values
$max = ini_get("max_input_vars");
if(sizeof($_POST)>=ini_get("max_input_vars")) 							{ $Result->show("danger", _("Number of discovered hosts exceed maximum possible defined by php.ini - set to ")." $max <hr>"._("Please adjust your php.ini settings for value `max_input_vars`"), true); }

// fetch custom fields and check for required
$required_fields = $Tools->fetch_custom_fields ('vlans');
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
	if(substr($key, 0,4)=="name") 			    { $res[substr($key, 4)]['name']  	    = $line; }
	// mac
	elseif(substr($key, 0,6)=="number") 		{ $res[substr($key, 6)]['number']  	    = $line; }
	// device
	elseif(substr($key, 0,8)=="domainId") 	    { $res[substr($key, 8)]['domainId']     = $line; }
	// description
	elseif(substr($key, 0,11)=="description") 	{ $res[substr($key, 11)]['description'] = $line; }
	// custom fields
	elseif (isset($required_fields)) {
    	foreach ($required_fields as $k=>$f) {
        	if((strpos($key, $f['name'])) !== false) {
                                                { $res[substr($key, strlen($f['name']))][$f['name']] = $line; }
        	}
    	}
	}
}

/*
print "<pre>";
var_dump($res);
die('alert-danger');
*/

# insert entries
if(sizeof($res)>0) {
	$errors = 0;
	foreach($res as $r) {
		# set insert values
		$values = array("number"=>$r['number'],
						"name"=>$r['name'],
						"domainId"=>$r['domainId'],
						"description"=>$r['description']
						);
        # custom fields
		if (isset($required_fields)) {
			foreach ($required_fields as $k=>$f) {
				$values[$f['name']] = $r[$f['name']];
			}
		}
        # insert vlans
        if(!$Admin->object_modify("vlans", "add", "vlanId", $values))	{ $Result->show("danger", _("Failed to import entry")." ".$r['number']." ".$r['name'], false); $errors++; }
	}

	# success if no errors
	if($errors==0) {  $Result->show("success", _("Scan results added to database")."!", true); }
}
# error
else { $Result->show("danger", _("No entries available"), true); }
?>