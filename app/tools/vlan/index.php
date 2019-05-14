<?php

/**
 * Script to display available VLANs
 */

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Tools->fetch_all_objects("vlanDomains", "name");

# get custom fields
$custom_fields = (array) $Tools->fetch_custom_fields('vlanDomains');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vlanDomains']) ? $hidden_fields['vlanDomains'] : array();

# size of custom fields
$csize = sizeof($custom_fields) - sizeof($hidden_fields);
if($_GET['page']=="administration") { $csize++; }

# set default domain
if(sizeof($vlan_domains)==1) { $_GET['subnetId'] = 1; }

# perm check
if ($User->get_module_permissions ("vlan")<1) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# search vlan requested
elseif(@$_GET['subnetId']=="all")								{ include("domain-vlans-all.php"); }
# vlan requested
elseif(isset($_GET['sPage']))									{ include("vlan-details.php"); }
# print all domains
elseif(@$_GET['subnetId']=="all") 								{ include("domain-vlans-all.php"); }
# we have more domains
elseif(sizeof($vlan_domains)>1 && !isset($_GET['subnetId'])) 	{ include("domains.php"); }
# only 1 domain, print vlans
else 															{ include("domain-vlans.php"); }
