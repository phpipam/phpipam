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
$User->check_module_permissions ("routing", User::ACCESS_RW, true, true);

# values
$values = [
			"type"      => "bgp",
			"direction" => $POST->direction,
			"object_id" => $POST->bgp_id,
			"subnet_id" => $POST->subnet_id
			];

# submit
if(!$Admin->object_modify ("routing_subnets", "add", "id", $values)) {
    $Result->show("danger", _("Mapping")." ".$User->get_post_action()." "._("failed"), false);
}
else {
    $Result->show("success", _("Mapping")." ".$User->get_post_action()." "._("successful"), false);
}
