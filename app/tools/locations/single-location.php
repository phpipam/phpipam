<?php if(!isset($hide_title)) { ?>
<h4><?php print _('Location details'); ?></h4>
<hr>
<?php } ?>
<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

// validate
if(!is_numeric($_GET['subnetId'])) {
    $Result->show("danger", _("Invalid Id"), true);
}
else {
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
        $location = $Tools->fetch_object("locations", "id", $_GET['subnetId']);

        // get custom fields
        $cfields = $Tools->fetch_custom_fields ('locations');

        if($location===false) {
             $Result->show("danger", _("Location not found"), false);
        }
        else {

            # grid
            print "<div class='row'>";

            print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-5'>";

                print "<div class='btn-group'>";
                if(!isset($hide_title))
                print "<a href='".create_link("tools", "locations")."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> ". _('Locations')."</a>";
                else
                print "<a href='".create_link("tools", "locations")."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-map'></i> ". _('Locations')."</a>";
                print "</div>";
                print "<br>";


                print "<table class='ipaddress_subnet table-condensed table-auto'>";

            	# name
            	print "<tr>";
            	print "	<th>"._('Name')."</th>";
            	print "	<td><strong>".escape_input($location->name)."</strong></td>";
            	print "</tr>";

            	# address
            	print "<tr>";
            	print "	<th>"._('Address')."</th>";
            	print "	<td>";
            	print strlen($location->address)>0 ? escape_input($location->address) : "/";
            	print "</td>";
            	print "</tr>";

            	print "<tr>";
            	print "	<th>"._('Coordinates')."</th>";
            	print "	<td>";
            	print strlen($location->lat)>0 && strlen($location->long)>0 ? "<span class='text-muted'>".escape_input($location->lat)." / ".escape_input($location->long)."</span> <a href='https://www.google.com/maps/@?api=1&map_action=map&center=".escape_input($location->lat)."%2C".escape_input($location->long). "&zoom=20&basemap=satellite' target='_blank'><i class='fa fa-gray fa-google' rel='tooltip' title='"._("Google Maps Satellite View")."'></i></a>" : "/";
            	print "</td>";
            	print "</tr>";

            	if(strlen($location->lat)==0 || strlen($location->long)==0) {
                	print "<tr>";
                	print "	<th></th>";
                	print "	<td>".$Result->show("warning", _('Location not set'), false, false, true)."</td>";
                	print "</tr>";
            	}

            	# description
            	print "<tr>";
            	print "	<th>"._('Description')."</th>";
            	print "	<td>". escape_input($location->description) ."</td>";
            	print "</tr>";

            	# print custom subnet fields if any
            	if(sizeof($cfields) > 0) {
            		// divider
            		print "<tr><td colspan='2'><hr></td></tr>";
            		// fields
            		foreach($cfields as $key=>$field) {
            			$location->{$key} = str_replace("\n", "<br>",$location->{$key});
            			// create links
            			$location->{$key} = $Tools->create_links($location->{$key});
            			print "<tr>";
            			print "	<th>".$Tools->print_custom_field_name ($key)."</th>";
            			print "	<td style='vertical-align:top;align:left;'>".$location->{$key}."</td>";
            			print "</tr>";
            		}
            	}

            	# actions
                if ($User->get_module_permissions ("locations")>=User::ACCESS_RW) {
                	print "<tr>";
                	print " <td colspan='2'><hr></td>";
                	print "</tr>";

                	print "<tr>";
                	print "	<th></th>";
                	print "	<td>";
                    $links = [];
                    $links[] = ["type"=>"header", "text"=>_("Manage")];
                    $links[] = ["type"=>"link", "text"=>_("Edit location"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/locations/edit.php' data-action='edit'  data-id='$location->id'", "icon"=>"pencil"];

                    if($User->get_module_permissions ("locations")>=User::ACCESS_RWA) {
                        $links[] = ["type"=>"link", "text"=>_("Delete location"), "href"=>"", "class"=>"open_popup", "dataparams"=>"data-script='app/admin/locations/edit.php' data-action='delete'  data-id='$location->id'", "icon"=>"times"];
                        $links[] = ["type"=>"divider"];
                    }
                    // print links
                    print $User->print_actions($User->user->compress_actions, $links, true, true);

                	print " </td>";
                	print "</tr>";
                }

            	// fetch objects
            	$objects = $Tools->fetch_location_objects ($location->id);

            	print "<tr>";
            	print "	<td colspan='2'><h4 style='margin-top:50px;'>"._('Belonging objects')."</h4><hr></td>";
            	print "</tr>";

                // none
                if($objects===false) {
                	print "<tr>";
                	print "	<td colspan='2'>".$Result->show("info", _('No objects'), false, false, true)."</td>";
                	print "</tr>";
                }
                else {
                    // reindex
                    $object_groups = array("racks"=>array(), "devices"=>array(), "subnets"=>array());
                    foreach ($objects as $o) {
                        $object_groups[$o->type][] = $o;
                    }

                    # permissions
                    if($User->get_module_permissions ("racks")==User::ACCESS_NONE)
                    unset($object_groups['racks']);

                    # permissions
                    if($User->get_module_permissions ("devices")==User::ACCESS_NONE)
                    unset($object_groups['devices']);

                    # permissions
                    if($User->get_module_permissions ("circuits")==User::ACCESS_NONE)
                    unset($object_groups['circuits']);

                    // loop
                    foreach ($object_groups as $t=>$ob) {
                    	print "<tr>";
                    	print "	<th>"._(ucwords($t))."</th>";
                        print "<td style='line-height:20px;'>";
                    	// print objects
                    	if(sizeof($ob)>0) {
                        	foreach ($ob as $o) {
                            	// fetch subnet
                            	if($o->type=="addresses") {
                                	$subnet = $Tools->fetch_object ("subnets", "id", $o->sectionId);
                            	}
                            	// link
                            	if($o->type=="devices")     { $href = create_link("tools", "devices", $o->id); }
                            	elseif($o->type=="subnets") { $href = create_link("subnets", $o->sectionId, $o->id); }
                            	elseif($o->type=="addresses") { $href = create_link("subnets", $subnet->sectionId, $subnet->id, "address-details", $o->id); }
                                elseif($o->type=="circuit") { $href = create_link("tools", "circuits", $o->id); }
                            	else                        { $href = create_link("tools", "racks", $o->id); }

                            	// description
                            	$o->description = strlen($o->description)>0 ? " <span class='text-muted'>($o->description)</span>" : "";

                            	// subnet name
                            	if ($o->type=="subnets")    $o->name = $Tools->transform_address ($o->name,"dotted")."/".$o->mask;

                            	// to ip
                            	if($o->type=="addresses")
                            	$o->name = $Tools->transform_address ($o->name, "dotted");

                                print "<a class='btn btn-xs btn-default removeLocationObject' data-object-id='$o->id' rel='tooltip' title='"._("Remove")."'><i class='fa fa-times'></i></a> ";
                                print "<a href='$href'>$o->name</a> $o->description";
                                print "<br>";
                        	}
                    	}
                    	else {
                        	print "<span class='text-muted'>/</span>";
                    	}
                    	print "<hr>";
                        print "</td>";
                    	print "</tr>";
                    }
                }


                print "</table>";

            print "</div>";

            # map
            print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-7'>";
            include("single-location-map.php");
            print "</div>";

            print "</div>";
        }
    }
}
