<?php

/**
 * Script to display available VLANs
 */

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Tools->fetch_all_objects("vlanDomains", "id");

# set default domain
if(sizeof($vlan_domains)==1) { $_GET['subnetId'] = 1; }

# vlan requested
if(isset($_GET['sPage']))										{ include("vlan-details.php"); }
# we have more domains
elseif(sizeof($vlan_domains)>1 && !isset($_GET['subnetId'])) 	{ include("domains.php"); }
# only 1 domain, print vlans
else 															{ include("domain-vlans.php"); }

?>