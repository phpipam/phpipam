<?php

/**
 * Script to edit domain
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();
$PowerDNS 	= new PowerDNS ($Database);

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("pdns", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("pdns", User::ACCESS_RWA, true, false);
}

# strip input tags
$_POST = $Tools->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "domain", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# checks / validation
if ($_POST['action']!="delete") {
	// fqdn
	if ($_POST['action']=="add")
	if($Tools->validate_hostname($_POST['name'])===false)			{ $Result->show("danger", _("Invalid domain name"), true); }
	// master
	if (!is_blank($_POST['master'])) {
    	// if multilpe masters
    	if (strpos($_POST['master'], ",")!==false) {
        	// to array and trim, check each
        	$masters = array_filter(pf_explode(",", $_POST['master']));
        	foreach ($masters as $m) {
              if(!filter_var($m, FILTER_VALIDATE_IP))  { $Result->show("danger", _("Master must be an IP address"). " - ". $m, true); }
        	}
    	}
    	else {
          if(!filter_var($_POST['master'], FILTER_VALIDATE_IP))	{ $Result->show("danger", _("Master must be an IP address"). " - ". $_POST['master'], true); }
    	}
	}
	// type
	if(!in_array($_POST['type'], (array )$PowerDNS->domain_types))	{ $Result->show("danger", _("Invalid domain type"), true); }

	# new domain
	if ($_POST['action']=="add" && !isset($_POST['manual'])) {
		if ($Tools->validate_email($_POST['hostmaster'])===false)	{ $Result->show("danger", _("Invalid domain hostmaster"), true); }
	}

	// if slave master must be present
	if ($_POST['type']=="SLAVE") {
    	if (is_blank($_POST['master'])) { $Result->show("danger", _("Please set master server(s) if domain type is SLAVE"), true); }
        else {
        	if (strpos($_POST['master'], ",")!==false) {
            	// to array and trim, check each
            	$masters = array_filter(pf_explode(",", $_POST['master']));
            	foreach ($masters as $m) {
                  if(!filter_var($m, FILTER_VALIDATE_IP))  { $Result->show("danger", _("Master must be an IP address"). " - ". $m, true); }
            	}
        	}
        	else {
              if(!filter_var($_POST['master'], FILTER_VALIDATE_IP))	{ $Result->show("danger", _("Master must be an IP address"). " - ". $_POST['master'], true); }
        	}
    	}
	}


	# if update sve old domain !
	if ($_POST['action']=="edit") {
		$old_domain = $PowerDNS->fetch_domain ($_POST['id']);
	}
}

# set update array
$values = array("id"=>@$_POST['id'],
				"master"=>@$_POST['master'],
				"type"=>@$_POST['type']
				);
# name only on add
if ($_POST['action']=="add")
$values['name'] = $_POST['name'];


# remove all references if delete
if ($_POST['action']=="delete") 									{ $PowerDNS->remove_all_records ($values['id']); }

# for creation validate default records before creating them ! => true means check only
if ($_POST['action']=="add" && !isset($_POST['manual']))            { $PowerDNS->create_default_records ($_POST, true); }

# update
if(!$PowerDNS->domain_edit($_POST['action'], $values)) {
    $Result->show("danger", _("Failed to")." ".$_POST["action"]." "._("domain").'!', true);
}
else {
    $Result->show("success", _("Domain")." ".$_POST["action"]." "._("successful").'!', false);
}

# create default records
if ($_POST['action']=="add" && !isset($_POST['manual']))			{ $PowerDNS->create_default_records ($_POST); }
?>
