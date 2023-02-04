<?php

/**
 * Script to display customer details
 *
 */

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("customers", User::ACCESS_R, true);

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);
# fetch customer
$customer = $Tools->fetch_object("customers", "title", urldecode($_GET['subnetId']));

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('customers');
# get hidden fields */
$hidden_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['customers']) ? $hidden_fields['customers'] : array();

# structure and include details
print "<div class='row'>";

// invlid ?
if ($customer===false) {
	$Result->show ("danger", _("Invalid customer"), false);
}
else {
	// details
	print "<div class='cols-xs-12 col-md-6'>";
	include ("details.php");
	print "</div>";

	// map
	print "<div class='cols-xs-12 col-md-6'>";
	if($User->settings->enableLocations==1)
	include ("map.php");
	print "</div>";

	// objects
	print "<div class='cols-xs-12'>";
	include ("objects.php");
	print "</div>";
}
print "</div>";