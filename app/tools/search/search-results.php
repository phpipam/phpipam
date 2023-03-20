<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# change * to % for database wildchar
$searchTerm = trim($searchTerm);
$searchTerm = str_replace("*", "%", $searchTerm);
$searchTerm_edited = ['high' => '', 'low' => ''];

// IP address low/high reformat
if (preg_match('/^[a-f0-9.:\/]+$/i', $searchTerm)) {
	// identify
	$type = $Addresses->identify_address($searchTerm); //identify address type

	# reformat if IP address for search
	if ($type == "IPv4") {
		$searchTerm_edited = $Tools->reformat_IPv4_for_search($searchTerm);
	}	//reformat the IPv4 address!
	elseif ($type == "IPv6") {
		$searchTerm_edited = $Tools->reformat_IPv6_for_search($searchTerm);
	}	//reformat the IPv4 address!
}

# set hidden custom fields
$hidden_fields = pf_json_decode($User->settings->hiddenCustomFields, true);

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = pf_explode(";", $selected_ip_fields);

$Params = new Params($_GET);

// all are off?
if (is_blank($Params->addresses) && is_blank($Params->subnets) && is_blank($Params->vlans) && is_blank($Params->vrf) && is_blank($Params->pstn) && is_blank($Params->circuits) && is_blank($Params->customers)) {
	require("search-tips.php");
}
// empty request
elseif (is_blank($Params->ip)) {
	require("search-tips.php");
}
// ok, search results print
else {
	# export button
	print '<a href="' . create_link(null) . '" id="exportSearch" rel="tooltip" data-post="' . escape_input($searchTerm) . '" title="' . _('Export All results to XLS') . '">';
	print '	<button class="btn btn-xs btn-default"><i class="fa fa-download"></i> ' . _('Export All results to XLS') . '</button>';
	print '</a>';


	#
	# Search and display
	#

	if (!is_blank($Params->ip)) {
		// subnets
		if ($Params->subnets == "on") {
			require(dirname(__FILE__) . '/search_results/search-results_subnets.php');
		}
		// addresses
		if ($Params->addresses == "on") {
			require(dirname(__FILE__) . '/search_results/search-results_addresses.php');
		}
		// vlan
		if ($Params->vlans == "on" && $User->get_module_permissions("vlan") >= User::ACCESS_R) {
			require(dirname(__FILE__) . '/search_results/search-results_vlans.php');
		}
		// vrf
		if ($Params->vrf == "on" && $User->get_module_permissions("vrf") >= User::ACCESS_R) {
			require(dirname(__FILE__) . '/search_results/search-results_vrfs.php');
		}
		// pstn
		if ($Params->pstn == "on" && $User->get_module_permissions("pstn") >= User::ACCESS_R) {
			require(dirname(__FILE__) . '/search_results/search-results_pstn.php');
		}
		// circuits
		if ($Params->circuits == "on" && $User->get_module_permissions("circuits") >= User::ACCESS_R) {
			require(dirname(__FILE__) . '/search_results/search-results_circuits.php');
		}
		// customers
		if ($Params->customers == "on" && $User->get_module_permissions("customers") >= User::ACCESS_R) {
			require(dirname(__FILE__) . '/search_results/search-results_customers.php');
		}
	}

	// export holder
	print '<div class="exportDIVSearch"></div>';
}
