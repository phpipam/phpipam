<h4><?php print _('Location details'); ?></h4>
<hr>
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
    # check that location support isenabled
    if ($User->settings->enableLocations!="1") {
        $Result->show("danger", _("Locations module disabled."), false);
    }
    else {
        # fetch all locations
        $location = $Tools->fetch_object("locations", "id", $_GET['subnetId']);

        if($location===false) {
             $Result->show("danger", _("Location not found"), false);
        }
        else {

            # grid
            print "<div class='row'>";

            print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-5'>";

                print "<div class='btn-group'>";
                print "<a href='".create_link("tools", "locations")."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> ". _('Locations')."</a>";
                print "</div>";
                print "<br>";


                print "<table class='ipaddress_subnet table-condensed table-auto'>";

            	# name
            	print "<tr>";
            	print "	<th>"._('Name')."</th>";
            	print "	<td><strong>$location->name</strong></td>";
            	print "</tr>";

            	# lat
            	print "<tr>";
            	print "	<th>"._('Latitude')."</th>";
            	print "	<td>$location->lat</td>";
            	print "</tr>";

            	# long
            	print "<tr>";
            	print "	<th>"._('Longitude')."</th>";
            	print "	<td>$location->long</td>";
            	print "</tr>";

            	# description
            	print "<tr>";
            	print "	<th>"._('Description')."</th>";
            	print "	<td>$location->description</td>";
            	print "</tr>";


            	# actions
            	print "<tr>";
            	print " <td colspan='2'><hr></td>";
            	print "</tr>";

            	print "<tr>";
            	print "	<th></th>";
            	print "	<td>";
                print "	<div class='btn-group'>";
        		print "		<a href='' class='btn btn-xs btn-default editLocation' data-action='edit'   data-id='$location->id'><i class='fa fa-pencil'></i></a>";
        		print "		<a href='' class='btn btn-xs btn-default editLocation' data-action='delete' data-id='$location->id'><i class='fa fa-times'></i></a>";
        		print "	</div>";
            	print " </td>";
            	print "</tr>";

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

                    // loop
                    foreach ($object_groups as $t=>$ob) {
                    	print "<tr>";
                    	print "	<th>"._(ucwords($t))."</th>";
                        print "<td style='line-height:20px;'>";
                    	// print objects
                    	if(sizeof($ob)>0) {
                        	foreach ($ob as $o) {
                            	// link
                            	if($o->type=="devices")     { $href = create_link("tools", "devices", "hosts", $o->id); }
                            	elseif($o->type=="subnets") { $href = create_link("subnets", $o->sectionId, $o->id); }
                            	else                        { $href = create_link("tools", "racks", $o->id); }

                            	// description
                            	$o->description = strlen($o->description)>0 ? " <span class='text-muted'>($o->description)</span>" : "";

                            	// subnet name
                            	if ($o->type=="subnets")    $o->name = $Tools->transform_address ($o->name,"dotted").".".$o->mask;

                                print "<a class='btn btn-xs btn-danger removeLocationObject' data-object-id='$o->id' rel='tooltip' title='"._("Remove")."'><i class='fa fa-times'></i></a> ";
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
?>