<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../functions/include-only.php' );

/**
 * Based on GET parameter we load:
 * 	- all circuits
 *  - all providers
 *  - specific circuit
 *  - specific provider
 *
 * For all circuits and all providers we also show menu
 *
 */

# verify that user is logged in
$User->check_user_session();

# get hidden fields
$hidden_circuit_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
$hidden_circuit_fields = is_array(@$hidden_circuit_fields['circuits']) ? $hidden_circuit_fields['circuits'] : array();

$hidden_provider_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
$hidden_provider_fields = is_array(@$hidden_provider_fields['circuitProviders']) ? $hidden_provider_fields['circuitProviders'] : array();

# menu
include("app/tools/circuits/menu.php");

# perm check
if ($User->get_module_permissions ("circuits")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# load subpage
elseif (!isset($_GET['subnetId']) || (@$_GET['subnetId']=="providers" && !isset($_GET['sPage'])) ) {
	// all circuits
	if(!isset($_GET['subnetId'])) {
		include('physical-circuits/all-circuits.php');
	}
	// all providers
	else {
		include('providers/all-providers.php');
	}
}
else {
	// specific provider
	if($_GET['subnetId']=="providers") {
		include("providers/provider-details.php");
	}
	elseif ($_GET['subnetId']=="logical") {
		if(isset($_GET["sPage"])){
			include("logical-circuits/logical-circuit-details.php");
		}else{
			include('logical-circuits/logical-circuits.php');
		}
	}
	// map
	elseif ($_GET['subnetId']=="circuit_map") {
		include('all-circuits-map.php');
	}
	// settings
	elseif ($_GET['subnetId']=="options") {
		include('options.php');
	}
	// specific circuit
	else {
		include("physical-circuits/circuit-details.php");
	}
}