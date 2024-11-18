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
$User->Crypto->csrf_cookie ("validate", "circuit_options", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# empty
if(is_blank($POST->option))                           { $Result->show("danger", _('Value cannot be empty'), true); }

# validate action
if($POST->action!="add" && $POST->action!="delete") { $Result->show("danger", _('Invalid action'), true); }

# validate type
if(!in_array($POST->type, array("type"))) { $Result->show("danger", _('Invalid type'), true); }

# defaults must be present
if($POST->type=="type") {
    if ($POST->option=="Default") { $Result->show("danger", _('Default value cannot be deleted'), true); }
}

# set values
$values = array (
                 "id"        => $POST->op_id,
                 "ctname"    => $POST->option,
                 "ctcolor"   => $POST->color,
                 "ctpattern" => $POST->pattern
                 );
# execute
if(!$Admin->object_modify("circuitTypes", $POST->action, "id", $values))  { $Result->show("danger",  _("Option " . $User->get_post_action() . " error"), true); }
else                                                                         { $Result->show("success", _("Option " . $User->get_post_action() . " success"), false); }

# updates values to default
if (is_numeric($POST->op_id)) {
  $Admin->update_object_references ("circuits", "type", $POST->op_id, 1);
}
