<?php

/*
 * Script to display search results
 **********************************/

# verify that user is logged in
$User->check_user_session();

# change * to % for database wildchar
$searchTerm = trim($searchTerm);
$searchTerm = str_replace("*", "%", $searchTerm);

// IP address low/high reformat
if (preg_match('/^[a-f0-9.:\/]+$/i', $searchTerm)) {
    // identify
    $type = $Addresses->identify_address( $searchTerm ); //identify address type

    # reformat if IP address for search
    if ($type == "IPv4") 		{ $searchTerm_edited = $Tools->reformat_IPv4_for_search ($searchTerm); }	//reformat the IPv4 address!
    elseif($type == "IPv6") 	{ $searchTerm_edited = $Tools->reformat_IPv6_for_search ($searchTerm); }	//reformat the IPv4 address!
}

# set hidden custom fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);


// all are off?
if(!isset($_GET['addresses']) && !isset($_GET['subnets']) && !isset($_GET['vlans']) && !isset($_GET['vrf']) && !isset($_GET['pstn']) && !isset($_GET['circuits']) ) {
    include("search-tips.php");
}
// empty request
elseif (strlen($_GET['ip'])==0)  {
    include("search-tips.php");
}
// ok, search results print
else {
	# export button
	print '<a href="'.create_link(null).'" id="exportSearch" rel="tooltip" data-post="'.escape_input($searchTerm).'" title="'._('Export All results to XLS').'">';
	print '	<button class="btn btn-xs btn-default"><i class="fa fa-download"></i> '._('Export All results to XLS').'</button>';
	print '</a>';


	#
	# Search and display
	#

	// subnets
	if(@$_GET['subnets']=="on" && strlen($_GET['ip'])>0 ) 	{ include(dirname(__FILE__).'/search_results/search-results_subnets.php'); }
	// addresses
	if(@$_GET['addresses']=="on" && strlen($_GET['ip'])>0) 	{ include(dirname(__FILE__).'/search_results/search-results_addresses.php'); }
	// vlan
	if(@$_GET['vlans']=="on" && strlen($_GET['ip'])>0) 	    { include(dirname(__FILE__).'/search_results/search-results_vlans.php'); }
	// vrf
	if(@$_GET['vrf']=="on" && strlen($_GET['ip'])>0) 	    { include(dirname(__FILE__).'/search_results/search-results_vrfs.php'); }
	// pstn
	if(@$_GET['pstn']=="on" && strlen($_GET['ip'])>0) 	    { include(dirname(__FILE__).'/search_results/search-results_pstn.php'); }
	// circuits
	if(@$_GET['circuits']=="on" && strlen($_GET['ip'])>0) 	{ include(dirname(__FILE__).'/search_results/search-results_circuits.php'); }


	// export holder
	print '<div class="exportDIVSearch"></div>';
}