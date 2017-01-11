<?php

/**
 * Edit tag
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->csrf_cookie ("validate", "tags", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch old values
if($_POST['action']=="delete") {
	$old_tag = $Admin->fetch_object ("ipTags", "id", $_POST['id']);
}
else {
	$old_tag = new StdClass ();
}

/* checks */
if($_POST['action']=="delete" && $old_tag->locked!="No")				{ $Result->show("danger", _("Cannot delete locked tag"), true); }
if($_POST['action']!="delete") {
	if(strlen($_POST['type'])<3)										{ $Result->show("danger", _("Invalid tag name"), true); }
	if(strlen($_POST['bgcolor'])<4)										{ $Result->show("danger", _("Invalid bg color"), true); }
	if(strlen($_POST['fgcolor'])<4)										{ $Result->show("danger", _("Invalid fg color"), true); }
}

# create array of values for modification
$values = array("id"=>@$_POST['id'],
				"type"=>$_POST['type'],
				"bgcolor"=>@$_POST['bgcolor'],
				"fgcolor"=>@$_POST['fgcolor'],
				"showtag"=>@$_POST['showtag'],
				"compress"=>@$_POST['compress'],
				"updateTag"=>@$_POST['updateTag']
				);

# execute
if(!$Admin->object_modify("ipTags", $_POST['action'], "id", $values)) 	{ $Result->show("danger",  _("Tag $_POST[action] error"), true); }
else 																	{ $Result->show("success", _("Tag $_POST[action] success"), false); }

# reset if delete to online
if($_POST['action']=="delete") {
	$Admin->update_object_references ("ipaddresses", "state", $old_tag->id, 0);
}
?>