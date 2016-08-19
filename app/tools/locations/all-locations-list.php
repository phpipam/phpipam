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
    $all_locations = $Tools->fetch_all_objects("locations", "name");

    $colspan = $admin ? 5 : 4;

    // table
    print "<table class='table sorted table-striped table-top table-td-top'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Objects')."</th>";
    print " <th>"._('Description')."</th>";
    print " <th>"._('Address')."</th>";
    print " <th>"._('Coordinates')."</th>";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
				$colspan++;
			}
		}
	}
    if($admin)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

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

            // print
            print "<tr>";
            print " <td><strong><a href='".create_link("tools", "locations", $l->id)."'>$l->name</strong></a></td>";
            print " <td><span class='badge badge1 badge5'>$cnt "._('objects')."</span></td>";
            // description
            $l->description = strlen($l->description)==0 ? "/" : $l->description;
            print " <td><span class='text-muted'>$l->description</span></td>";
            // address
            $l->address = strlen($l->address)==0 ? "/" : $l->address;
            print "<td>$l->address</td>";
            // coordinates
            if(strlen($l->lat)>0 || strlen($l->long)==0) { print "<td><span class='text-muted'>$l->lat / $l->long</span></td>"; }
            else                                         { print "<td>".$Result->show("warning", _("Location not set"), false, false, true)."</td>"; }
    		//custom
    		if(sizeof($custom) > 0) {
    			foreach($custom as $field) {
    				if(!in_array($field['name'], $hidden_custom_fields)) {
    					print "<td class='hidden-xs hidden-sm hidden-md'>";

    					// create links
    					$l->{$field['name']} = $Result->create_links ($l->{$field['name']}, $field['type']);

    					//booleans
    					if($field['type']=="tinyint(1)")	{
    						if($l->{$field['name']} == "0")		{ print _("No"); }
    						elseif($l->{$field['name']} == "1")	{ print _("Yes"); }
    					}
    					//text
    					elseif($field['type']=="text") {
    						if(strlen($l->{$field['name']})>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $l->{$field['name']})."'>"; }
    						else								{ print ""; }
    					}
    					else {
    						print $l->{$field['name']};

    					}
    					print "</td>";
    				}
    			}
    		}
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