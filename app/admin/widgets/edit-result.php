<?php

/**
 * Script to display widget edit
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
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "widget", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# ID must be numeric */
if($POST->action!="add") {
	if(!is_numeric($POST->wid))								{ $Result->show("danger", _("Invalid ID"), true); }
}
# Title and path must be present!
if($POST->action!="delete") {
if(is_blank($POST->wtitle) || is_blank($POST->wfile)) 	{ $Result->show("danger", _("Filename and title are mandatory")."!", true); }
}

# Remove .php form wfile if it is present
$POST->wfile = str_replace(".php","",trim($POST->wfile));

# set update values
$values = array("wid"=>$POST->wid,
				"wtitle"=>$POST->wtitle,
				"wdescription"=>$POST->wdescription,
				"wfile"=>$POST->wfile,
				"wadminonly"=>$POST->wadminonly,
				"wactive"=>$POST->wactive,
				"wparams"=>$POST->wparams,
				"whref"=>$POST->whref,
				"wsize"=>$POST->wsize
				);
# update
if(!$Admin->object_modify("widgets", $POST->action, "wid", $values))	{ $Result->show("danger",  _("Widget ".$User->get_post_action()." error")."!", true); }
else																	{ $Result->show("success", _("Widget ".$User->get_post_action()." success")."!", true); }
