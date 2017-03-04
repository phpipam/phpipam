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

# print link to manage
print "<div class='btn-group'>";
print "<a class='btn btn-sm btn-default' href='".create_link("tools","devices")."' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-angle-left'></i> ". _('All devices')."</a>";
print "</div>";

# print
if($_GET['subnetId']!=0 && sizeof($device)>0) {

    print "<table class='table'>";
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

    	print "<tr>";
    	print "	<td colspan='2'><hr></td>";
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
    	print " <th>"._('NAT')."</th>";
    	print " <td><span class='badge badge1 badge5'>$cnt_nat "._('NAT')."</span></td>";
    	print "</tr>";

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
    			print "<th>$field[name]</th>";
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
    		print "		<button class='btn btn-xs btn-default editSwitch' data-action='edit'   data-switchid='".$device['id']."'><i class='fa fa-gray fa-pencil'></i></button>";
    		print "		<button class='btn btn-xs btn-default editSwitch' data-action='delete' data-switchid='".$device['id']."'><i class='fa fa-gray fa-times'></i></button>";
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

    	print "<td>";

        // validate rack
        $rack = $Tools->fetch_object ("racks", "id", $device['rack']);
        if ($rack!==false) {

        print " <td style='width:200px; vertical-align:top !important;'>";
            # title
            print "     <img src='".$Tools->create_rack_link ($device['rack'], $device['id'])."' class='pull-right' style='width:200px;'>";
        print " </td>";
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