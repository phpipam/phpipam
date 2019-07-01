<?php
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

# perm check popup
$User->check_module_permissions ("routing", 2, true, true);

# values
$values = [
			"type"      => "bgp",
			"direction" => $_POST['direction'],
			"object_id" => $_POST['bgp_id'],
			"subnet_id" => $_POST['subnet_id']
			];

# submit
if(!$Admin->object_modify ("routing_subnets", "add", "id", $values))  { $Result->show("danger",  _("Mapping $_POST[action] failed"), false); }
else																  { $Result->show("success", _("Mapping $_POST[action] successful"), false); }
