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

# search vlan requested
if(@$_GET['subnetId']=="all")									{ include("domain-vlans-all.php"); }
# vlan requested
elseif(isset($_GET['sPage']))									{ include("vlan-details.php"); }
# print all domains
elseif(@$_GET['subnetId']=="all") 								{ include("domain-vlans-all.php"); }
# we have more domains
elseif(sizeof($vlan_domains)>1 && !isset($_GET['subnetId'])) 	{ include("domains.php"); }
# only 1 domain, print vlans
else 															{ include("domain-vlans.php"); }

?>