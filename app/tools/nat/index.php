<?php

/**
 * Script to print nats
 ***************************/

# verify that user is logged in
$User->check_user_session();

# check that nat support isenabled
if ($User->settings->enableNAT!="1") {
    $Result->show("danger", _("NAT module disabled."), false);
}
elseif ($User->check_module_permissions ("nat", User::ACCESS_R, false, false)===false) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {
    //single nat
    if(isset($GET->subnetId)) { include(dirname(__FILE__).'/nat_details.php'); }
    //all nats
    else                         { include(dirname(__FILE__).'/all_nats.php'); }
}