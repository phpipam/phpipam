<?php

/**
 *
 * This script refreshes PTR records for subnet
 *
 */

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();
$Subnets 	= new Subnets ($Database);
$Addresses 	= new Addresses ($Database);
$PowerDNS 	= new PowerDNS ($Database);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# fetch subnet
$subnet = $Subnets->fetch_subnet ("id", $_POST['subnetId']);

# checks
if ($subnet===false)					{ $Result->show("danger", _("Invalid subnet"), true); }
if ($subnet->DNSrecursive!=1)			{ $Result->show("danger", _("Automatic PTR creation for this subnet is disabled"), true); }
if ($User->settings->enablePowerDNS!=1) { $Result->show("danger", _("PowerDNS not enabled"), true); }


// set zone
$zone = $PowerDNS->get_ptr_zone_name ($subnet->ip, $subnet->mask);
// try to fetch domain
$domain = $PowerDNS->fetch_domain_by_name ($zone);
// default values
$values = json_decode($User->settings->powerDNS, true);
$values['name'] = $zone;

// domain missing, create it and default records
if ($domain===false) {
	// create domain
	$PowerDNS->domain_edit ("add", array("name"=>$zone,"type"=>"NATIVE"));
	// create default records
	$PowerDNS->create_default_records ($values);
}

// fetch PTR records for current domain
$ptr_indexes = $Addresses->ptr_get_subnet_indexes ($subnet->id);

// remove existing records and links
$PowerDNS->remove_all_ptr_records ($domain->id, $ptr_indexes);
$Addresses->ptr_unlink_subnet_addresses ($subnet->id);

// fetch all hosts
$hosts   = $Addresses->fetch_subnet_addresses ($subnet->id, "ip_addr", "asc");

// create PTR records
if (sizeof($hosts)>0) {
	foreach ($hosts as $h) {
    	// set default hostname for PTR if set
    	if (strlen($h->dns_name)==0) {
        	if (strlen($values['def_ptr_domain'])>0) {
            	$h->dns_name = $values['def_ptr_domain'];
        	}
    	}
		// ignore PTR
		if ($h->PTRignore == "1") {
			$ignored[] = $h;
		}
		// validate hostname, we only add valid hostnames
		elseif ($Result->validate_hostname ($h->dns_name) !== false) {
			// formulate new record
			$record = $PowerDNS->formulate_new_record ($domain->id, $PowerDNS->get_ip_ptr_name ($h->ip), "PTR", $h->dns_name, $values['ttl']);
			// insert record
			$PowerDNS->add_domain_record ($record, false);

			// link
			$Addresses->ptr_link ($h->id, $PowerDNS->lastId);

			// ok
			$success[] = $h;
		}
		// false
		else {
			$failures[] = $h;
		}
	}
}
else 										{ $empty = true; }


# generate print
if (sizeof(@$success)>0) {
	$print[] = "<div class='alert alert-success'><h4>Successful PTR records:</h4>";
	foreach ($success as $s) {
		$print[] = $PowerDNS->get_ip_ptr_name ($s->ip)." > ". $s->dns_name;
	}
	$print[] = "</div>";
}
if (sizeof(@$failures)>0) {
	$print[] = "<div class='alert alert-danger'><h4>Invalid PTR hostnames:</h4>";
	foreach ($failures as $s) {
		$print[] = "&middot; $s->dns_name ($s->ip)";
	}
	$print[] = "</div>";
}
if (sizeof(@$ignored)>0) {
	$print[] = "<div class='alert alert-info'><h4>Ignored records:</h4>";
	foreach ($ignored as $s) {
		$print[] = "&middot; $s->dns_name ($s->ip)";
	}
	$print[] = "</div>";
}
if(isset($empty)) {
	$print[] = "<div class='alert alert-warning'>Subnet is empty!</div>";
}


print "<p class='hidden alert-danger'></p>";
print implode("<br>", $print);


?>