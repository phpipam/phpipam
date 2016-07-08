<h4><?php print _('List of all locations'); ?></h4>
<hr>

<div class="btn-group">
    <?php if($_GET['page']=="administration") { ?>
	<a href="" class='btn btn-sm btn-default editLocation' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add location'); ?></a>
	<?php } else { ?>
	<a href="<?php print create_link("administration", "locations") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
	<?php } ?>
	<a href="<?php print create_link("tools", "locations", "map") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-map'></i> <?php print _('Map'); ?></a>
</div>
<br>

<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# check that location support isenabled
if ($User->settings->enableLocations!="1") {
    $Result->show("danger", _("Locations module disabled."), false);
}
else {
    # fetch all locations
    $all_locations = $Tools->fetch_all_objects("locations", "id");

    // table
    print "<table class='table sorted table-striped table-top table-td-top'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Objects')."</th>";
    print " <th>"._('Description')."</th>";
    print " <th>"._('Latitude')."</th>";
    print " <th>"._('Longitude')."</th>";
    if($admin)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    $colspan = $admin ? 5 : 4;

    # if none than print
    if($all_locations===false) {
        print "<tr>";
        print " <td colspan='$colspan'>".$Result->show("info","No Locations configured", false, false, true)."</td>";
        print "</tr>";
    }
    else {
        foreach ($all_locations as $l) {

            // count
            $cnt = $Tools->fetch_location_objects ($l->id, true);
            $cnt = $cnt[0]->cnt;

            // fix
            $l->lat = strlen($l->lat)>0 ? $l->lat : "<span class='text-muted'>/</span>";
            $l->long = strlen($l->long)>0 ? $l->long : "<span class='text-muted'>/</span>";

            // print
            print "<tr>";
            print " <td><strong><a href='".create_link("tools", "locations", $l->id)."'>$l->name</strong></td>";
            print " <td><span class='badge badge1 badge5'>$cnt "._('objects')."</span></td>";
            print " <td><span class='text-muted'>$l->description</span></td>";
            print " <td>$l->long</td>";
            print " <td>$l->lat</td>";
            // actions
            if($admin) {
    		print "	<td class='actions'>";
    		print "	<div class='btn-group'>";
    		print "		<a href='' class='btn btn-xs btn-default editLocation' data-action='edit'   data-id='$l->id'><i class='fa fa-pencil'></i></a>";
    		print "		<a href='".create_link("tools", "locations", $l->id)."' class='btn btn-xs btn-default' ><i class='fa fa-eye'></i></a>";
    		print "		<a href='' class='btn btn-xs btn-default editLocation' data-action='delete' data-id='$l->id'><i class='fa fa-times'></i></a>";
    		print "	</div>";
    		print " </td>";
    		}

            print "</tr>";
        }
    }
    print "</tbody>";
    print "</table>";
}
?>