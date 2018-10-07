<?php

/**
 * Script to display devices
 */

# verify that user is logged in
$User->check_user_session();

# check
is_numeric($_GET['subnetId']) ? : $Result->show("danger", _("Invalid ID"), true);

# fetch device
$device = (array) $Tools->fetch_object ("devices", "id", $_GET['subnetId']);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');

# title
print "<h4>"._('Device details')."</h4>";
print "<hr>";

# print
if($_GET['subnetId']!=0 && sizeof($device)>0) {

    print "<table class='table table-noborder'>";
    print "<tr>";
    print "<td style='vertical-align:top !important;'>";

	# set type
	$device_type = $Tools->fetch_object("deviceTypes", "tid", $device['type']);

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

        if($User->settings->enableLocations=="1") { ?>
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

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
    	print "</tr>";

    	print '<tr>';
    	print "	<th>". _('Sections').':</th>';
    	print "	<td>";
    	if(strlen($device['hostname'])>0) {
    		$section_ids = explode(";", $device['sections']);
    		foreach($section_ids as $k=>$id) {
    			$section = $Sections->fetch_section(null, $id);
    			$section_print[$k]  = "&middot; ".$section->name;
    			$section_print[$k] .= strlen($section->description)>0 ? " <span class='text-muted'>($section->description)</span>" : "";
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
            $version = $device['snmp_version']=="0" ? "<span class='text-muted'>Disabled</span>" : "Version ".$device['snmp_version'];
            print '<tr>';
            print " <th>". _('SNMP version').'</th>';
            print " <td>$version</td>";
            print "</tr>";

            // set show
            if ($device['snmp_version']=="1" || $device['snmp_version']=="2" || $device['snmp_version']=="3") {
                // version
                print '<tr>';
                print " <th>". _('Community').'</th>';
                print " <td>$device[snmp_community]</td>";
                print "</tr>";
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
                print " <td>$device[snmp_v3_auth_pass]</td>";
                print "</tr>";
                // privacy proto
                print '<tr>';
                print " <th>". _('Privacy protocol').'</th>';
                print " <td>$device[snmp_v3_priv_protocol]</td>";
                print "</tr>";
                // privacy pass
                print '<tr>';
                print " <th>". _('Privacy passphrase').'</th>';
                print " <td>$device[snmp_v3_priv_pass]</td>";
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
        if($User->settings->enableNAT=="1") {
    	print " <th>"._('NAT')."</th>";
    	print " <td><span class='badge badge1 badge5'>$cnt_nat "._('NAT')."</span></td>";
    	print "</tr>";
        }
        if($User->settings->enablePSTN=="1") {
        print " <th>"._('PSTN')."</th>";
        print " <td><span class='badge badge1 badge5'>$cnt_pstn "._('PSTN')."</span></td>";
        print "</tr>";
        }
        if($User->settings->enableCircuits=="1") {
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
    			$device[$field['name']] = $Result->create_links ($device[$field['name']]);

    			print "<tr>";
    			print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
    			print "<td>".$device[$field['name']]."</d>";
    			print "</tr>";
    		}

    		print "<tr>";
    		print "	<td colspan='2'><hr></td>";
    		print "</tr>";
    	}

    	print "<tr>";
    	print "	<td></td>";

    	if($User->is_admin(false)) {
    		print "	<td class='actions'>";
    		print "	<div class='btn-group'>";
    		print "		<button class='btn btn-xs btn-default editSwitch' data-action='edit'   data-switchid='".$device['id']."'><i class='fa fa-pencil'></i></button>";
            if($User->settings->enableSNMP=="1")
            print "     <button class='btn btn-xs btn-default editSwitchSNMP' data-action='edit' data-switchid='$device[id]' rel='tooltip' title='Manage SNMP'><i class='fa fa-cogs'></i></button>";
    		print "		<button class='btn btn-xs btn-default editSwitch' data-action='delete' data-switchid='".$device['id']."'><i class='fa fa-times'></i></button>";
    		print "	</div>";
    		print " </td>";
    	}
    	else {
    		print "	<td class='small actions'>";
    		print "	<div class='btn-group'>";
    		print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
    		print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
    		print "	</div>";
    		print " </td>";
    	}
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
	if ($User->settings->enableRACK=="1") {

    	print "<td vertical-align:top !important;' class='text-right'>";
        // validate rack
        $rack = $Tools->fetch_object ("racks", "id", $device['rack']);
        if ($rack!==false) {
            // front
            print " <img src='".$Tools->create_rack_link ($device['rack'], $device['id'])."' class='pul1l-right' style='width:180px;'>";
            // back
            if($rack->hasBack!="0") {
            print " <img src='".$Tools->create_rack_link ($device['rack'], $device['id'], true)."' class='pull-r1ight' style='width:180px;margin-left:5px;'>";
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

?>