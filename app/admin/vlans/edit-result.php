<?php

/**
 * Script to edit VLAN details
 *******************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("vlan", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("vlan", User::ACCESS_RWA, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "vlan", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vlans');

//if it already exist die
if($User->settings->vlanDuplicate==0 && ($POST->action=="add" || $POST->action=="edit")) {
	$check_vlan = $Admin->fetch_multiple_objects ("vlans", "domainId", $POST->domainid, "vlanId");
	// check
	if($check_vlan!==false) {
		foreach($check_vlan as $v) {
			if ($v->vlanId==$POST->vlanid) {}
			elseif($v->number == $POST->number) {
																			{ $Result->show("danger", _("VLAN already exists"), true); }
			}
		}
	}
}

// if unique required
if (isset($POST->unique)) {
	if ($POST->unique=="on") {
		if ($Tools->fetch_object ("vlans", "number", $POST->number)!==false) { $Result->show("danger", _("VLAN already exists in another domain!"), true); }
	}
}

//if number too high
if($POST->number>$User->settings->vlanMax && $POST->action!="delete")	{ $Result->show("danger", _('Highest possible VLAN number is ').$User->settings->vlanMax.'!', true); }
if($POST->action=="add") {
	if($POST->number<0)													{ $Result->show("danger", _('VLAN number cannot be negative').'!', true); }
	elseif(!is_numeric($POST->number))									{ $Result->show("danger", _('Not number').'!', true); }
}
if(is_blank($POST->name))												{ $Result->show("danger", _('Name is required').'!', true); }


# formulate update query
$values = array(
				"vlanId"      => $POST->vlanid,
				"number"      => $POST->number,
				"name"        => $POST->name,
				"description" => $POST->description,
				"domainId"    => $POST->domainid
				);

# append custom
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
		if(isset($POST->{$myField['nameTest']})) { $values[$myField['name']] = $POST->{$myField['nameTest']};}
	}
}
# append customerId
if($User->settings->enableCustomers=="1") {
	if (is_numeric($POST->customer_id)) {
		if ($POST->customer_id>0) {
			$values['customer_id'] = $POST->customer_id;
		}
		else {
			$values['customer_id'] = null;
		}
	}
}


# update
if(!$Admin->object_modify("vlans", $POST->action, "vlanId", $values))	{ $Result->show("danger",  _("Failed to ".$POST->action." VLAN").'!', true); }
else																		{ $Result->show("success", _("VLAN ".$POST->action." successful").'!', false); }

# remove all references if delete
if($POST->action=="delete") { $Admin->remove_object_references ("subnets", "vlanId", $POST->vlanid); }

# print value for on the fly
if($POST->action=="add")	   { print '<p id="vlanidforonthefly"    style="display:none">'.$Admin->lastId.'</p>'; }
