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
# perm check
$User->check_module_permissions ("circuits", User::ACCESS_RWA, true, false);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "circuit_options", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# empty
if(is_blank($_POST['option']))                           { $Result->show("danger", _('Value cannot be empty'), true); }
# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
if($_POST['action']!="add" && $_POST['action']!="delete") { $Result->show("danger", _('Invalid action'), true); }

# validate type
if(!in_array($_POST['type'], array("type"))) { $Result->show("danger", _('Invalid type'), true); }

# defaults must be present
if($_POST['type']=="type") {
    if ($_POST['option']=="Default") { $Result->show("danger", _('Default value cannot be deleted'), true); }
}

# set values
$values = array (
                 "id"        => $_POST['op_id'],
                 "ctname"    => $_POST['option'],
                 "ctcolor"   => $_POST['color'],
                 "ctpattern" => $_POST['pattern']
                 );
# execute
if(!$Admin->object_modify("circuitTypes", $_POST['action'], "id", $values))  { $Result->show("danger",  _("Option $_POST[action] error"), true); }
else                                                                         { $Result->show("success", _("Option $_POST[action] success"), false); }

# updates values to default
if (is_numeric($_POST['op_id'])) {
  $Admin->update_object_references ("circuits", "type", $_POST['op_id'], 1);
}