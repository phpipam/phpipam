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
$User->Crypto->csrf_cookie ("validate", "pdns_defaults", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

// validations
if(is_blank($POST->ttl))	{ $POST->ttl = $PowerDNS->defaults->ttl; }

// formulate json
$values = new StdClass ();

// get old settings for defaults
$old_values = db_json_decode($User->settings->powerDNS);

$values->host 		= $old_values->host;
$values->name 		= $old_values->name;
$values->username 	= $old_values->username;
$values->password 	= $old_values->password;
$values->port 		= $old_values->port;
$values->autoserial = @$old_values->autoserial;

// defaults
$values->ns 		= $POST->ns;
$values->hostmaster = $POST->hostmaster;
$values->def_ptr_domain = $POST->def_ptr_domain;
$values->refresh 	= $POST->refresh;
$values->retry 		= $POST->retry;
$values->expire 	= $POST->expire;
$values->nxdomain_ttl = $POST->nxdomain_ttl;
$values->ttl 		= $POST->ttl;

# set update values
$values = array("id"=>1,
				"powerDNS"=>json_encode($values),
				);
if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), true); }
else															{ $Result->show("success", _("Settings updated successfully"), true); }