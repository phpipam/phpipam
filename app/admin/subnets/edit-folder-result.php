<?php

/**
 * Function to add / edit / delete section
 ********************************************/

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

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
if($POST->action=="add") {
	$User->Crypto->csrf_cookie ("validate", "folder_add", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
} else {
	$User->Crypto->csrf_cookie ("validate", "folder_".$POST->subnetId, $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
}

# ID must be numeric
if($POST->action=="add") {
	if(!is_numeric($POST->sectionId))	{ $Result->show("danger", _("Invalid ID"), true); }
} else {
	if(!is_numeric($POST->subnetId))		{ $Result->show("danger", _("Invalid ID"), true); }
}

# verify that user has permissions to add subnet
if($POST->action=="add") {
	if($Sections->check_permission ($User->user, $POST->sectionId) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true); }
}
# otherwise check subnet permission
else {
	if($Subnets->check_permission ($User->user, $POST->subnetId) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true); }
}

# we need old values for mailing
if($POST->action=="edit" || $POST->action=="delete") {
	$subnet_old_details = (array) $Subnets->fetch_subnet(null, $POST->subnetId);
}

# get section details
$section = (array) $Sections->fetch_section(null, $POST->sectionId);
# fetch custom fields
$custom = $Tools->fetch_custom_fields('subnets');

//custom
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
		if(isset($POST->{$myField['nameTest']})) { $POST->{$myField['name']} = $POST->{$myField['nameTest']};}
	}
}

//remove subnet-specific fields
unset ($POST->subnet,$POST->allowRequests,$POST->showName,$POST->pingSubnet,$POST->discoverSubnet);
unset ($subnet_old_details['subnet'],$subnet_old_details['allowRequests'],$subnet_old_details['showName'],$subnet_old_details['pingSubnet'],$subnet_old_details['discoverSubnet']);

# Set permissions if adding new subnet
if($POST->action=="add") {
	# root
	if($POST->masterSubnetId==0) {
		$POST->permissions = $section['permissions'];
	}
	# nested - inherit parent permissions
	else {
		# get parent
		$parent = $Subnets->fetch_subnet(null, $POST->masterSubnetId);
		$POST->permissions = $parent->permissions;
	}
}
elseif ($POST->action=="edit") {
    /* for nesting - MasterId cannot be the same as subnetId! */
    if ( $POST->masterSubnetId==$POST->subnetId ) {
    	$Result->show("danger", _('Folder cannot nest behind itself!'), true);
    }
}

//check for name length - 2 is minimum!
if(strlen($POST->description)<2 && $POST->action!="delete") { $Result->show("danger", _('Folder name must have at least 2 characters')."!", true); }
//custom fields
if(sizeof($custom) > 0 && $POST->action!="delete") {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($POST->{$myField['name']}>1) {
				$POST->{$myField['name']} = "";
			}
		}
		//not empty
		if($myField['Null']=="NO" && is_blank($POST->{$myField['name']})) {
			$errors[] = "Field \"$myField[name]\" cannot be empty!";
		}
	}
}

# delete and not yet confirmed
if ($POST->action=="delete" && !isset($POST->deleteconfirm)) {
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
	print "	<a class='btn btn-sm btn-danger editFolderSubmitDelete' id='editFolderSubmitDelete' data-subnetId='".escape_input($POST->subnetId)."'>"._("Confirm")."</a>";
	print "</div>";
	print "</div>";
	print "</div>";
}
# execute
else {

	# create array of default update values
	$values = array(
					"id"             => $POST->subnetId,
					"isFolder"       => 1,
					"masterSubnetId" => $POST->masterSubnetId,
					"description"    => $POST->description
					);
	# for new subnets we add permissions
	if($POST->action=="add") {
		$values['permissions'] = $POST->permissions;
		$values['sectionId']   = $POST->sectionId;
	}
	else {
		# if section change
		if($POST->sectionId != $POST->sectionIdNew) {
			$values['sectionId'] = $POST->sectionIdNew;
		}
	}
	# append custom fields
	$custom = $Tools->fetch_custom_fields('subnets');
	if(sizeof($custom) > 0) {
		foreach($custom as $myField) {

			//replace possible ___ back to spaces
			$myField['nameTest'] = str_replace(" ", "___", $myField['name']);
			if(isset($POST->{$myField['nameTest']})) { $POST->{$myField['name']} = $POST->{$myField['nameTest']};}

			//booleans can be only 0 and 1!
			if($myField['type']=="tinyint(1)") {
				if($POST->{$myField['name']}>1) {
					$POST->{$myField['name']} = 0;
				}
			}
			//not null!
			if ($POST->action!="delete") {
          if($myField['Null']=="NO" && is_blank($POST->{$myField['name']})) { $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }
      }

			# save to update array
			$values[$myField['name']] = $POST->{$myField['name']};
		}
	}

	# execute
	if(!$Subnets->modify_subnet ($POST->action, $values))	{ $Result->show("danger", _('Error editing folder'), true); }
	else {
		# update also all slave subnets!
		if(isset($values['sectionId']) && $POST->action=="edit") {
			$Subnets->reset_subnet_slaves_recursive();
			$Subnets->fetch_subnet_slaves_recursive($POST->subnetId);
			$Subnets->remove_subnet_slaves_master($POST->subnetId);

			if(sizeof($Subnets->slaves)>0) {
				foreach($Subnets->slaves as $slaveId) {
					$Admin->object_modify ("subnets", "edit", "id", array("id"=>$slaveId, "sectionId"=>$values['sectionId']));
				}
			}
		}
		# delete
		elseif ($POST->action=="delete") {
			$Subnets->reset_subnet_slaves_recursive();
			$Subnets->fetch_subnet_slaves_recursive($POST->subnetId);
			$Subnets->remove_subnet_slaves_master($POST->subnetId);

			if(sizeof($Subnets->slaves)>0) {
				foreach($Subnets->slaves as $slaveId) {
					$Admin->object_modify ("subnets", "delete", "id", array("id"=>$slaveId));
				}
			}
		}

		# edit success
		if($POST->action=="delete")	{ $Result->show("success", _('Folder, IP addresses and all belonging subnets deleted successfully').'!', false); }
		else { $Result->show("success", _("Folder")." ".$User->get_post_action()." "._("successful").'!', true); }
	}
}
