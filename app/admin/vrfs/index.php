<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all vrfs
$all_vrfs = $Admin->fetch_all_objects("vrf", "vrfId");
?>

<h4><?php print _('Manage VRF'); ?></h4>
<hr><br>

<button class='btn btn-sm btn-default vrfManagement' data-action='add' data-vrfid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add VRF'); ?></button>

<!-- vrfs -->
<?php

# first check if they exist!
if($all_vrfs===false) { $Result->show("danger", _("No VRFs configured")."!", true);}
else {
	print '<table id="vrfManagement" class="table table-striped table-top table-hover table-auto">'. "\n";

	# headers
	print '<tr>'. "\n";
	print '	<th>'._('Name').'</th>'. "\n";
	print '	<th>'._('RD').'</th>'. "\n";
	print '	<th>'._('Description').'</th>'. "\n";
	print '	<th></th>'. "\n";
	print '</tr>'. "\n";

	# loop
	foreach ($all_vrfs as $vrf) {
		//cast
		$vrf = (array) $vrf;

		//print details
		print '<tr>'. "\n";
		print '	<td class="name">'. $vrf['name'] .'</td>'. "\n";
		print '	<td class="rd">'. $vrf['rd'] .'</td>'. "\n";
		print '	<td class="description">'. $vrf['description'] .'</td>'. "\n";
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