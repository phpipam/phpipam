<?php

/**
 * Script to remove temp access
 *************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Subnets 	= new Subnets ($Database);
$Admin	 	= new Admin ($Database, false);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

print '<div class="pHeader">'._("Remove temporary share").'</div>';
print '<div class="pContent">';

/* checks */
if($User->settings->tempShare!=1)									{ $Result->show("danger", _("Temporary sharing disabled"), true); }
if(strlen($_POST['code'])!=32) 										{ $Result->show("danger", _("Invalid code"), true); }

# remove object
$old_access = pf_json_decode($User->settings->tempAccess, true);
//check that it exists
if(!isset($old_access[$_POST['code']]))								{ $Result->show("danger", _("Code does not exist"), true); }
//remove
unset($old_access[$_POST['code']]);

//reset
$new_access = !is_array($old_access) ? "" : json_encode(array_filter($old_access));

# execute
if(!$Admin->object_modify("settings", "edit", "id", array("id"=>1,"tempAccess"=>$new_access))) 	{ $Result->show("danger",  _("Temporary share delete error"), true); }
else 																							{ $Result->show("success", _("Temporary share deleted"), false); }

?>
</div>

<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
	</div>
</div>