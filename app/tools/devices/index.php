<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();

# print link to manage
print "<div class='btn-group'>";
	//back button
	if(isset($_GET['sPage'])) { print "<a class='btn btn-sm btn-default' href='".create_link("tools","devices")."' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> ". _('Back')."</a>"; }
	//administer
	elseif($User->isadmin) 	  { print "<a class='btn btn-sm btn-default' href='".create_link("administration","devices")."' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> ". _('Manage')."</a>"; }
print "</div>";


# print hosts or all devices
if(isset($_GET['subnetId'])) {
	include('devices-hosts.php');

} else {
	print "<div class='devicePrintHolder'>";
	include('devices-print.php');
	print "</div>";
}

?>