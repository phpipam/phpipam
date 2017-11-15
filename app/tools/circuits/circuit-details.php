<?php

/**
 * Script to display circuit details
 */

# verify that user is logged in
$User->check_user_session();

# check
is_numeric($_GET['subnetId']) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch circuit
$circuit = $Tools->fetch_object ("circuits", "id", $_GET['subnetId']);

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
}
else {
	$Result->show("danger", _("Invalid circuit id"), true);
}