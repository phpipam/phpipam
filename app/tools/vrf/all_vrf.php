<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vrf", User::ACCESS_R, true, false);

# fetch all vrfs
$all_vrfs = $User->fetch_all_objects("vrf", "name");

# fetch custom fields
$custom = $Tools->fetch_custom_fields('vrf');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vrf']) ? $hidden_fields['vrf'] : array();

# set size of custom fields
$custom_size = sizeof($custom) - sizeof($hidden_fields);
?>

<h4><?php print _('Manage VRF'); ?></h4>
<hr><br>

<div class="btn-group">
    <button class='btn btn-sm btn-default open_popup' data-script='app/admin/vrf/edit.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Add VRF'); ?></button>
    <?php
    // snmp
    if($User->is_admin(false)===true && $User->settings->enableSNMP==1) { ?>
	<button class="btn btn-sm btn-default" id="snmp-vrf" data-action="add"><i class="fa fa-cogs"></i> <?php print _('Scan for VRFs'); ?></button>
	<?php } ?>

</div>

<!-- vrfs -->
<?php

# first check if they exist!
if($all_vrfs===false) {
	$Result->show("info", _("No VRFs configured")."!", false);
}
else {
	print '<table id="vrfManagement" class="table sorted table-striped table-top table-hover" data-cookie-id-table="admin_vrf">'. "\n";

	# headers
	print "<thead>";
	print '<tr>'. "\n";
	print '	<th>'._('Name').'</th>'. "\n";
	print '	<th>'._('RD').'</th>'. "\n";
	print '	<th>'._('Sections').'</th>'. "\n";
	print '	<th>'._('Description').'</th>'. "\n";
	if($User->settings->enableCustomers=="1")
	print ' <th data-field="customer" data-sortable="true">'._('Customer').'</th>' . "\n";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<th class='customField hidden-xs hidden-sm'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			}
		}
	}
	print '	<th></th>'. "\n";
	print '</tr>'. "\n";
	print "</thead>";

    print "<tbody>";
	# loop
	foreach ($all_vrfs as $vrf) {
		//cast
		$vrf = (array) $vrf;

    	// format sections
    	if(strlen($vrf['sections'])==0) {
    		$sections = "All sections";
    	}
    	else {
    		//explode
    		$sec = [];
    		$sections_tmp = explode(";", $vrf['sections']);
    		foreach($sections_tmp as $t) {
    			//fetch section
    			$tmp_section = $Sections->fetch_section(null, $t);
				if (is_object($tmp_section)) {
    				$sec[] = " &middot; ".$tmp_section->name;
				}
    		}
    		//implode
    		$sections = implode("<br>", $sec);
    	}
		//print details
		print '<tr class="text-top">'. "\n";
		print '	<td class="name"><a class="btn btn-xs btn-default" href="'.create_link($_GET['page'],"vrf",$vrf['vrfId']).'"><i class="fa fa-cloud prefix"></i>'. $vrf['name'] .'</a></td>'. "\n";
		print '	<td class="rd">'. $vrf['rd'] .'</td>'. "\n";
		print "	<td><span class='text-muted'>$sections</span></td>";
		print '	<td class="description">'. $vrf['description'] .'</td>'. "\n";

		// customer
		if($User->settings->enableCustomers=="1") {
			 $customer = $Tools->fetch_object ("customers", "id", $vrf['customer_id']);
			 print $customer===false ? "<td></td>" : "<td>{$customer->title} <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></td>";
		}

		// custom fields
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_fields)) {
					print "<td class='customField hidden-xs hidden-sm'>";
					$Tools->print_custom_field ($field['type'], $vrf[$field['name']]);
					print "</td>";
				}
			}
		}

		// actions
        print "<td class='actions'>";
        $links = [];
        $links[] = ["type"=>"header", "text"=>_("Show")];
        $links[] = ["type"=>"link", "text"=>_("Show VRF"), "href"=>create_link($_GET['page'], "vrf", $vrf['vrfId']), "icon"=>"eye", "visible"=>"dropdown"];
        $links[] = ["type"=>"divider"];
        if($User->get_module_permissions ("vrf")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit VRF"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vrf/edit.php' data-class='700' data-action='edit' data-vrfid='$vrf[vrfId]'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("vrf")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete VRF"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vrf/edit.php' data-class='700' data-action='delete' data-vrfid='$vrf[vrfId]'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links);
        print "</td>";

	}
	print "</tbody>";
	print '</table>'. "\n";
}
?>

<!-- edit result holder -->
<div class="vrfManagementEdit"></div>