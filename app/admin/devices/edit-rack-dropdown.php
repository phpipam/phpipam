<?php

/**
 * Print dropdown menu for rack selection of device
 *
 * We need following inputs from $_POST:
 *  - rackid 		(id of rack)
 *  - device_id 	(id of device)
 *
 */

# show only for numeric (set) rackid
if($_POST['rackid']>0 || @$device['rack']>0) {
	# load objects for ajax-loaded stuff
	if(!isset($User) || !is_object($User)) {
		/* functions */
		require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

		# initialize user object
		$Database 	= new Database_PDO;
		$User 		= new User ($Database);
		$Racks 		= new phpipam_rack ($Database);
		$Result 	= new Result ();

		# verify that user is logged in
		$User->check_user_session();

		# validate in inputs
		if(!is_numeric($_POST['rackid'])) 	{ print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid ID"), false, false, true)."</td></tr>"; die(); }
		# fetch rack
		$rack = $User->fetch_object ("racks", "id", $_POST['rackid']);
		if($rack===false) 					{ print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid rack"), false, false, true)."</td></tr>"; die(); }

		if (isset($_POST['deviceid'])) {
			if(!is_numeric($_POST['deviceid'])) { print "<tr><td colspan='2'>".$Result->show ("danger", _("Invalid ID"), false, false, true)."</td></tr>"; die(); }
			# fetch device
			$device = $User->fetch_object ("devices", "id", $_POST['deviceid']);
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
	$User->check_module_permissions ("racks", 1, true, false);

	# rack devices
	$rack_devices = $Racks->fetch_rack_devices($rack->id);
	$rack_contents = $Racks->fetch_rack_contents($rack->id);

	// available spaces
	$available = array();
	$available_back = array ();

	for($m=1; $m<=$rack->size; $m++) {
	    $available[$m] = $m;
	}

	// available back
	if($rack->hasBack!="0") {
		for($m=1; $m<=$rack->size; $m++) {
			$available_back[$m+$rack->size] = $m;
		}
	}

	if($rack_devices!==false) {
	    // remove units used by devices
	    foreach ($rack_devices as $d) {
	        for($m=$d->rack_start; $m<=($d->rack_start+($d->rack_size-1)); $m++) {
		    unset($available[$m]); unset($available_back[$m]);
	        }
	    }
	    // place back current device (if present)
	    if (isset($device['rack_start'])) {
	        for($m=$device['rack_start']; $m<=($device['rack_start']+($device['rack_size']-1)); $m++) {
		   if ($m >= $rack->size) {
		   	$available_back[$m] = $m - $rack->size;
		   } else {
		   	$available[$m] = $m - $rack->size;
		   }
	        }
	    }
	}

	if ($rack_contents !== false) {
	    // remove units used by special rack devices too
	    foreach ($rack_contents as $d) {
	        for($m=$d->rack_start; $m<=($d->rack_start+($d->rack_size-1)); $m++) {
		    unset($available[$m]); unset($available_back[$m]);
		}
	    }
	}
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
                    		$selected = $a==$device['rack_start'] ? "selected" : "";
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