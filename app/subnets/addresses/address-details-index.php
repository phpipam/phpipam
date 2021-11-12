<?php

/**
 * Script to display IP address info and history
 ***********************************************/

# verify that user is logged in
$User->check_user_session();

# powerdns class
$PowerDNS = new PowerDNS ($Database);

# checks
if(!is_numeric($_GET['subnetId']))		{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_GET['section']))		{ $Result->show("danger", _("Invalid ID"), true); }
if(!is_numeric($_GET['ipaddrid']))		{ $Result->show("danger", _("Invalid ID"), true); }

# get IP a nd subnet details
$address = (array) $Addresses-> fetch_address(null, $_GET['ipaddrid']);
$subnet  = (array) $Subnets->fetch_subnet(null, $address['subnetId']);

# fetch all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
# set hidden custom fields
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true) ? : ['ipaddresses' => null];
$hidden_cfields = is_array($hidden_cfields['ipaddresses']) ? $hidden_cfields['ipaddresses'] : array();

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);																			//format to array
$selected_ip_fields_size = in_array('state', $selected_ip_fields) ? (sizeof($selected_ip_fields)-1) : sizeof($selected_ip_fields);	//set size of selected fields
if($selected_ip_fields_size==1 && strlen($selected_ip_fields[0])==0) { $selected_ip_fields_size = 0; }								//fix for 0


# set ping statuses
$statuses = explode(";", $User->settings->pingStatus);

# permissions
$subnet_permission  = $Subnets->check_permission($User->user, $subnet['id']);
$section_permission = $Sections->check_permission ($User->user, $subnet['sectionId']);

# checks
if(sizeof($subnet)==0) 					{ $Result->show("danger", _('Subnet does not exist'), true); }									//subnet doesnt exist
if($subnet_permission == 0)				{ $Result->show("danger", _('You do not have permission to access this network'), true); }		//not allowed to access

 # resolve dns name
$DNS = new DNS ($Database);
$resolve = $DNS->resolve_address($address['ip_addr'], $address['hostname'], false, $subnet['nameserverId']);

# reformat empty fields
$address = $Addresses->reformat_empty_array_fields($address, "<span class='text-muted'>/</span>");

# multicast
$mcast = $User->settings->enableMulticast=="1" && $Subnets->is_multicast ($Subnets->transform_address($address['ip_addr'], "dotted")) ? true : false;


// header
print "<h4>"._('IP address details')." ".$Subnets->transform_to_dotted( $address['ip_addr'])."</h4><hr>";
// back
if($subnet['isFolder']==1)
print "<a class='btn btn-default btn-sm btn-default' href='".create_link("folder",$subnet['sectionId'],$subnet['id'])."' style='margin-bottom:20px;'><i class='fa fa-chevron-left'></i> "._('Back to folder')."</a>";
else
print "<a class='btn btn-default btn-sm btn-default' href='".create_link("subnets",$subnet['sectionId'],$subnet['id'])."' style='margin-bottom:20px;'><i class='fa fa-chevron-left'></i> "._('Back to subnet')."</a>";


# check if it exists, otherwise print error
if(sizeof($address)>1) {

    # NAT search
    $all_nats = array();
    $all_nats_per_object = array();

    if ($User->settings->enableNAT==1) {
        # fetch all object
        $all_nats = $Tools->fetch_all_objects ("nat", "name");

        if ($all_nats!==false) {
            foreach ($all_nats as $n) {
                $out[$n->id] = $n;
            }
            $all_nats = $out;

            # reindex
            $all_nats_per_object = $Tools->reindex_nat_objects ($all_nats);
        }
    }


    // print tabs
    print "<ul class='nav nav-tabs ip-det-switcher' style='margin-bottom:20px;'>";
    $active = !isset($_GET['tab']) ? "active" : "";
    print " <li role='presentation' class='$active' data-target='div_details'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'])."'>"._("IP address details")."</a></li>";
    if($User->is_admin(false)) {
    $active = @$_GET['tab']=="permissions" ? "active" : "";
    print " <li role='presentation' class='$active'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "permissions")."'>"._("Permissions")."</a></li>";
    }
    if($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>=User::ACCESS_R) {
    $active = @$_GET['tab']=="nat" ? "active" : "";
    print " <li role='presentation' class='$active'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "nat")."'>"._("NAT")."</a></li>";
    }
    $active = @$_GET['tab']=="linked" ? "active" : "";
    print " <li role='presentation' class='$active'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "linked")."'>"._("Linked addresses")."</a></li>";
    if($mcast) {
    $active = @$_GET['tab']=="multicast" ? "active" : "";
    print " <li role='presentation' class='$active'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "multicast")."'>"._("Multicast")."</a></li>";
    }
    if($User->settings->enableLocations==1) {
    $active = @$_GET['tab']=="location" ? "active" : "";
    print " <li role='presentation' class='$active'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "location")."'>"._("Location")."</a></li>";
    }
    $active = @$_GET['tab']=="changelog" ? "active" : "";
    print " <li role='presentation' class='$active'><a href='".create_link("subnets", $subnet['sectionId'], $subnet['id'], "address-details", $address['id'], "changelog")."'>"._("Changelog")."</a></li>";
    print "</ul>";

    // address details
    if(!isset($_GET['tab']))
	include("address-details/address-details.php");

    // address details
    if($User->is_admin(false)) {
    if(@$_GET['tab']=="permissions")
	include("address-details/address-details-permissions.php");
    }

    //nat
    if($User->settings->enableNAT==1) {
    if(@$_GET['tab']=="nat")
	include("address-details/address-details-nat.php");
    }

    // Linked addresses
    if(@$_GET['tab']=="linked")
	include("address-details/address-details-linked.php");

    // Multicast addresses
    if($mcast) {
    if(@$_GET['tab']=="multicast")
	include("address-details/address-details-multicast.php");
    }

    if(@$_GET['tab']=="location")
    include("address-details/address-details-location.php");

    // changelog
    if(@$_GET['tab']=="changelog")
	include("address-details/address-changelog.php");
}
# not exisitng
else {
	$Result->show("danger", _("IP address not existing in database")."!", true);
}
?>
