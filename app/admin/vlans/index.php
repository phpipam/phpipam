<?php

/**
 *	Print all available VLANs and configurations
 ************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");

# set default domain
if(sizeof($vlan_domains)==1) { $_GET['subnetId'] = 1; }
?>


<h4><?php print _('Manage VLANs'); ?></h4>
<hr><br>

<?php
# we have more domains
if(sizeof($vlan_domains)>1 && !isset($_GET['subnetId'])) 	{ include("print-domains.php"); }
# only 1 domain, print vlans
else 														{ include("print-domain-vlans.php"); }

?>
