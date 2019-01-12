<?php

/**
 * Script to display available VLANs
 */

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Tools->fetch_all_objects("vlanDomains", "name");

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