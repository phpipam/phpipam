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
if($POST->action=="edit") {
    $User->check_module_permissions ("pdns", User::ACCESS_RW, true, false);
}
else {
    $User->check_module_permissions ("pdns", User::ACCESS_RWA, true, false);
}

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "domain", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# checks / validation
if ($POST->action!="delete") {
	// fqdn
	if ($POST->action=="add")
	if($Tools->validate_hostname($POST->name)===false)			{ $Result->show("danger", _("Invalid domain name"), true); }
	// master
	if (!is_blank($POST->master)) {
    	// if multiple masters
    	if (strpos($POST->master, ",")!==false) {
        	// to array and trim, check each
        	$masters = array_filter(pf_explode(",", $POST->master));
        	foreach ($masters as $m) {
              if(!filter_var($m, FILTER_VALIDATE_IP))  { $Result->show("danger", _("Master must be an IP address"). " - ". $m, true); }
        	}
    	}
    	else {
          if(!filter_var($POST->master, FILTER_VALIDATE_IP))	{ $Result->show("danger", _("Master must be an IP address"). " - ". escape_input($POST->master), true); }
    	}
	}
	// type
	if(!in_array($POST->type, (array )$PowerDNS->domain_types))	{ $Result->show("danger", _("Invalid domain type"), true); }

	# new domain
	if ($POST->action=="add" && !isset($POST->manual)) {
		if ($Tools->validate_email($POST->hostmaster)===false)	{ $Result->show("danger", _("Invalid domain hostmaster"), true); }
	}

	// if slave master must be present
	if ($POST->type=="SLAVE") {
    	if (is_blank($POST->master)) { $Result->show("danger", _("Please set master server(s) if domain type is SLAVE"), true); }
        else {
        	if (strpos($POST->master, ",")!==false) {
            	// to array and trim, check each
            	$masters = array_filter(pf_explode(",", $POST->master));
            	foreach ($masters as $m) {
                  if(!filter_var($m, FILTER_VALIDATE_IP))  { $Result->show("danger", _("Master must be an IP address"). " - ". $m, true); }
            	}
        	}
        	else {
              if(!filter_var($POST->master, FILTER_VALIDATE_IP))	{ $Result->show("danger", _("Master must be an IP address"). " - ". escape_input($POST->master), true); }
        	}
    	}
	}


	# if update sve old domain !
	if ($POST->action=="edit") {
		$old_domain = $PowerDNS->fetch_domain ($POST->id);
	}
}

# set update array
$values = array("id"=>$POST->id,
				"master"=>$POST->master,
				"type"=>$POST->type
				);
# name only on add
if ($POST->action=="add")
$values['name'] = $POST->name;


# remove all references if delete
if ($POST->action=="delete") 									{ $PowerDNS->remove_all_records ($values['id']); }

# for creation validate default records before creating them ! => true means check only
if ($POST->action=="add" && !isset($POST->manual))            { $PowerDNS->create_default_records ($POST->as_array(), true); }

# update
if(!$PowerDNS->domain_edit($POST->action, $values)) {
    $Result->show("danger", _("Failed to")." ".$User->get_post_action()." "._("domain").'!', true);
}
else {
    $Result->show("success", _("Domain")." ".$User->get_post_action()." "._("successful").'!', false);
}

# create default records
if ($POST->action=="add" && !isset($POST->manual))			{ $PowerDNS->create_default_records ($POST->as_array()); }

