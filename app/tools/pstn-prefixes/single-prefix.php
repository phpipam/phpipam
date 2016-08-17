<h4><?php print _('PSTN prefix details'); ?></h4>
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
    if ($User->settings->enablePSTN!="1") {
        $Result->show("danger", _("PSTN prefixes module disabled."), false);
    }
    else {
        # fetch all locations
        $prefix = $Tools->fetch_object("pstnPrefixes", "id", $_GET['subnetId']);

        // get custom fields
        $cfields = $Tools->fetch_custom_fields ('pstnPrefixes');

        if($prefix===false) {
             $Result->show("danger", _("Prefix not found"), false);
        }
        else {

            # set permission
            $permission = $Tools->check_prefix_permission ($User->user);

            # raw prefix number
            $prefix->prefix_raw = $Tools->prefix_normalize ($prefix->prefix);
            $prefix->prefix_raw_start = $Tools->prefix_normalize ($prefix->prefix.$prefix->start);
            $prefix->prefix_raw_stop  = $Tools->prefix_normalize ($prefix->prefix.$prefix->stop);

            # get objects
            $numbers = $Tools->fetch_multiple_objects ("pstnNumbers", "prefix", $prefix->id, "number", true);
            # get count
            $details = $Tools->calculate_prefix_usege( $prefix, $numbers);

            print "<div class='btn-group'>";
            if($prefix->master>0 )
            print "<a href='".create_link($_GET['page'], "pstn-prefixes", $prefix->master)."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> ". _('Master prefix')."</a>";
            else
            print "<a href='".create_link($_GET['page'], "pstn-prefixes")."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> ". _('All prefixes')."</a>";
            print "</div>";
            print "<br>";

            print "<div class='row'>";
            print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-8'>";

            print "<h4>"._('Prefix details')."</h4><hr>";
            print "<table class='ipaddress_subnet table-condensed table-auto'>";

        	# name
        	print "<tr>";
        	print "	<th>"._('Name')."</th>";
        	print "	<td><strong>$prefix->name</strong></td>";
        	print "</tr>";

        	# prefix
        	print "<tr>";
        	print "	<th>"._('Prefix')."</th>";
        	print "	<td>$prefix->prefix</td>";
        	print "</tr>";

            # hierarchy
        	print "<tr>";
        	print "<th>"._('Hierarchy')."</th>";
        	print "<td>";
        	print $Subnets->print_breadcrumbs($Sections, $Subnets, $_GET);
        	print "</td>";
        	print "</tr>";

        	# description
        	print "<tr>";
        	print "	<th>"._('Description')."</th>";
        	print "	<td>$prefix->description</td>";
        	print "</tr>";

        	# device
        	print "<tr>";
        	print "	<th>"._('Device')."</th>";
        	$device = $Tools->fetch_object ("devices", "id", $prefix->deviceId);
        	if($device===false) {
            	print "<td>/</td>";
        	}
        	else {
        	print "	<td><a href='".create_link("tools","devices",$device->id)."'>$device->hostname</a></td>";
            }
        	print "</tr>";

        	# divider
        	print "<tr>";
        	print "	<td colspan='2'><hr></td>";
        	print "</tr>";

        	print "<tr>";
        	print "	<th>"._('Range').":</th>";
        	print "	<td></td>";
        	print "</tr>";

        	print "<tr>";
        	print "	<th style='font-weight:normal;'>"._('Start')."</th>";
        	print "	<td>$prefix->prefix$prefix->start</td>";
        	print "</tr>";

        	print "<tr>";
        	print "	<th style='font-weight:normal;'>"._('Stop')."</th>";
        	print "	<td>$prefix->prefix$prefix->stop</td>";
        	print "</tr>";

        	# divider
        	print "<tr>";
        	print "	<td colspan='2'><hr></td>";
        	print "</tr>";

        	print "<tr>";
        	print "	<th>"._('Utilization')."</th>";
        	print "	<td>".($details['maxhosts'] - $details['freehosts'])." / ".$details['maxhosts']." "._('Used')." (".$details['freehosts_percent']."% "._('Free').")</td>";
        	print "</tr>";

        	print "<tr>";
        	print "	<td colspan='2'><hr></td>";
        	print "</tr>";

        	# print custom subnet fields if any
        	if(sizeof($cfields) > 0) {
        		// divider
        		print "<tr><td colspan='2'><hr></td></tr>";
        		// fields
        		foreach($cfields as $key=>$field) {
        			$prefix->{$key} = str_replace("\n", "<br>",$prefix->{$key});
        			// create links
        			$prefix->{$key} = $Result->create_links($prefix->{$key});
        			print "<tr>";
        			print "	<th>$key</th>";
        			print "	<td style='vertical-align:top;align:left;'>".$prefix->{$key}."</td>";
        			print "</tr>";
        		}
        	}

        	# actions
        	print "<tr>";
        	print "	<th></th>";
        	print "	<td>";
        	print " <div class='btn-group'>";
        	if($permission == 3) {
    		    print "<a class='btn btn-xs btn-success editPSTNnumber' data-action='add' data-id='$prefix->id' data-container='body' rel='tooltip' title='"._('Add address to prefix')."'><i class='fa fa-plus'></i></a>";
        		print "<a class='btn btn-xs btn-default editPSTN' data-action='edit' data-id='$prefix->id' data-container='body' rel='tooltip' title='"._('Edit prefix properties')."'><i class='fa fa-pencil'></i></a>";
        		print "<a class='btn btn-xs btn-default editPSTN' data-action='add' data-id='$prefix->id' data-container='body' rel='tooltip' title='"._('Create new prefix')."'><i class='fa fa-plus-circle'></i></a> ";
        		print "<a class='btn btn-xs btn-danger editPSTN' data-action='delete' data-id='$prefix->id' data-container='body' rel='tooltip' title='"._('Delete prefix')."'><i class='fa fa-remove'></i></a>";
            }
        	elseif($permission == 2) {
     		    print "<a class='btn btn-xs btn-success editPSTNnumber' data-action='add' data-id='$prefix->id' data-container='body' rel='tooltip' title='"._('Add address to prefix')."'><i class='fa fa-plus'></i></a>";
        		print "<a class='btn btn-xs btn-default disabled' rel='tooltip' title='"._('Edit prefix properties')."'><i class='fa fa-pencil'></i></a>";
        		print "<a class='btn btn-xs btn-default disabled' rel='tooltip' title='"._('Create new prefix')."'><i class='fa fa-plus-circle'></i></a> ";
        		print "<a class='btn btn-xs btn-danger disabled' rel='tooltip' title='"._('Delete prefix')."'><i class='fa fa-remove'></i></a>";

        	}
        	else {
        		print "<button class='btn btn-xs btn-default btn-danger' data-container='body' rel='tooltip' title='"._('You do not have permissions to edit prefix')."'><i class='fa fa-lock'></i></button> ";
     		    print "<a class='btn btn-xs btn-success disabled' rel='tooltip' title='"._('Add address to prefix')."'><i class='fa fa-plus'></i></a>";
        		print "<a class='btn btn-xs btn-default disabled' rel='tooltip' title='"._('Edit prefix properties')."'><i class='fa fa-pencil'></i></a>";
        		print "<a class='btn btn-xs btn-default disabled' rel='tooltip' title='"._('Create new prefix')."'><i class='fa fa-plus-circle'></i></a> ";
        		print "<a class='btn btn-xs btn-danger disabled' rel='tooltip' title='"._('Delete prefix')."'><i class='fa fa-remove'></i></a>";
            }
            print "	</div>";

        	print " </td>";
        	print "</tr>";

            print "</table>";
            print "</div>";


            // graph
            print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'>";
            print "<h4>"._('Utilization')."</h4><hr>";
            include("single-prefix-graph.php");
            print "</div>";


            # addresses
            print "<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12' style='margin-top:40px;'>";
            print "<h4>"._('Belonging Numbers')."</h4><hr>";
            include("single-prefix-numbers.php");
            print "</div>";
        }
    }
}
?>