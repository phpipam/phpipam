<?php

/**
 * Script to print racks
 ***************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools 		= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();


# verify that user is logged in
$User->check_user_session();

# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_R, true, true);

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), true, true, false, true);
}
else {
    # validate integer
    if(!is_numeric($POST->rackid))      { $error = _("Invalid rack Id"); }
    # init racks object
    $Racks = new phpipam_rack ($Database);
    # fetch all racks
    $rack = $Racks->fetch_rack_details ($POST->rackid);

    // rack check
    if($rack===false)                       { $error =_("Invalid rack Id"); }
}
?>


<div class="pHeader"><?php print _("Rack details"); ?></div>

<div class="pContent text-center">
    <?php
    if(isset($error)) {
        $Result->show("danger", _($error), true, true, false, true);
    }
    else {
    ?>
        <img src="<?php print $Tools->create_rack_link ($rack->id, $POST->deviceid); ?>" style='width:180px;'>
        <?php if($rack->hasBack!="0") { ?>
        <img src="<?php print $Tools->create_rack_link ($rack->id, $POST->deviceid, true); ?>" style='width:180px;'>
        <?php } ?>
    <?php } ?>
</div>

<div class="pFooter"><button class="btn btn-sm btn-default hidePopup2">Close</button></div>