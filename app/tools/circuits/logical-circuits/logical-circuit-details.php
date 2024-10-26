<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

/**
 * Script to display circuit details
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

# check
is_numeric($GET->sPage) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch circuit
$logical_circuit = $Tools->fetch_object ("circuitsLogical", "id", $GET->sPage);
$member_circuits = $Tools->fetch_all_logical_circuit_members($GET->sPage);

// back link
print "<div'>";
print "<a class='btn btn-sm btn-default' href='".create_link("tools","circuits", "logical")."' style='margin-bottom:10px;'><i class='fa fa-angle-left'></i> ". _('All Logical circuits')."</a>";
print "</div>";

# print
if($logical_circuit!==false) {
	//get custom fields
	$custom_fields = $Tools->fetch_custom_fields('circuitsLogical');

	// overlay
	print "<div class='row'>";

		//
		// details
		//
		print "<div class='col-xs-12 col-md-6'>";
		// details
		print "<div class='col-xs-12'>";
		include("logical-circuit-details-general.php");
	    print "</div>";

	    // connection points
		print "<div class='col-xs-12' style='margin-top:20px'>";
		include("logical-circuit-details-members.php");
	    print "</div>";
		print "</div>";


		//
		// map
		//
		print "<div class='col-xs-12 col-md-6'>";
		if($User->settings->enableLocations==1 && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
		print "<div class='col-xs-12'>";
		include("logical-circuit-details-map.php");
	    print "</div>";
		}
	    print "</div>";

    print "</div>";
}
else {
	$Result->show("danger", _("Invalid circuit id"), true);
}
