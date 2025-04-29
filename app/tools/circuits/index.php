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
$hidden_circuit_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_circuit_fields = is_array(@$hidden_circuit_fields['circuits']) ? $hidden_circuit_fields['circuits'] : array();

$hidden_logical_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_logical_fields = is_array(@$hidden_logical_fields['circuits']) ? $hidden_logical_fields['circuitsLogical'] : array();

$hidden_provider_fields = db_json_decode($User->settings->hiddenCustomFields, true);
$hidden_provider_fields = is_array(@$hidden_provider_fields['circuitProviders']) ? $hidden_provider_fields['circuitProviders'] : array();

# menu
include("app/tools/circuits/menu.php");

# perm check
if ($User->get_module_permissions ("circuits")==User::ACCESS_NONE) {
	$Result->show("danger", _("You do not have permissions to access this module"), false);
}
# load subpage
elseif (!isset($GET->subnetId) || ($GET->subnetId=="providers" && !isset($GET->sPage)) ) {
	// all circuits
	if(!isset($GET->subnetId)) {
		include('physical-circuits/all-circuits.php');
	}
	// all providers
	else {
		include('providers/all-providers.php');
	}
}
else {
	// specific provider
	if($GET->subnetId=="providers") {
		include("providers/provider-details.php");
	}
	elseif ($GET->subnetId=="logical") {
		if(isset($GET->sPage)){
			include("logical-circuits/logical-circuit-details.php");
		}else{
			include('logical-circuits/logical-circuits.php');
		}
	}
	// map
	elseif ($GET->subnetId=="circuit_map") {
		include('all-circuits-map.php');
	}
	// settings
	elseif ($GET->subnetId=="options") {
		include('options.php');
	}
	// specific circuit
	else {
		include("physical-circuits/circuit-details.php");
	}
}
