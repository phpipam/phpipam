<?php

/**
 * Move VLAN to new domain
 *******************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

// checks
if(!is_numeric($_POST['newDomainId']))			$Result->show("danger", _("Invalid ID"), true);
if(!is_numeric($_POST['vlanId']))				$Result->show("danger", _("Invalid ID"), true);

// verify that new exists
$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $_POST['newDomainId']);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true); }

//fetch vlan
$vlan = $Admin->fetch_object("vlans", "vlanId", $_POST['vlanId']);
if($vlan===false)					{ $Result->show("danger", _("Invalid ID"), true); }

// check that it is not already set !
if($User->settings->vlanDuplicate==0) {
	$check_vlan = $Admin->fetch_multiple_objects ("vlans", "domainId", $vlan_domain->id, "vlanId");
	if($check_vlan!==false) {
		foreach($check_vlan as $v) {
			if($v->number == $vlan->number) {
									{ $Result->show("danger", _("VLAN already exists"), true); }
			}
		}
	}
}

# formulate update query
$values = array("vlanId"=>@$_POST['vlanId'],
				"domainId"=>$vlan_domain->id
				);
# update
if(!$Admin->object_modify("vlans", "edit", "vlanId", $values))	{ $Result->show("danger",  _("Failed to move VLAN to new domain").'!', true); }
else															{ $Result->show("success", _("VLAN moved to new domain successfully").'!', false); }
?>