<?php

/**
 * Edit tag
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
$User->Crypto->csrf_cookie ("validate", "tags", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# fetch old values
if($POST->action=="delete") {
	$old_tag = $Admin->fetch_object ("ipTags", "id", $POST->id);
}
else {
	$old_tag = new StdClass ();
}

/* checks */
if($POST->action=="delete" && $old_tag->locked!="No")				{ $Result->show("danger", _("Cannot delete locked tag"), true); }
if($POST->action!="delete") {
	if(strlen($POST->type)<3)										{ $Result->show("danger", _("Invalid tag name"), true); }
	if(strlen($POST->bgcolor)<4)										{ $Result->show("danger", _("Invalid bg color"), true); }
	if(strlen($POST->fgcolor)<4)										{ $Result->show("danger", _("Invalid fg color"), true); }
}

# create array of values for modification
$values = array("id"=>$POST->id,
				"type"=>$POST->type,
				"bgcolor"=>$POST->bgcolor,
				"fgcolor"=>$POST->fgcolor,
				"showtag"=>$POST->showtag,
				"compress"=>$POST->compress,
				"updateTag"=>$POST->updateTag
				);

# execute
if(!$Admin->object_modify("ipTags", $POST->action, "id", $values)) 	{ $Result->show("danger", _("Tag")." ".$User->get_post_action()._(" error"), true); }
else 																	{ $Result->show("success", _("Tag")." ".$User->get_post_action()._(" success"), false); }

# reset if delete to online
if($POST->action=="delete") {
	$Admin->update_object_references ("ipaddresses", "state", $old_tag->id, 0);
}