<?php
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

/**
 * Print dropdown menu for rack selection of device
 *
 * We need following inputs from $POST:
 *  - rackid 		(id of rack)
 *  - device_id 	(id of device)
 *
 */

# show only for numeric (set) rackid
if($POST->rackid>0 || @$device['rack']>0) {
	# load objects for ajax-loaded stuff
	if(!isset($User) || !is_object($User)) {
		# initialize user object
		$Database 	= new Database_PDO;
		$User 		= new User ($Database);
		$Racks 		= new phpipam_rack ($Database);
		$Result 	= new Result ();

		# verify that user is logged in
		$User->check_user_session();

		# validate in inputs
		if(!is_numeric($POST->rackid)) 	{ print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid ID"), false, false, true)."</td></tr>"; die(); }
		# fetch rack
		$rack = $User->fetch_object ("racks", "id", $POST->rackid);
		if($rack===false) 					{ print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid rack"), false, false, true)."</td></tr>"; die(); }

		if (isset($POST->deviceid)) {
			if(!is_numeric($POST->deviceid)) { print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid ID"), false, false, true)."</td></tr>"; die(); }
			# fetch device
			$device = $User->fetch_object ("devices", "id", $POST->deviceid);
			if($device===false) 				{ print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid device"), false, false, true)."</td></tr>"; die(); }
			$device = (array) $device;
		} else {
			$device = [];
		}
	}
	# fetch rack details if set on edit
	else {

		if (@$device['rack']>0) {
			$rack = $User->fetch_object ("racks", "id", $device['rack']);
		}
	}

	# check permissions
	$User->check_module_permissions ("racks", User::ACCESS_R, true, false);

	# rack devices
	$rack_devices = $Racks->fetch_rack_devices($rack->id);
	$rack_contents = $Racks->fetch_rack_contents($rack->id);

	// available spaces
	list($available, $available_back) = $Racks->free_u($rack, $rack_devices, $rack_contents, $device);
	?>

	<tr>
		<td></td>
		<td>
			<a class="showRackPopup btn btn-xs btn-default" rel='tooltip' data-placement='right' data-rackid="<?php print @$rack->id; ?>" data-deviceid='<?php print @$device['id']; ?>' title='<?php print _("Show rack"); ?>'><i class='fa fa-server'></i></a>
		</td>
	</tr>

	<tr>
	    <td><?php print _('Start position'); ?></td>
	    <td>
			<select name="rack_start" class="form-control input-sm input-w-auto">
			<?php
			// print available spaces
			if($rack->hasBack!="0") {
			    print "<optgroup label='"._("Front")."'>";
			    foreach ($available as $a) {
			    	$selected = $a==$device['rack_start'] ? "selected" : "";
			        print "<option value='$a' $selected $disabled>$a</option>";
			    }
			    print "</optgroup>";

			    print "<optgroup label='"._("Back")."'>";
			    foreach ($available_back as $k=>$a) {
			    	$selected = $k==$device['rack_start'] ? "selected" : "";
			        print "<option value='$k' $selected>$a</option>";
			    }
			    print "</optgroup>";
			}
			else {
			    foreach ($available as $a) {
                    		$selected = $a==@$device['rack_start'] ? "selected" : "";
			        print "<option value='$a' $selected>$a</option>";
			    }
			}
			?>
			</select>
	    </td>
	</tr>
	<tr>
	    <td><?php print _('Size'); ?> (U)</td>
	    <td>
	        <input type="text" name="rack_size" size="2" class="form-control input-w-auto input-sm" style="width:100px;" placeholder="1" value="<?php print @$device['rack_size']; ?>">
	    </td>
	</tr>
<?php
}
# set hidden values
else {
	print "<input type='hidden' name='rack_start' value='0'>";
	print "<input type='hidden' name='rack_size' value='0'>";
}
