<?php

/**
 * Script to display api edit result
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
$User->Crypto->csrf_cookie ("validate", "apiedit", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

/* checks */
$error = array();

if($POST->action!="delete") {
	# code must be exactly 32 chars long and alphanumeric if app_security = crypt
	if($POST->app_security=="crypt") {
	if(strlen($POST->app_code)!=32 || !preg_match("#^[a-zA-Z0-9-_=]+$#", $POST->app_code))								{ $error[] = "Invalid application code"; }
	}
	# name must be more than 2 and alphanumeric
	if(strlen($POST->app_id)<3 || strlen($POST->app_id)>12 || !preg_match("#^[a-zA-Z0-9-_=]+$#",$POST->app_id))			{ $error[] = "Invalid application id"; }
	# permissions must be 0,1,2
	if($POST->app_security!="user") {
	if(!($POST->app_permissions==0 || $POST->app_permissions==1 || $POST->app_permissions ==2 || $POST->app_permissions ==3 ))	{ $error[] = "Invalid permissions"; }
	}
	# lock check
	if($POST->app_lock=="1") {
    	if(!is_numeric($POST->app_lock_wait))                                                            { $error[] = "Invalid wait value"; }
    	elseif ($POST->app_lock_wait<1)                                                                  { $error[] = "Invalid wait value"; }
	}
	# api_allow_unsafe check
	if($POST->app_security=="none" && Config::ValueOf('api_allow_unsafe')!==true)											{ $error[] = "API server requires SSL. Please set \$api_allow_unsafe in config.php to override"; }
}

# default lock_wait
if($POST->app_lock_wait=="") { $POST->app_lock_wait=0; }

# die if errors
if(sizeof($error) > 0) {
	$Result->show("danger", $error, true);
}
else {
	# create array of values for modification
	$values = array(
					"id"                     =>$POST->id,
					"app_id"                 =>$POST->app_id,
					"app_code"               =>$POST->app_code,
					"app_permissions"        =>$POST->app_permissions,
					"app_security"           =>$POST->app_security,
					"app_lock"               =>$POST->app_lock,
					"app_lock_wait"          =>$POST->app_lock_wait,
					"app_nest_custom_fields" =>$POST->app_nest_custom_fields,
					"app_show_links"         =>$POST->app_show_links,
					"app_comment"            =>$POST->app_comment
					);

	# execute
	if(!$Admin->object_modify("api", $POST->action, "id", $values)) 	{ $Result->show("danger",  _("API"). " ".$POST->action ." "._("error"), true); }
	else 																{ $Result->show("success", _("API"). " ".$POST->action ." "._("success"), true); }
}
