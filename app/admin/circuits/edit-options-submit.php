<?php

/**
 *	Edit device details
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# admin check
$User->is_admin(true);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "circuit_options", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# empty
if(strlen($_POST['option'])==0)                           { $Result->show("danger", _('Value cannot be empty'), true); }

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], false);

# validate type
if(!in_array($_POST['type'], array("type"))) 			{ $Result->show("danger", _('Invalid type'), true); }

# defaults must be present
if($_POST['type']=="type") {
	if ($_POST['option']=="Default") 					{ $Result->show("danger", _('Default value cannot be deleted'), true); }
}

# get old field data
$circuit_options  = $Database->getFieldInfo ("circuits", $_POST['type']);
# parse and remove type
$circuit_option_values = explode("'", str_replace(array("(", ")", ","), "", $circuit_options->Type));
unset($circuit_option_values[0]);
// reindex and remove empty
$circuit_option_values = array_values(array_filter($circuit_option_values));
$circuit_options = (array) $circuit_options;

# execute
if($_POST['action']=="add") {
    if ($_POST['type']=="type") { $circuit_option_values[] = $_POST['option']; }
}
// remove option
elseif ($_POST['action']=="delete") {
    if ($_POST['type']=="type") { unset($circuit_option_values[array_search($_POST['option'], $circuit_option_values)]); }
}
else {
    $Result->show("danger", _('Invalid action'), true);
}

# update exisiting vlaues to default
$query = "update `circuits` set `$_POST[type]` = 'Default' where `$_POST[type]` = ?";
# execute
try { $Database->runQuery($query, array($_POST['option'])); }
catch (Exception $e) {
	$Result->show("danger", _("Error: ").$e->getMessage());
}

# set query
$query = "ALTER TABLE `circuits` CHANGE `$_POST[type]` `$_POST[type]` ENUM('".implode("','", $circuit_option_values)."')  CHARACTER SET utf8  NULL  DEFAULT NULL";

// print_r($query);
// die('alert-danger');

# execute
try { $Database->runQuery($query); }
catch (Exception $e) {
	$Result->show("danger", _("Error: ").$e->getMessage());
}

# ok
$Result->show("success", _("Options updated"));
