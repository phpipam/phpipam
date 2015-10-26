<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display devices
 *
 */


# for ajax-loaded pages
if(!is_object($Subnets)) {
	# include required scripts
	require( dirname(__FILE__) . '/../../../functions/functions.php' );

	# initialize required objects
	$Database 	= new Database_PDO;
	$Result		= new Result;
	$User		= new User ($Database);
	$Subnets	= new Subnets ($Database);
	$Tools	    = new Tools ($Database);
	$Addresses	= new Addresses ($Database);
}

# verify that user is logged in
$User->check_user_session();


# sorting
if(!isset($_POST['direction'])) {
	$sort['direction'] = 'asc';
	$sort['field']	   = 'hostname';

	$sort['directionNext'] = "desc";

	$_POST['direction']  = "hostname|asc";

	# fields
	$_POST['ffield'] = null;
	$_POST['fval']   = null;
}
else {
	//format posted values!
	$tmp = explode("|", $_POST['direction']);

	$sort['field'] 	   = $tmp[0];
	$sort['direction'] = $tmp[1];

	if($sort['direction'] == "asc") { $sort['directionNext'] = "desc"; }
	else 							{ $sort['directionNext'] = "asc"; }
}

# filter devices or fetch print all?
$filter = isset($_POST['ffield']) ? true : false;
$devices = $Tools->fetch_devices ($_POST['ffield'], $_POST['fval'], $sort['field'], $sort['direction']);
$device_types = $Tools->fetch_all_objects ("deviceTypes", "tid");

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['devices']) ? $hidden_fields['devices'] : array();


# sort icons
if($sort['direction'] == 'asc') 	{ $icon = "<i class='fa fa-angle-down'></i> "; }
else								{ $icon = "<i class='fa fa-angle-up'></i> "; }

# title
print "<h4>"._('List of network devices')."</h4>";
print "<hr>";

//filter
print "<div class='filter' style='margin-bottom:5px;text-align:right'>";
print "<form class='form-inline' id='deviceFilter'>";
	//select
	$select = array("hostname"=>"Hostname", "ip_addr"=>"IP address", "description"=>"Description", "type"=>"Type", "vendor"=>"Vendor", "model"=>"Model", "version"=>"Version");
	foreach($custom_fields as $c) {
		$select[$c['name']] = $c['name'];
	}

	print "	<select class='form-control input-sm' name='ffield'>";
	foreach($select as $k=>$v) {
		if(@$_POST['ffield']==$k)	{ print "<option value='$k' selected='selected'>"._("$v")."</option>"; }
		else						{ print "<option value='$k'>"._("$v")."</option>"; }
	}
	print "	</select>";

	//field
	print "<input type='text' name='fval' class='input-sm form-control' value='".@$_POST['fval']."' placeholder='"._('Search string')."'>";
	print "<input type='hidden' name='direction' value='$_POST[direction]'>";
	print "<input type='submit' class='btn btn-sm btn-default' value='"._("Filter")."'>";

print "</form>";
print "</div>";


# filter notification
if($filter)
$Result->show("warning", _('Filter applied:'). " $_POST[ffield] like *$_POST[fval]*", false);

print '<table id="switchManagement" class="table table-striped table-top">';

#headers
print '<tr>';
print "	<th><a href='' data-id='hostname|$sort[directionNext]' 		class='sort' rel='tooltip' data-container='body' title='"._('Sort by hostname')."'>"; ; 	if($sort['field'] == "hostname") 	print $icon; print _('Name').'</a></th>';
print "	<th><a href='' data-id='ip_addr|$sort[directionNext]'  	 	class='sort' rel='tooltip' data-container='body' title='"._('Sort by IP address')."'>"; ; 	if($sort['field'] == "ip_addr") 	print $icon; print _('IP address').'</th>';
print "	<th><a href='' data-id='description|$sort[directionNext]'  	class='sort' rel='tooltip' data-container='body' title='"._('Sort by description')."'>"; ; 	if($sort['field'] == "description") print $icon; print _('Description').'</th>';
print "	<th style='color:#428bca'>"._('Number of hosts').'</th>';
print "	<th class='hidden-sm'><a href='' 		   data-id='type|$sort[directionNext]'    class='sort' rel='tooltip' data-container='body' title='"._('Sort by type')."'>"; ; 		if($sort['field'] == "type") print $icon; print _('Type').'</th>';
print "	<th class='hidden-sm hidden-xs'><a href='' data-id='vendor|$sort[directionNext]'  class='sort' rel='tooltip' data-container='body' title='"._('Sort by vendor')."'>"; ; 	if($sort['field'] == "vendor") print $icon; print _('Vendor').'</th>';
print "	<th class='hidden-sm hidden-xs'><a href='' data-id='model|$sort[directionNext]'   class='sort' rel='tooltip' data-container='body' title='"._('Sort by model')."'>"; ; 		if($sort['field'] == "model") print $icon; 	print _('Model').'</th>';

