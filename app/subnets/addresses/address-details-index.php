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
$hidden_cfields = json_decode($User->settings->hiddenCustomFields, true);
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
$resolve = $DNS->resolve_address($address['ip_addr'], $address['dns_name'], false, $subnet['nameserverId']);

# reformat empty fields
$address = $Addresses->reformat_empty_array_fields($address, "<span class='text-muted'>/</span>");



// header
print "<h4>"._('IP address details')."</h4><hr>";
// back
print "<a class='btn btn-default btn-sm btn-default' href='".create_link("subnets",$subnet['sectionId'],$subnet['id'])."' style='margin-bottom:20px;'><i class='fa fa-chevron-left'></i> "._('Back to subnet')."</a>";



# check if it exists, otherwise print error
if(sizeof($address)>1) {

    // print tabs
    print "<ul class='nav nav-tabs ip-det-switcher' style='margin-bottom:20px;'>";
    print " <li role='presentation' class='active' data-target='div_details'><a href='#'>"._("IP address details")."</a></li>";
    print " <li role='presentation' data-target='div_linked'><a href='#'>"._("Linked addresses")."</a></li>";
    print " <li role='presentation' data-target='div_changelog'><a href='#'>"._("Changelog")."</a></li>";
    print "</ul>";

    // address details
    print "<div class='div_det_common div_details'>";
	include("address-details.php");
    print "</div>";

    // address details
    print "<div class='div_det_common div_linked' style='display:none'>";
	include("address-details-linked.php");
    print "</div>";

    // changelog
    print "<div class='div_det_common div_changelog' style='display:none'>";
	include("address-changelog.php");
	print "</div>";
}
# not exisitng
else {
	$Result->show("danger", _("IP address not existing in database")."!", true);
}
?>

<script type="text/javascript">
$(document).ready(function() {
$('ul.ip-det-switcher li a').click(function() {
   var target = $(this).parent().attr("data-target");
   // class
   $('ul.ip-det-switcher li').removeClass('active');
   $(this).parent().addClass('active');
   // hide old
   $('div.div_det_common').hide();
   $('div.'+target).show();

   return false;
});
});
</script>
