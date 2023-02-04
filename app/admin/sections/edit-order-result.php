<?php

/**
 * Function to add / edit / delete section
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# check maintaneance mode
$User->check_maintaneance_mode ();

# create array of ordering
$otmp = pf_explode(";", $_POST['position']);
foreach($otmp as $ot) {
	$ptmp = pf_explode(":", $ot);
	$order[$ptmp[0]] = $ptmp[1];
}

#update
if(!$Sections->modify_section ("reorder", $order))	{ $Result->show("danger",  _("Section reordering failed"), true); }
else												{ $Result->show("success", _("Section reordering successful"), true); }
?>