if(sizeof(@$custom) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'><a href='' data-id='$field[name]|$sort[directionNext]'  class='sort' rel='tooltip' data-container='body' title='"._('Sort by')." $field[name]'>"; ; 	if($sort['field'] == $field['name']) print $icon; print $field['name']."</th>";
			$colspanCustom++;
		}
	}
}
print '	<th class="actions"></th>';
print '</tr>';

// search - none found
if(sizeof(@$devices) == 0 && isset($filter)) {
	$colspan = 8 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No devices configured')."!", false, false, true)."</td>";
	print "</tr>";
}
// no devices
elseif(sizeof(@$devices) == 0) {
	$colspan = 8 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($devices as $device) {
	//cast
	$device = (array) $device;

	//count items
	$cnt = $Tools->count_device_addresses($device['id']);

	// reindex types
	if (isset($device_types)) {
		foreach($device_types as $dt) {
			$device_types_indexed[$dt->tid] = $dt;
		}
	}

	//print details
	print '<tr>'. "\n";

	print "	<td><a href='".create_link("tools","devices","hosts",$device['id'])."'>". $device['hostname'] .'</a></td>'. "\n";
	print "	<td>". $device['ip_addr'] .'</td>'. "\n";
	print '	<td class="description">'. $device['description'] .'</td>'. "\n";
	print '	<td><strong>'. $cnt .'</strong> '._('Hosts').'</td>'. "\n";
	print '	<td class="hidden-sm">'. $device_types_indexed[$device['type']]->tname .'</td>'. "\n";
	print '	<td class="hidden-sm hidden-xs">'. $device['vendor'] .'</td>'. "\n";
	print '	<td class="hidden-sm hidden-xs">'. $device['model'] .'</td>'. "\n";

	//custom
	if(sizeof(@$custom) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<td class='hidden-sm hidden-xs hidden-md'>".$device[$field['name']]."</td>";
			}
		}
	}

	print '	<td class="actions"><a href="'.create_link("tools","devices","hosts",$device['id']).'" class="btn btn-sm btn-default"><i class="fa fa-angle-right"></i> '._('Show all hosts').'</a></td>';
	print '</tr>'. "\n";

	}

	# print for unspecified
	print '<tr class="unspecified">'. "\n";

	//$cnt = countIPaddressesBySwitchId(NULL);
	$cnt = $Tools->count_device_addresses(0);


	print '	<td>'._('Device not specified').'</td>'. "\n";
	print '	<td></td>'. "\n";
	print '	<td></td>'. "\n";
	print '	<td><strong>'. $cnt .'</strong> '._('Hosts').'</td>'. "\n";
	print '	<td class="hidden-sm"></td>'. "\n";
	print '	<td class="hidden-sm hidden-xs"></td>'. "\n";
	print '	<td class="hidden-sm hidden-xs"></td>'. "\n";

	//custom
	if(sizeof(@$custom) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<td class='hidden-sm hidden-xs hidden-md'></td>";
			}
		}
	}
	print '	<td class="actions"><a href="'.create_link("tools","devices","hosts","0").'" class="btn btn-sm btn-default"><i class="fa fa-angle-right"></i> '._('Show all hosts').'</a></td>';
	print '</tr>'. "\n";
}

print '</table>';
?>