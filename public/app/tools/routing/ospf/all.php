<?php

/**
 * Script to print BGP
 ***************************/

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("routing", User::ACCESS_R, true);
?>

<?php

# check that rack support isenabled
if ($User->settings->enableRouting!="1") {
    $Result->show("danger", _("Routing module disabled."), false);
}
else {
    $Result->show("danger", _("Not implemented."), false);
}