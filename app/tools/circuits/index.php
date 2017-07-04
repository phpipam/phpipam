<?php

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
$hidden_circuit_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_circuit_fields = is_array(@$hidden_circuit_fields['circuits']) ? $hidden_circuit_fields['circuits'] : array();

$hidden_provider_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_provider_fields = is_array(@$hidden_provider_fields['circuitProviders']) ? $hidden_provider_fields['circuitProviders'] : array();

# menu
include("app/tools/circuits/menu.php");

# load subpage
if (!isset($_GET['subnetId']) || (@$_GET['subnetId']=="providers" && !isset($_GET['sPage'])) ) {
	// all circuits
	if(!isset($_GET['subnetId'])) {
		include('all-circuits.php');
	}
	// all providers
	else {
		include('all-providers.php');
	}
}
else {
	// specific provider
	if($_GET['subnetId']=="providers") {
		include("provider-details.php");
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
		include("circuit-details.php");
	}
}