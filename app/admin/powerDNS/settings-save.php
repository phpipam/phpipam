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
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "pdns_settings", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

// validations
if(is_blank($_POST['name']))			{ $Result->show("danger", "Invalid database name", true); }
if(is_blank($_POST['port']))			{ $_POST['port'] = 3306; }
elseif (!is_numeric($_POST['port']))	{ $Result->show("danger", "Invalid port number", true); }

// formulate json
$values = new StdClass ();

$values->host 		= $_POST['host'];
$values->name 		= $_POST['name'];
$values->username 	= $_POST['username'];
$values->password 	= $_POST['password'];
$values->port 		= $_POST['port'];
$values->autoserial = isset($_POST['autoserial']) ? "Yes" : "No";

// get old settings for defaults
$old_values = pf_json_decode($User->settings->powerDNS);

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