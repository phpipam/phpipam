<?php

/**
 * Script to print devices
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch all devices
$devices = $Admin->fetch_all_objects("deviceTypes", "tid");
?>

<h4><?php print _('Device type management'); ?></h4>
<hr>

<div class="btn-group">
	<a href="<?php print create_link("administration", "devices"); ?>" class="btn btn-sm btn-default"><i class='fa fa-angle-left'></i> <?php print _('Manage devices'); ?></a>
	<button class='btn btn-sm btn-default editDevType' data-action='add'   data-tid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add device type'); ?></button>
</div>

<?php
/* first check if they exist! */
if(sizeof($devices) == 0) {
	print '	<div class="alert alert-warn alert-absolute">'._('No devices configured').'!</div>'. "\n";
}
/* Print them out */
else {

	print '<table id="switchManagement" class="table table-striped table-auto table-top">';

	#headers
	print '<tr>';
	print '	<th>'._('Name').'</th>';
	print '	<th>'._('Description').'</th>';
	print '	<th class="actions"></th>';
	print '</tr>';

	foreach ($devices as $type) {
		//cast
		$type = (array) $type;

		//print details
		print '<tr>'. "\n";

		print '	<td>'. _($type['tname']) .'</td>'. "\n";
		print '	<td>'. _($type['tdescription']) .'</td>'. "\n";

		print '	<td class="actions">'. "\n";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editDevType' data-action='edit'   data-tid='$type[tid]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default editDevType' data-action='delete' data-tid='$type[tid]'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print '	</td>'. "\n";

		print '</tr>'. "\n";

	}
	print '</table>';
}

?>

<!-- edit result holder -->
<div class="switchManagementEdit"></div>