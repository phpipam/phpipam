<?php
/**
 * Script to print locations
 **/

# verify that user is logged in
$User->check_user_session();
?>

<h4><?php print _('PSTN prefix details'); ?></h4>
<hr>

<?php

# perm check
if ($User->get_module_permissions ("pstn")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
// validate
elseif(!is_numeric($GET->subnetId)) {
    $Result->show("danger", _("Invalid Id"), true);
}
else {
    # check that location support isenabled
    if ($User->settings->enablePSTN!="1") {
        $Result->show("danger", _("PSTN prefixes module disabled."), false);
    }
    else {
        # fetch all prefixes
        $prefix = $Tools->fetch_object("pstnPrefixes", "id", $GET->subnetId);

        // get custom fields
        $cfields = $Tools->fetch_custom_fields ('pstnPrefixes');

        if($prefix===false) {
             $Result->show("danger", _("Prefix not found"), false);
        }
        else {

            # raw prefix number
            $prefix->prefix_raw = $Tools->prefix_normalize ($prefix->prefix);
            $prefix->prefix_raw_start = $Tools->prefix_normalize ($prefix->prefix.$prefix->start);
            $prefix->prefix_raw_stop  = $Tools->prefix_normalize ($prefix->prefix.$prefix->stop);


            if ($isMaster) {
                # get objects + slaves and parse ids
                $subprefixes = $Tools->fetch_all_prefixes ($prefix->id);
                $subprefixes_cnt = $Tools->fetch_all_prefixes ($prefix->id, true);

                if($subprefixes_cnt !== false) {
                    $numbers = array();
                    foreach ($subprefixes_cnt as $sp) {
                        $subprefix_numbers = $Tools->fetch_multiple_objects ("pstnNumbers", "prefix", $sp->id, "number", true);
                        if ($subprefix_numbers!==false) {
                            $numbers = array_merge($numbers, $subprefix_numbers);
                        }
                    }
                }
            } else {
                # get objects
                $numbers = $Tools->fetch_multiple_objects ("pstnNumbers", "prefix", $prefix->id, "number", true);
            }

            # get count
            $details = $Tools->calculate_prefix_usege( $prefix, $numbers);

            print "<div class='btn-group'>";
            if($prefix->master > 0)
                print "<a href='".create_link($GET->page, "pstn-prefixes", $isMaster ? $back_link : $prefix->master)."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> ". _('Master prefix')."</a>";
            else
                print "<a href='".create_link($GET->page, "pstn-prefixes")."' style='margin-bottom:20px;' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> ". _('All prefixes')."</a>";
            print "</div>";
            print "<br>";

            print "<div class='row'>";
            print "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-8'>";

            print "<h4>"._('Prefix details')."</h4><hr>";
            print "<table class='ipaddress_subnet table-condensed table-auto'>";

        	# name
        	print "<tr>";
        	print " <th>"._('Name')."</th>";
        	print " <td><strong>$prefix->name</strong></td>";
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
        	print $Subnets->print_breadcrumbs($Sections, $Subnets, $GET->as_array());
        	print "</td>";
        	print "</tr>";

        	# description
        	print "<tr>";
        	print "	<th>"._('Description')."</th>";
        	print "	<td>$prefix->description</td>";
        	print "</tr>";

            if($User->get_module_permissions ("devices")>=User::ACCESS_R) {
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
            }

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

        	# print custom subnet fields if any
        	if(sizeof($cfields) > 0) {
        		// divider
        		print "<tr><td colspan='2'><hr></td></tr>";
        		// fields
        		foreach($cfields as $key=>$field) {
        			$prefix->{$key} = str_replace("\n", "<br>",$prefix->{$key});
        			// create links
        			$prefix->{$key} = $Tools->create_links($prefix->{$key});
        			print "<tr>";
        			print "	<th>".$Tools->print_custom_field_name ($key)."</th>";
        			print "	<td style='vertical-align:top;align-content:left;'>".$prefix->{$key}."</td>";
        			print "</tr>";
        		}
        	}

        	# actions
        	print "<tr>";
        	print "	<th></th>";
        	print "	<td>";
        	print " <div class='btn-group'>";

            $links = [];
            if($User->get_module_permissions ("pstn")>=User::ACCESS_RW) {
                if(!$isMaster) {
                $links[] = ["type"=>"header", "text"=>_("Create address")];
                $links[] = ["type"=>"link", "text"=>_("Add address to prefix"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit-number.php' data-class='700' data-action='add' data-id='$prefix->id'", "icon"=>"plus"];
                }
                $links[] = ["type"=>"header", "text"=>_("Create")];
                $links[] = ["type"=>"link", "text"=>_("Create new prefix"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit.php' data-class='700' data-action='add' data-id='$prefix->id'", "icon"=>"plus-circle"];
                $links[] = ["type"=>"divider"];
                $links[] = ["type"=>"header", "text"=>_("Manage")];
                $links[] = ["type"=>"link", "text"=>_("Edit prefix"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit.php' data-class='700' data-action='edit' data-id='$prefix->id'", "icon"=>"pencil"];
            }
            if($User->get_module_permissions ("pstn")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"link", "text"=>_("Delete prefix"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit.php' data-class='700' data-action='delete' data-id='$prefix->id'", "icon"=>"times"];
            }
            print $User->print_actions($User->user->compress_actions, $links, true);

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
            if ($isMaster) {
                print "<h4>"._('Belonging prefixes')."</h4><hr>";
                include("single-prefix-slaves-list.php");
            } else {
                print "<h4>"._('Belonging Numbers')."</h4><hr>";
                include("single-prefix-numbers.php");
            }
            print "</div>";
        }
    }
}