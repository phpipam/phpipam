<?php

/**
 * Script to display devices
 */

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("devices", User::ACCESS_R, true, false);

# check
is_numeric($GET->subnetId) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch device
$device = (array) $Tools->fetch_object ("devices", "id", $GET->subnetId);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');

# title
print "<h4>"._('Device details')."</h4>";
print "<hr>";

# print
if($GET->subnetId!=0 && sizeof($device)>0) {

    print "<table class='table table-noborder'>";
    print "<tr>";
    print "<td style='vertical-align:top !important;'>";

	# set type
	$device_type = $Tools->fetch_object("deviceTypes", "tid", $device['type']);
    if (!is_object($device_type)) {
        $device_type = new Params();
    }

    # device
	print "<table class='ipaddress_subnet table-condensed table-auto'>";

    	print '<tr>';
    	print "	<th>". _('Name').'</a></th>';
    	print "	<td>$device[hostname]</td>";
    	print "</tr>";
    	print '<tr>';
    	print "	<th>". _('IP address').'</th>';
    	print "	<td>$device[ip_addr]</td>";
    	print "</tr>";
    	print '<tr>';
    	print "	<th>". _('Description').'</th>';
    	print "	<td>$device[description]</td>";
    	print "</tr>";
    	print '<tr>';
    	print "	<th>". _('Type').'</th>';
    	print "	<td>$device_type->tname</td>";
    	print "</tr>";

        if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
    	<tr>
    		<th><?php print _('Location'); ?></th>
    		<td>
    		<?php

    		// Only show nameservers if defined for subnet
    		if(!empty($device['location']) && $device['location']!=0) {
    			# fetch recursive nameserver details
    			$location2 = $Tools->fetch_object("locations", "id", $device['location']);
                if($location2!==false) {
                    print "<a href='".create_link("tools", "locations", $device['location'])."'>$location2->name</a>";
                }
    		}

    		else {
    			print "<span class='text-muted'>/</span>";
    		}
    		?>
    		</td>
    	</tr>

        <?php }

        // acrtions
        if($User->get_module_permissions ("devices")>=User::ACCESS_RW) {
            print "<tr>";
            print " <td></td>";
            print " <td>";

            $links = [];
            $links[] = ["type"=>"header", "text"=>_("Manage device")];
            $links[] = ["type"=>"link", "text"=>_("Edit device"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/devices/edit.php' data-class='500' data-action='edit' data-switchId='$device[id]'", "icon"=>"pencil"];

            if($User->get_module_permissions ("devices")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"link", "text"=>_("Delete device"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/devices/edit.php' data-class='500' data-action='delete' data-switchId='$device[id]'", "icon"=>"times"];
                $links[] = ["type"=>"divider"];
            }
            if($User->settings->enableSNMP=="1" && $User->is_admin(false)) {
                $links[] = ["type"=>"header", "text"=>_("SNMP")];
                $links[] = ["type"=>"link", "text"=>_("Manage SNMP"), "href"=>"", "class"=>"open_popup", "dataparams"=>"  data-script='app/admin/devices/edit-snmp.php' data-class='500' data-action='delete' data-switchId='$device[id]'", "icon"=>"cogs"];
            }
            // print links
            print $User->print_actions($User->user->compress_actions, $links, true, true);
            print " </td>";
            print "</tr>";
        }


    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";

    	print '<tr>';
    	print "	<th>". _('Sections').':</th>';
    	print "	<td>";
    	if(!is_blank($device['hostname'])) {
    		$section_ids = pf_explode(";", $device['sections']);
    		foreach($section_ids as $k=>$id) {
    			$section = $Sections->fetch_section(null, $id);
                if (!is_object($section)) {
                    $section = new Params();
                }
    			$section_print[$k]  = "&middot; ".$section->name;
    			$section_print[$k] .= !is_blank($section->description) ? " <span class='text-muted'>($section->description)</span>" : "";
    		}
    		print implode("<br>", $section_print);
    	}
    	print "</td>";
    	print "</tr>";


        if($User->settings->enableSNMP=="1") {
            // title
            print '<tr>';
            print " <td colspan='2'><h4 style='padding-top:20px;'>". _('SNMP data').'</h4><hr></td>';
            print "</tr>";

            // version
            $version = $device['snmp_version']=="0" ? "<span class='text-muted'>"._("Disabled")."</span>" : _("Version")." ".$device['snmp_version'];
            print '<tr>';
            print " <th>". _('SNMP version').'</th>';
            print " <td>$version</td>";
            print "</tr>";

            // set show
            if ($device['snmp_version']=="1" || $device['snmp_version']=="2" || $device['snmp_version']=="3") {
                // version
                print '<tr>';
                print " <th>". _('Community').'</th>';
                print $User->is_admin(false) ? " <td>$device[snmp_community]</td>" : " <td>********</td>";
                // port
                print '<tr>';
                print " <th>". _('Port').'</th>';
                print " <td>$device[snmp_port]</td>";
                print "</tr>";
                // timeout
                print '<tr>';
                print " <th>". _('Timeout').'</th>';
                print " <td>$device[snmp_timeout]</td>";
                print "</tr>";
            }
            // v3 info
            if ($device['snmp_version']=="3") {
                print "<tr>";
                print " <td colspan='2'><hr></td>";
                print "</tr>";
                // sec level
                print '<tr>';
                print " <th>". _('Security level').'</th>';
                print " <td>$device[snmp_v3_sec_level]</td>";
                print "</tr>";
                // auth proto
                print '<tr>';
                print " <th>". _('Auth protocol').'</th>';
                print " <td>$device[snmp_v3_auth_protocol]</td>";
                print "</tr>";
                // pass
                print '<tr>';
                print " <th>". _('Password').'</th>';
                $User->is_admin(false) ? print " <td>".escape_input($device['snmp_v3_auth_pass'])."</td>" : " <td>********</td>";
                print "</tr>";
                // privacy proto
                print '<tr>';
                print " <th>". _('Privacy protocol').'</th>';
                print " <td>$device[snmp_v3_priv_protocol]</td>";
                print "</tr>";
                // privacy pass
                print '<tr>';
                print " <th>". _('Privacy passphrase').'</th>';
                $User->is_admin(false) ? print " <td>".escape_input($device['snmp_v3_priv_pass'])."</td>" : " <td>********</td>";
                print "</tr>";
                // context name
                print '<tr>';
                print " <th>". _('Context name').'</th>';
                print " <td>$device[snmp_v3_ctx_name]</td>";
                print "</tr>";
                // engine id
                print '<tr>';
                print " <th>". _('Context engine ID').'</th>';
                print " <td>$device[snmp_v3_ctx_engine_id]</td>";
                print "</tr>";
            }
        }


        // title
        print '<tr>';
        print " <td colspan='2'><h4 style='padding-top:20px;'>". _('Objects').'</h4><hr></td>';
        print "</tr>";



    	print "<tr>";
    	print " <th>"._('Subnets')."</th>";
    	print " <td><span class='badge badge1 badge5'>$cnt_subnets "._('Subnets')."</span></td>";
    	print "</tr>";
    	print "<tr>";
    	print " <th>"._('Addresses')."</th>";
    	print " <td><span class='badge badge1 badge5'>$cnt_addresses "._('Addresses')."</span></td>";
    	print "</tr>";
    	print "<tr>";
        if($User->settings->enableNAT=="1" && $User->get_module_permissions ("nat")>=User::ACCESS_R) {
    	print " <th>"._('NAT')."</th>";
    	print " <td><span class='badge badge1 badge5'>$cnt_nat "._('NAT')."</span></td>";
    	print "</tr>";
        }
        if($User->settings->enablePSTN=="1" && $User->get_module_permissions ("pstn")>=User::ACCESS_R) {
        print " <th>"._('PSTN')."</th>";
        print " <td><span class='badge badge1 badge5'>$cnt_pstn "._('PSTN')."</span></td>";
        print "</tr>";
        }
        if($User->settings->enableCircuits=="1" && $User->get_module_permissions ("pstn")>=User::ACCESS_R) {
        print " <th>"._('Circuits')."</th>";
        print " <td><span class='badge badge1 badge5'>$cnt_circuits "._('Circuits')."</span></td>";
        print "</tr>";
        }

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";


    	if(sizeof($custom_fields) > 0) {
    		foreach($custom_fields as $field) {

    			# fix for boolean
    			if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
    				if($device[$field['name']]=="0")		{ $device[$field['name']] = "false"; }
    				elseif($device[$field['name']]=="1")	{ $device[$field['name']] = "true"; }
    				else									{ $device[$field['name']] = ""; }
    			}

    			# create links
    			$device[$field['name']] = $Tools->create_links ($device[$field['name']]);

    			print "<tr>";
    			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
    			print "<td>".$device[$field['name']]."</td>";
    			print "</tr>";
    		}

    		print "<tr>";
    		print "	<td colspan='2'><hr></td>";
    		print "</tr>";
    	}

    	print "<tr>";
    	print "	<td></td>";

    	print "</tr>";

    print "</table>";
    print "</td>";

    # format device
    if(empty($device['hostname'])) {
    	$device['hostname'] = _('Device not specified');
    }
    else 										{
    	if(empty($device['hostname'])) 				{ $device['hostname'] = "Unspecified";}
    }


	# rack
	if ($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>=User::ACCESS_R) {

    	print "<td vertical-align:top !important;' class='text-right'>";
        // validate rack
        $rack = $Tools->fetch_object ("racks", "id", $device['rack']);
        if ($rack!==false) {
            // front
            print " <img src='".$Tools->create_rack_link ($device['rack'], $device['id'])."' class='pull-right' style='width:180px;'>";
            // back
            if($rack->hasBack!="0") {
            print " <img src='".$Tools->create_rack_link ($device['rack'], $device['id'], true)."' class='pull-right' style='width:180px;margin-left:5px;'>";
            }
        }
        print "</td>";
    }

	print "</tr>";

    print "</table>";
}
else {
    $Result->show("danger", _('Invalid ID'), false);
}
