<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_R, true);

# set admin
$admin = $User->is_admin(false);

?>
<h4><?php print _('RACK list'); ?></h4>
<hr>

<?php

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), false);
}
# print racks
else {
    # print
    print "<ul class='nav nav-tabs' style='margin-bottom:20px;'>";
    $class = !isset($_GET['subnetId']) ? "active" : "";
    print " <li role='presentation' class='$class'><a href='".create_link("tools", "racks")."'>"._('Rack list')."</a></li>";
    $class = isset($_GET['subnetId']) && $_GET['subnetId']=="map" ? "active" : "";
    print " <li role='presentation' class='$class'><a href='".create_link("tools", "racks", "map")."'>"._("Rack scheme")."</a></li>";
    print "</ul>";

    # buttons
    print '<div class="btn-group">';
    if($User->get_module_permissions ("racks")>=User::ACCESS_RWA)
    print "    <a href=''' class='btn btn-sm btn-default  editRack' data-action='add'   data-rackid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add rack')."</a>";
    print '</div>';
    print '<br>';

    # include subpage
    if(!isset($_GET['subnetId']))   { include(dirname(__FILE__)."/print-racks-list.php"); }
    else                            { include("print-racks-map.php"); }
}