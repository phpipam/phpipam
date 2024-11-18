<?php

/**
 * Script to display language edit
 *************************************/


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
$User->Crypto->csrf_cookie ("validate", "languages", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# verify that description is present if action != delete
if($POST->action != "delete" && strlen($POST->l_code) < 2)		{ $Result->show("danger", _('Code must be at least 2 characters long'), true); }
if($POST->action != "delete" && strlen($POST->l_name) < 2)		{ $Result->show("danger", _('Name must be at least 2 characters long'), true); }

# create update array
$values = array("l_id"=>$POST->l_id,
				"l_code"=>$POST->l_code,
				"l_name"=>$POST->l_name
				);

# update
if(!$Admin->object_modify("lang", $POST->action, "l_id", $values))	{ $Result->show("danger",  _("Language " . $User->get_post_action() . " error"), true); }
else																	{ $Result->show("success", _("Language " . $User->get_post_action() . " success"), true); }
