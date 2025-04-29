<?php

/**
 *	Site settings
 **************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check if site is demo
$User->is_demo();
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "pdns_settings", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

// validations
if(is_blank($POST->name))			{ $Result->show("danger", "Invalid database name", true); }
if(is_blank($POST->port))			{ $POST->port = 3306; }
elseif (!is_numeric($POST->port))	{ $Result->show("danger", "Invalid port number", true); }

// formulate json
$values = new StdClass ();

$values->host 		= trim(str_replace(",", ";", $POST->host));
$values->name 		= $POST->name;
$values->username 	= $POST->username;
$values->password 	= $POST->password;
$values->port 		= $POST->port;
$values->autoserial = isset($POST->autoserial) ? "Yes" : "No";

// get old settings for defaults
$old_values = db_json_decode($User->settings->powerDNS);
if (!is_object($old_values)) {
    $old_values = new Params();
}

$values->ns			= $old_values->ns;
$values->hostmaster	= $old_values->hostmaster;
$values->def_ptr_domain	= $old_values->def_ptr_domain;
$values->refresh 	= $old_values->refresh;
$values->retry 		= $old_values->retry;
$values->expire 	= $old_values->expire;
$values->nxdomain_ttl = $old_values->nxdomain_ttl;
$values->ttl 		= $old_values->ttl;

# set update values
$values_new = array("id"=>1,
				"powerDNS"=>json_encode($values),
				);
if(!$Admin->object_modify("settings", "edit", "id", $values_new))	{ $Result->show("danger",  _("Cannot update settings"), false); }
else															    { $Result->show("success", _("Settings updated successfully"), false); }

# autoserial change - set default SOA for all records !
if ($values->autoserial!==@$old_values->autoserial) {
    // start class
    $PowerDNS 	= new PowerDNS ($Database);
    // check connection
    if($PowerDNS->db_check()!==false) {
        // update all serials
        $PowerDNS->update_all_soa_serials ($values->autoserial);
    }
}