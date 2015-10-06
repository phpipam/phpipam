<?php

/**
 *	Show all address / subnet tags
 ************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all vrfs
$all_types = $Admin->fetch_all_objects("ipTags");
?>

<h4><?php print _('Manage Address Tags'); ?></h4>
<hr><br>

<button class='btn btn-sm btn-default editType' data-action='add' data-vrfid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add Tag'); ?></button>

<!-- vrfs -->
<?php
print '<table class="table table-striped table-top table-auto">'. "\n";

# headers
print '<tr>'. "\n";
print '	<th>'._('type').'</th>'. "\n";
print '	<th>'._('Show Tag').'</th>'. "\n";
print '	<th>'._('BG color').'</th>'. "\n";
print '	<th>'._('FG color').'</th>'. "\n";
print '	<th>'._('Compress range').'</th>'. "\n";
print '	<th>'._('Locked').'</th>'. "\n";
print '	<th></th>'. "\n";
print '</tr>'. "\n";

# loop
if ($all_types!==false) {
	// cast
	$all_types = (array) $all_types;
	// loop
	foreach ($all_types as $type) {
		//cast
		$type = (array) $type;

		//format type
		$showtag = $type['showtag']==1 ? "Yes" : "No";

		//print details
		print '<tr>'. "\n";
		print '	<td>'. $type['type'] .'</td>'. "\n";
		print '	<td>'.$showtag.'</td>'. "\n";
		print '	<td style="background-color:'.$type['bgcolor'].'">'. $type['bgcolor'] .'</td>'. "\n";
		print '	<td style="background-color:'.$type['fgcolor'].'">'. $type['fgcolor'] .'</td>'. "\n";
		print '	<td>'. $type['compress'] .'</td>'. "\n";
		print '	<td>'. $type['locked'] .'</td>'. "\n";

		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editType' data-action='edit'   data-id='$type[id]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default editType' data-action='delete' data-id='$type[id]'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print "	</td>";
		print '</tr>'. "\n";
	}
}
print '</table>'. "\n";
?>

<!-- edit result holder -->
<div class="vrfManagementEdit"></div>