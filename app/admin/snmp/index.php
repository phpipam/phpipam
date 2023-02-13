<?php

/**
 * Script to print devices
 ***************************/

# verify that user is logged in
$User->check_user_session();

# fetch all Devices
$Snmp = new phpipamSNMP ();

# fetch all Device types and reindex
$device_types = $Admin->fetch_all_objects("deviceTypes", "tid");
if ($device_types !== false) {
	foreach ($device_types as $dt) {
		$device_types_i[$dt->tid] = $dt;
	}
}

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');

# get hidden fields
$hidden_custom_fields = pf_json_decode($User->settings->hiddenCustomFields, true);
$hidden_custom_fields = is_array(@$hidden_custom_fields['devices']) ? $hidden_custom_fields['devices'] : array();
?>

<h4><?php print _('SNMP management'); ?></h4>
<hr>
<div class="btn-group">
	<a href="<?php print create_link("administration", "devices"); ?>" class="btn btn-xs btn-default" style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Manage devices'); ?></a>
</div>

<?php
/* first check if they exist! */
if($User->settings->enableSNMP===false) {
	$Result->show("danger alert-absolute", _('SNMP module disabled').'!', false);
}
/* Print them out */
else {

	print '<table class="table sorted table-condensed table-striped table-td-top" data-cookie-id-table="admin_snmp">';

	# headers
	print "<thead>";
	print '<tr>';
	print '	<th>'._('Query').'</th>';
	print '	<th>'._('Description').'</th>';
	print '	<th>'._('OID').'</th>';
	print '	<th>'._('Devices').'</th>';
// 	print '	<th class="actions"></th>';
	print '</tr>';
    print "</thead>";

    print "<tbody>";
	# loop through devices
	foreach ($Snmp->snmp_queries as $k=>$query) {

    	// get associated devices
    	$devices_used = $Tools->fetch_multiple_objects ("devices", "snmp_queries", "%$k%", "id", true, true);

		//print details
		print "<tr>";
        print " <td><strong>$k</strong></td>";
        print " <td>$query->description</td>";
        print " <td><span class='badge badge1'>$query->oid</span></td>";

        // devices
        print " <td>";
        if ($devices_used===false) {
            $Result->show("info", _('No devices'), false);
        }
        else {
            foreach ($devices_used as $d) {
                print "<a href='".create_link("tools","devices", $d->id)."'>".$d->hostname."</a><br>";
            }
        }
        print " </td>";

/*
		print '	<td class="actions">'. "\n";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default edit-snmp-device-query' data-action='add'    data-query='$k' rel='tooltip' title='Add device to query'><i class='fa fa-plus'></i></button>";
		print "		<button class='btn btn-xs btn-default edit-snmp-device-query' data-action='delete' data-query='$k' rel='tooltip' title='Remove device from query'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print '	</td>'. "\n";
*/

		print '</tr>'. "\n";

	}
	print "</tbody>";
	print '</table>';
}
?>

<!-- edit result holder -->
<div class="switchManagementEdit"></div>
