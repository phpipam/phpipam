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

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "widget", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";


# ID must be numeric */
if($_POST['action']!="add") {
	if(!is_numeric($_POST['wid']))								{ $Result->show("danger", _("Invalid ID"), true); }
}
# Title and path must be present!
if($_POST['action']!="delete") {
if(is_blank($_POST['wtitle']) || is_blank($_POST['wfile'])) 	{ $Result->show("danger", _("Filename and title are mandatory")."!", true); }
}

# Remove .php form wfile if it is present
$_POST['wfile'] = str_replace(".php","",trim(@$_POST['wfile']));

# set update values
$values = array("wid"=>@$_POST['wid'],
				"wtitle"=>$_POST['wtitle'],
				"wdescription"=>@$_POST['wdescription'],
				"wfile"=>$_POST['wfile'],
				"wadminonly"=>$_POST['wadminonly'],
				"wactive"=>$_POST['wactive'],
				"wparams"=>$_POST['wparams'],
				"whref"=>$_POST['whref'],
				"wsize"=>$_POST['wsize']
				);
# update
if(!$Admin->object_modify("widgets", $_POST['action'], "wid", $values))	{ $Result->show("danger",  _("Widget $_POST[action] error")."!", true); }
else																	{ $Result->show("success", _("Widget $_POST[action] success")."!", true); }
?>