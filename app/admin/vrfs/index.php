<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all vrfs
$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId");

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

<button class='btn btn-sm btn-default vrfManagement' data-action='add' data-vrfid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add VRF'); ?></button>

<!-- vrfs -->
<?php

# first check if they exist!
if($all_vrfs===false) { $Result->show("info", _("No VRFs configured")."!", false);}
else {
	print '<table id="vrfManagement" class="table table-striped table-top table-hover table-auto">'. "\n";

	# headers
	print '<tr>'. "\n";
	print '	<th>'._('Name').'</th>'. "\n";
	print '	<th>'._('RD').'</th>'. "\n";
	print '	<th>'._('Sections').'</th>'. "\n";
	print '	<th>'._('Description').'</th>'. "\n";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<th class='customField hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
	}
	print '	<th></th>'. "\n";
	print '</tr>'. "\n";

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
    		unset($sec);
    		$sections_tmp = explode(";", $vrf['sections']);
    		foreach($sections_tmp as $t) {
    			//fetch section
    			$tmp_section = $Sections->fetch_section(null, $t);
    			$sec[] = " &middot; ".$tmp_section->name;
    		}
    		//implode
    		$sections = implode("<br>", $sec);
    	}

		//print details
		print '<tr class="text-top">'. "\n";
		print '	<td class="name">'. $vrf['name'] .'</td>'. "\n";
		print '	<td class="rd">'. $vrf['rd'] .'</td>'. "\n";
		print "	<td><span class='text-muted'>$sections</span></td>";
		print '	<td class="description">'. $vrf['description'] .'</td>'. "\n";

		// custom fields
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				if(!in_array($field['name'], $hidden_fields)) {

					print "<td class='customField hidden-xs hidden-sm'>";

					// create links
					$vrf[$field['name']] = $Result->create_links ($vrf[$field['name']], $field['type']);

					//booleans
					if($field['type']=="tinyint(1)")	{
						if($vrf[$field['name']] == "0")		{ print _("No"); }
						elseif($vrf[$field['name']] == "1")	{ print _("Yes"); }
					}
					//text
					elseif($field['type']=="text") {
						if(strlen($vrf[$field['name']])>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $vrf[$field['name']])."'>"; }
						else											{ print ""; }
					}
					else {
						print $vrf[$field['name']];

					}
					print "</td>";
				}
			}
		}

		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default vrfManagement' data-action='edit'   data-vrfid='$vrf[vrfId]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default vrfManagement' data-action='delete' data-vrfid='$vrf[vrfId]'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print "	</td>";
		print '</tr>'. "\n";
	}
	print '</table>'. "\n";
}
?>

<!-- edit result holder -->
<div class="vrfManagementEdit"></div>