<?php

/**
 * Edit rack result
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("racks", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("racks", User::ACCESS_RWA, true, true);
}

# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "rack", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# get modified details
$rack = $Tools->strip_input_tags($_POST);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['rackid']))			{ $Result->show("danger", _("Invalid ID"), true); }

# Hostname must be present
if($rack['name'] == "") 											    { $Result->show("danger", _('Name is mandatory').'!', true); }

# rack checks
# validate position and size
if (!is_numeric($rack['size']))                                         { $Result->show("danger", _('Invalid rack size').'!', true); }
# validate rack
if ($rack['action']=="edit") {
    if (!is_numeric($rack['rackid']))                                   { $Result->show("danger", _('Invalid rack identifier').'!', true); }
    $rack_details = $Racks->fetch_rack_details ($rack['rackid']);
    if ($rack_details===false)                                          { $Result->show("danger", _('Rack does not exist').'!', true); }
}
elseif($rack['action']=="delete") {
    if (!is_numeric($rack['rackid']))                                   { $Result->show("danger", _('Invalid rack identifier').'!', true); }
}

# check if rack shrinks that no overflow of devices ocur
if($_POST['action']=="edit" && @$rack['hasBack']=="1" && $rack['size'] < $rack_details->size ) {
	// fetch all devices
	$rack_devices = $Racks->fetch_rack_devices ($rack_details->id);
	// split to front / back
	if (is_array($rack_devices)) {
		foreach ($rack_devices as $d) {
			// front devices
			if($d->rack_start <= $rack_details->size) {
				if (($d->rack_start + $d->rack_size -1) > $rack['size']) { $Result->show("danger", _('Device')." $d->hostname ".("is out of bounds for new rack size"."!"), true); }
			}
			// back devices
			else {
				if (($d->rack_start - $rack_details->size + $d->rack_size -1) > $rack['size']) { $Result->show("danger", _('Device')." $d->hostname ".("is out of bounds for new rack size"."!"), true); }
			}
		}
	}

    // fetch all custom devices
    $rack_content = $Racks->fetch_rack_contents ($rack_details->id);
    // split to front / back
    if (is_array($rack_content)) {
        foreach ($rack_content as $d) {
            // front devices
            if($d->rack_start <= $rack_details->size) {
                if (($d->rack_start + $d->rack_size -1) > $rack['size']) { $Result->show("danger", _('Device')." $d->hostname ".("is out of bounds for new rack size"."!"), true); }
            }
            // back devices
            else {
                if (($d->rack_start - $rack_details->size + $d->rack_size -1) > $rack['size']) { $Result->show("danger", _('Device')." $d->hostname ".("is out of bounds for new rack size"."!"), true); }
            }
        }
    }
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');
if(sizeof($custom) > 0) {
	foreach($custom as $myField) {
		//booleans can be only 0 and 1!
		if($myField['type']=="tinyint(1)") {
			if($rack[$myField['name']]>1) {
				$rack[$myField['name']] = 0;
			}
		}
		//not null!
		if($myField['Null']=="NO" && is_blank($rack[$myField['name']])) {
			{ $Result->show("danger", $myField['name']." "._("can not be empty!"), true); }
		}
		# save to update array
		$update[$myField['name']] = $rack[$myField['name']];
	}
}

# set update values
$values = array(
				"id"          => @$rack['rackid'],
				"name"        => @$rack['name'],
				"size"        => @$rack['size'],
				"hasBack"     => $Admin->verify_checkbox(@$rack['hasBack']),
                "topDown"     => @$rack['topDown'],
				"description" => @$rack['description']
				);
# custom fields
if(isset($update)) {
	$values = array_merge($values, $update);
}

# append location
if ($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_RW) {
    if (is_numeric($_POST['location'])) {
        $values['location'] = $_POST['location'] > 0 ? $_POST['location'] : NULL;
    }
}

# append customerId
if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_RW) {
    if (is_numeric($_POST['customer_id'])) {
        $values['customer_id'] = $_POST['customer_id'] > 0 ? $_POST['customer_id'] : NULL;
    }
}

# update rack
if(!$Admin->object_modify("racks", $_POST['action'], "id", $values))	{}
else { $Result->show("success", _("Rack")." ".$rack["action"]." "._("successful").'!', false); }

if($_POST['action']=="delete"){
	# remove all references from subnets and ip addresses
	$Admin->remove_object_references ("devices", "rack", $values["id"], NULL);
    # remove all custom devices for the rack
    try { $Database->runQuery("delete from rackContents where `rack` = ?", array($values['id'])); }
    catch (Exception $e) {}
}
# remove all devices if back is removed
if($_POST['action']=="edit" && @$rack['hasBack']!="1") {
    try { $Database->runQuery("update devices set `rack` = 0 where `rack` = ? and rack_start > ?;", array($rack['rackid'], $rack['size'])); }
    catch (Exception $e) {}
    try { $Database->runQuery("delete from rackContents where `rack` = ? and rack_start > ?;", array($rack['rackid'], $rack['size'])); }
    catch (Exception $e) {}
}
# update positions of rack devices when rack size changes
if($_POST['action']=="edit" && @$rack['hasBack']=="1" && $rack_details->size!=$rack['size'] ) {
	$values = array (
						"rackid"   => $rack_details->id,
						"new_size" => $rack['size'],
						"old_size" => $rack_details->size
	                 );
    try { $Database->runQuery("UPDATE `devices` set `rack_start` = `rack_start` + :new_size - :old_size where `rack` = :rackid and rack_start > :old_size", $values); }
    catch (Exception $e) {}
    try { $Database->runQuery("UPDATE `rackContents` set `rack_start` = `rack_start` + :new_size - :old_size where `rack` = :rackid and rack_start > :old_size", $values); }
    catch (Exception $e) {}
}
