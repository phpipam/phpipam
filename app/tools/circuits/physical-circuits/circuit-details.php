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
is_numeric($GET->subnetId) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch circuit
$circuit = $Tools->fetch_object ("circuits", "id", $GET->subnetId);

// back link
print "<div'>";
print "<a class='btn btn-sm btn-default' href='".create_link("tools","circuits")."' style='margin-bottom:10px;'><i class='fa fa-angle-left'></i> ". _('All circuits')."</a>";
print "</div>";

# print
if($circuit!==false) {
	// get custom fields
	$custom_fields = $Tools->fetch_custom_fields('circuits');
	$custom_provider_fields = $Tools->fetch_custom_fields('circuitProviders');
	// provider
	$provider = $Tools->fetch_object ("circuitProviders", "id", $circuit->provider);
	$logical_circuits = $Tools->fetch_all_logical_circuits_using_circuit($circuit->id);


	// overlay
	print "<div class='row'>";

		//
		// details
		//
		print "<div class='col-xs-12 col-md-6'>";
		// details
		print "<div class='col-xs-12'>";
		include("circuit-details-general.php");
	    print "</div>";

	    // connection points
		print "<div class='col-xs-12' style='margin-top:20px'>";
		include("circuit-details-points.php");
	    print "</div>";
		print "</div>";


		//
		// map
		//
		print "<div class='col-xs-12 col-md-6'>";
		if($User->settings->enableLocations==1) {
		print "<div class='col-xs-12'>";
		include("circuit-details-map.php");
	    print "</div>";
		}
	    print "</div>";


	    //
	    // providers
	    //
		print "<div class='col-xs-12' style='margin-top:50px;'>";
		print "<div class='col-xs-12'>";
		include("circuit-details-provider.php");
	    print "</div>";
	    print "</div>";

		print "</div>";

		//
		// logical circuits
		//
		print "<div class='col-xs-12' style='margin-top:50px;'>";
		print "<div class='col-xs-12'>";
		include("circuit-details-logical-parents.php");
		print "</div>";
		print "</div>";
	print "</div>";

}
else {
	$Result->show("danger", _("Invalid circuit id"), true);
}