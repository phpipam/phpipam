<?php if(@$title!==false) { ?>
<h4><?php print _('Locations Map'); ?></h4>
<hr>
<?php } ?>

<ul class='nav nav-tabs' style='margin-top:0px;margin-bottom:20px;'>
    <li role='presentation' <?php if(!isset($_GET['subnetId'])||is_numeric($_GET['subnetId'])) print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "locations"); ?>'><?php print _("Locations list"); ?></a>
    </li>
    <li role='presentation' <?php if(@$_GET['subnetId']=="map") print " class='active'"; ?>>
        <a href='<?php print create_link($_GET['page'], "locations", "map"); ?>'><?php print _("Locations map"); ?></a>
    </li>
</ul>

<?php if(isset($admin) && ($admin && $User->settings->enableLocations=="1")) { ?>
<?php
if($User->get_module_permissions ("locations")>=User::ACCESS_RW) {
include('menu.php');
}
?>
<br>
<?php } ?>
<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("locations", User::ACCESS_R, true, false);

# perm check
if ($User->get_module_permissions ("locations")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# check that location support isenabled
elseif ($User->settings->enableLocations!="1") {
    $Result->show("danger", _("Locations module disabled."), false);
}
else {
    # fetch all locations
    $all_locations = $Tools->fetch_all_objects("locations", "name");
    $all_locations = is_array($all_locations) ? $all_locations : [];

    $OSM = new OpenStreetMap($Database);
    foreach ($all_locations as $l) {
        $OSM->add_location($l);
    }
    $OSM->map();
}
