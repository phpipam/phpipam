<?php

/**
 * Move VLAN to new domain
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
$User->check_module_permissions ("vlan", User::ACCESS_RW, true, false);

// checks
if(!is_numeric($POST->newDomainId))			$Result->show("danger", _("Invalid ID"), true);
if(!is_numeric($POST->vlanid))				$Result->show("danger", _("Invalid ID"), true);

// verify that new exists
$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $POST->newDomainId);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true); }

//fetch vlan
$vlan = $Admin->fetch_object("vlans", "vlanid", $POST->vlanid);
if($vlan===false)					{ $Result->show("danger", _("Invalid ID"), true); }

// check that it is not already set !
if($User->settings->vlanDuplicate==0) {
	$check_vlan = $Admin->fetch_multiple_objects ("vlans", "domainId", $vlan_domain->id, "vlanid");
	if($check_vlan!==false) {
		foreach($check_vlan as $v) {
			if($v->number == $vlan->number) {
									{ $Result->show("danger", _("VLAN already exists"), true); }
			}
		}
	}
}

# formulate update query
$values = array(
				"vlanid"   =>$POST->vlanid,
				"domainId" =>$vlan_domain->id
				);
# update
if(!$Admin->object_modify("vlans", "edit", "vlanid", $values))	{ $Result->show("danger",  _("Failed to move VLAN to new domain").'!', true); }
else															{ $Result->show("success", _("VLAN moved to new domain successfully").'!', false); }