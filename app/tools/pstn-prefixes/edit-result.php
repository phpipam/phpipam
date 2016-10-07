<?php

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# check permissions
if($Tools->check_prefix_permission ($User->user) <3)   { $Result->show("danger", _('You do not have permission to manage PSTN prefixes'), true); }

# validate csrf cookie
$User->csrf_cookie ("validate", "pstn", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if($_POST['action']=="delete" || $_POST['action']=="edit") {
    if($Admin->fetch_object ('pstnPrefixes', "id", $_POST['id'])===false) {
        $Result->show("danger",  _("Invalid PSTN object identifier"), false);
    }
}
if($_POST['action']=="add" || $_POST['action']=="edit") {
    // name
    if(strlen($_POST['name'])<3)                                        { $Result->show("danger",  _("Name must have at least 3 characters"), true); }

    // number
    if(!is_numeric($_POST['start']))                                    { $Result->show("danger",  _("Start must be numeric"), true); }
    if(!is_numeric($_POST['stop']))                                     { $Result->show("danger",  _("Stop must be numeric"), true); }

    // check master
    if($_POST['master']!=0) {
        $master_prefix = $Tools->fetch_object("pstnPrefixes", "id", $_POST['master']);
        if($master_prefix===false)                                      { $Result->show("danger",  _("Invalid master prefix"), true); }

        // ranges
        $master_prefix->prefix_raw = $Tools->prefix_normalize ($master_prefix->prefix);
        $master_prefix->prefix_raw_start = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->start);
        $master_prefix->prefix_raw_stop  = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->stop);

        $_POST['prefix_raw'] = $Tools->prefix_normalize ($_POST['prefix']);
        $_POST['prefix_raw_start'] = $Tools->prefix_normalize ($_POST['prefix'].$_POST['start']);
        $_POST['prefix_raw_stop']  = $Tools->prefix_normalize ($_POST['prefix'].$_POST['stop']);

        // prefix must be inside range
        if ($_POST['prefix_raw_start'] == $master_prefix->prefix_raw_start &&
            $_POST['prefix_raw_stop'] == $master_prefix->prefix_raw_stop)
                                                                        { $Result->show("danger",  _("Prefix cannot be same as master"), true); }
        if ($_POST['prefix_raw_start'] < $master_prefix->prefix_raw_start ||
            $_POST['prefix_raw_stop']  > $master_prefix->prefix_raw_stop)
                                                                        { $Result->show("danger",  _("Prefix not inside its master"), true); }
    }
}
// root check
if($_POST['action']=="add" && $_POST['master']==0) {
    // set raw values
    $_POST['prefix_raw'] = $Tools->prefix_normalize ($_POST['prefix']);
    $_POST['prefix_raw_start'] = $Tools->prefix_normalize ($_POST['prefix'].$_POST['start']);
    $_POST['prefix_raw_stop']  = $Tools->prefix_normalize ($_POST['prefix'].$_POST['stop']);
    $_POST['prefix_size'] = $_POST['prefix_raw_stop'] - $_POST['prefix_raw_start'];

    # fetch all
    $all_prefixes = $Tools->fetch_all_objects("pstnPrefixes", "master", 0);
    if($all_prefixes!==false) {
        foreach ($all_prefixes as $master_prefix) {

            $overlap_text = _("Prefix overlaps with prefix ".$master_prefix->name." (".$master_prefix->prefix.")");

            // ranges
            $master_prefix->prefix_raw = $Tools->prefix_normalize ($master_prefix->prefix);
            $master_prefix->prefix_raw_start = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->start);
            $master_prefix->prefix_raw_stop  = $Tools->prefix_normalize ($master_prefix->prefix.$master_prefix->stop);
            $master_prefix->prefix_size  = $master_prefix->prefix_raw_stop - $master_prefix->prefix_raw_start;

            // if it begins before
            if ($_POST['prefix_raw_start'] < $master_prefix->prefix_raw_start) {
                if($_POST['prefix_raw_stop'] >= $master_prefix->prefix_raw_start) {
                    { $Result->show("danger", $overlap_text, true); }
                }
            }
            elseif($_POST['prefix_raw_start'] > $master_prefix->prefix_raw_start) {
                if($_POST['prefix_raw_start'] <= $master_prefix->prefix_raw_stop) {
                    { $Result->show("danger", $overlap_text, true); }
                }
            }
            else    { $Result->show("danger", $overlap_text, true); }
        }
    }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnPrefixes');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($_POST[$myField['name']]>1) {
				$_POST[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && strlen($_POST[$myField['name']])==0) {
																		{ $Result->show("danger", $myField['name'].'" can not be empty!', true); }
		}
		# save to update array
		$update[$myField['name']] = $_POST[$myField['name']];
	}
}

// set values
$values = array(
    "id"=>@$_POST['id'],
    "name"=>$_POST['name'],
    "prefix"=>$_POST['prefix'],
    "start"=>$_POST['start'],
    "stop"=>$_POST['stop'],
    "deviceId"=>$_POST['deviceId'],
    "description"=>$_POST['description']
    );

# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# execute update
if(!$Admin->object_modify ("pstnPrefixes", $_POST['action'], "id", $values))    { $Result->show("danger",  _("Prefix $_POST[action] failed"), false); }
else																	        { $Result->show("success", _("Prefix $_POST[action] successful"), false); }

# if delete remove all slaves
if ($_POST['action']=="delete") {
    $values['master'] =  $values['id'];
	# remove all references from prefixes and remove all numbers
	$Admin->remove_object_references ("pstnPrefixes", "master", $values["id"], 0);
    $Admin->object_modify ("pstnNumbers", "delete", "prefix", $values);
}

?>
