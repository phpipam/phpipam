<?php

/**
 *	Site settings
 **************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

// validations
if(strlen($_POST['ttl'])==0)	{ $_POST['ttl'] = $PowerDNS->defaults->ttl; }

// formulate json
$values = new StdClass ();

// get old settings for defaults
$old_values = json_decode($User->settings->powerDNS);

$values->host 		= $old_values->host;
$values->name 		= $old_values->name;
$values->username 	= $old_values->username;
$values->password 	= $old_values->password;
$values->port 		= $old_values->port;
$values->autoserial = @$old_values->autoserial;

// defaults
$values->ns 		= $_POST['ns'];
$values->hostmaster = $_POST['hostmaster'];
$values->refresh 	= $_POST['refresh'];
$values->retry 		= $_POST['retry'];
$values->expire 	= $_POST['expire'];
$values->nxdomain_ttl = $_POST['nxdomain_ttl'];
$values->ttl 		= $_POST['ttl'];

# set update values
$values = array("id"=>1,
				"powerDNS"=>json_encode($values),
				);
if(!$Admin->object_modify("settings", "edit", "id", $values))	{ $Result->show("danger",  _("Cannot update settings"), true); }
else															{ $Result->show("success", _("Settings updated successfully"), true); }
?>