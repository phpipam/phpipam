<h4><?php print _('List of all locations'); ?></h4>
<hr>

<?php
if($User->get_module_permissions ("locations")>=User::ACCESS_RW) {
include('menu.php');
}
?>


<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

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

    $colspan = 4;

    // table
    print "<table class='table sorted table-striped table-top table-td-top' data-cookie-id-table='all_locations'>";
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
				print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
				$colspan++;
			}
		}
	}
    if($User->get_module_permissions ("locations")>=User::ACCESS_RW)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    # if none than print
    if($all_locations===false) {
        print "<tr>";
        print " <td colspan='$colspan'>".$Result->show("info",_("No Locations configured"), false, false, true)."</td>";
        print "</tr>";
    }
    else {
        foreach ($all_locations as $l) {

            // count
            $cnt = $Tools->fetch_location_objects ($l->id, true);
            $cnt = $cnt[0]->cnt;

            // print
            print "<tr>";
            print " <td><a class='btn btn-xs btn-default' href='".create_link("tools", "locations", $l->id)."'><i class='fa fa-map prefix'></i> $l->name</a></td>";
            print " <td><span class='badge badge1 badge5'>$cnt "._('objects')."</span></td>";
            // description
            $l->description = is_blank($l->description) ? "/" : $l->description;
            print " <td><span class='text-muted'>$l->description</span></td>";
            // address
            $l->address = is_blank($l->address) ? "/" : $l->address;
            print "<td>$l->address</td>";
            // coordinates
            if(!is_blank($l->lat) || is_blank($l->long)) { print "<td><span class='text-muted'>$l->lat / $l->long</span></td>"; }
            else                                         { print "<td>".$Result->show("warning", _("Location not set"), false, false, true)."</td>"; }
    		//custom
    		if(sizeof($custom) > 0) {
    			foreach($custom as $field) {
    				if(!in_array($field['name'], $hidden_custom_fields)) {
    					print "<td class='hidden-xs hidden-sm hidden-md'>";
                        $Tools->print_custom_field ($field['type'], $l->{$field['name']});
    					print "</td>";
    				}
    			}
    		}
            // actions
            if($User->get_module_permissions ("locations")>=User::ACCESS_RW) {
            print "<td class='actions'>";
            $links = [];
            $links[] = ["type"=>"header", "text"=>_("Show")];
            $links[] = ["type"=>"link", "text"=>_("Show location"), "href"=>create_link($GET->page, "locations", $l->id), "icon"=>"eye", "visible"=>"dropdown"];
            $links[] = ["type"=>"divider"];

            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit location"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/locations/edit.php' data-action='edit'  data-id='$l->id'", "icon"=>"pencil"];

            if($User->get_module_permissions ("locations")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"link", "text"=>_("Delete location"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/locations/edit.php' data-action='delete'  data-id='$l->id'", "icon"=>"times"];
                $links[] = ["type"=>"divider"];
            }
            // print links
            print $User->print_actions($User->user->compress_actions, $links);
            print "</td>";

    		}

            print "</tr>";
        }
    }
    print "</tbody>";
    print "</table>";
}
