<?php

/**
 * Script to confirm / reject IP address request
 ***********************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "requests");

# fetch request
$request = $Admin->fetch_object("requests", "id", $_POST['requestid']);

//fail
if($request===false) { $Result->show("danger", _("Request does not exist"), true, true); }
else				 { $request = (array) $request; }

# strip
$_POST = $Admin->strip_input_tags($request);

# verify permissions
if($Subnets->check_permission($User->user, $request['subnetId']) != 3)	{ $Result->show("danger", _('You do not have permissions to process this request')."!", true, true); }


# set IP address
# if provided (requested from logged in user) check if already in use, if it is warn and set next free
# else get next free
if(!is_blank($request['ip_addr'])) {
	// check if it exists
	if ( $Addresses->address_exists ($request['ip_addr'], $request['subnetId'])) {
		$errmsg = _("Requested IP address").' '.($request['ip_addr']).' '._("already used. First available address automatically provided.");
		$errmsg_class = "warning";
		//fetch first free
		$ip_address = $Addresses->transform_to_dotted($Addresses->get_first_available_address ($request['subnetId']));
	}
	else {
		$ip_address = $request['ip_addr'];
	}

} else {
	// fetch first free
	$ip_address = $Addresses->transform_to_dotted($Addresses->get_first_available_address ($request['subnetId']));
}

// false
if ($ip_address===false) {
	$ip_address = "";
	$errmsg = _("No IP addresses available in requested subnet.");
	$errmsg_class = "danger";
}


# set selected address fields array
$selected_ip_fields = $Tools->explode_filtered(";", $User->settings->IPfilter);
# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('ipaddresses');
?>

<!-- header -->
<div class="pHeader"><?php print _('Manage IP address request'); ?></div>

<!-- content -->
<div class="pContent">

	<?php
	// if error / warning message provided
	if (isset($errmsg)) {
		$Result->show($errmsg_class, $errmsg, false, false);
		print "<hr>";
	}
	// error check
	if (@$errmsg_class!="danger") {
	?>

	<!-- IP address request form -->
	<form class="manageRequestEdit" name="manageRequestEdit">
	<!-- edit IP address table -->
	<table id="manageRequestEdit" class="table table-noborder table-condensed">

	<!-- divider -->
	<tr>
		<td colspan="2"><h4><?php print _('Request details'); ?></h4><hr></td>
	</tr>

	<!-- Subnet -->
	<tr>
		<th><?php print _('Requested subnet'); ?></th>
		<td>
			<select name="subnetId" id="subnetId" class="form-control input-sm input-w-auto">
			<?php
			$request_subnets = $Admin->fetch_multiple_objects("subnets", "allowRequests", 1);

			foreach($request_subnets as $subnet) {
				$subnet = (array) $subnet;
				# print
				if($request['subnetId']==$subnet['id'])	{ print '<option value="'. $subnet['id'] .'" selected>' . $Addresses->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .' ['. $subnet['description'] .']</option>'; }
				else 									{ print '<option value="'. $subnet['id'] .'">' 			. $Addresses->transform_to_dotted($subnet['subnet']) .'/'. $subnet['mask'] .' ['. $subnet['description'] .']</option>'; }
			}
			?>
			</select>
		</td>
	</tr>
	<!-- IP address -->
	<tr>
		<th><?php print _('IP address'); ?></th>
		<td>
			<input type="text" name="ip_addr" class="ip_addr form-control input-sm" value="<?php print $Tools->strip_xss($ip_address); ?>" size="30">
			<input type="hidden" name="requestId" value="<?php print $request['id']; ?>">
			<input type="hidden" name="requester" value="<?php print $request['requester']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
    	</td>
	</tr>
	<!-- description -->
	<tr>
		<th><?php print _('Description'); ?></th>
		<td>
			<input type="text" name="description" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$request['description']); ?>" size="30" placeholder="<?php print _('Enter IP description'); ?>">
		</td>
	</tr>
	<!-- MAC Address -->
	<tr>
		<th><?php print _('MAC Address'); ?></th>
		<td>
			<input type="text" name="mac" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$request['mac']); ?>" size="30" placeholder="<?php print _('Enter MAC Address'); ?>">
		</td>
	</tr>
	<!-- DNS name -->
	<tr>
		<th><?php print _('Hostname'); ?></th>
		<td>
			<input type="text" name="hostname" class="form-control input-sm" value="<?php print $Tools->strip_xss(@$request['hostname']); ?>" size="30" placeholder="<?php print _('Enter hostname'); ?>">
		</td>
	</tr>

	<?php if(in_array('state', $selected_ip_fields)) { ?>
	<!-- state -->
	<tr>
		<th><?php print _('State'); ?></th>
		<td>
			<select name="state" class="form-control input-sm input-w-auto">
			<?php
			$states = $Addresses->addresses_types_fetch ();
			# default tag
			if (!isset($request['state']))	{ $request['state'] = "2"; }
			foreach($states as $s) {
				if ($request['state']==$s['id'])	{ print "<option value='$s[id]' selected='selected'>$s[type]</option>"; }
				else								{ print "<option value='$s[id]'>$s[type]</option>"; }
			}
			?>
			</select>
		</td>
	</tr>
	<?php } ?>

	<?php if(in_array('owner', $selected_ip_fields)) { ?>
	<!-- owner -->
	<tr>
		<th><?php print _('Owner'); ?></th>
		<td>
			<input type="text" name="owner" class="form-control input-sm" id="owner" value="<?php print $Tools->strip_xss(@$request['owner']); ?>" size="30" placeholder="<?php print _('Enter IP owner'); ?>">
		</td>
	</tr>
	<?php } ?>

	<?php if(in_array('switch', $selected_ip_fields)) { ?>
	<!-- switch / port -->
	<tr>
		<th><?php print _('Device'); ?> / <?php print _('port'); ?></th>
		<td>
			<select name="switch" class="form-control input-sm input-w-100">
				<option disabled><?php print _('Select device'); ?>:</option>
				<option value="" selected><?php print _('None'); ?></option>
				<?php
				$devices = $Tools->fetch_all_objects("devices", "hostname");
				//loop
				if ($devices!==false) {
    				foreach($devices as $device) {
    					//cast
    					$device = (array) $device;

    					if($device['id'] == @$request['switch']) { print '<option value="'. $device['id'] .'" selected>'. $device['hostname'] .'</option>'. "\n"; }
    					else 									 { print '<option value="'. $device['id'] .'">'. 		 $device['hostname'] .'</option>'. "\n"; }
    				}
				}
				?>
			</select>
			<?php if(in_array('port', $selected_ip_fields)) { ?>
			/
			<input type="text" name="port" class="form-control input-sm input-w-100" value="<?php print $Tools->strip_xss(@$request['port']); ?>"  placeholder="<?php print _('Port'); ?>">
		</td>
	</tr>
	<?php } ?>
		</td>
	</tr>
	<?php } ?>

	<?php if(in_array('note', $selected_ip_fields)) { ?>
	<!-- note -->
	<tr>
		<th><?php print _('Note'); ?></th>
		<td>
			<input type="text" name="note" class="form-control input-sm" id="note" placeholder="<?php print _('Write note'); ?>" size="30">
		</td>
	</tr>
	<?php } ?>

	<!-- Custom -->
	<?php
	if(sizeof($custom_fields) > 0) {
		# count datepickers
		$timepicker_index = 0;

		# all my fields
		foreach($custom_fields as $field) {
    		// create input > result is array (required, input(html), timepicker_index)
    		$custom_input = $Tools->create_custom_field_input ($field, $request, $timepicker_index);
    		$timepicker_index = $custom_input['timepicker_index'];
            // print
			print "<tr>";
			print "	<th>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</th>";
			print "	<th>".$custom_input['field']."</th>";
			print "</tr>";
		}
	}
	?>

	<!-- divider -->
	<tr>
		<td colspan="2" style="padding-top:30px;"><h4><?php print _('Additional information'); ?></h4><hr></td>
	</tr>

	<!-- requested by -->
	<tr>
		<th><?php print _('Requester email'); ?></th>
		<td>
			<input type="text" disabled="disabled" class="form-control" value="<?php print $Tools->strip_xss(@$request['requester']); ?>">
		</td>
	</tr>
	<!-- comment -->
	<tr>
		<th><?php print _('Requester comment'); ?></th>
		<td>
			<input type="text" disabled="disabled" class="form-control" value="<?php print $Tools->strip_xss(@$request['comment']); ?>">
			<?php print "<input type='hidden' name='comment' value='".$Tools->strip_xss(@$request['comment'])."'>"; ?></i></td>
	</tr>
	<!-- Admin comment -->
	<tr>
		<th><?php print _('Comment approval/reject'); ?>:</th>
		<td>
			<textarea name="adminComment" rows="3" cols="30" class="form-control input-sm" placeholder="<?php print _('Enter reason for reject/approval to be sent to requester'); ?>"></textarea>
		</td>
	</tr>

	</table>
	</form>
	<?php } ?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if (@$errmsg_class!="danger") { ?>
		<button class="btn btn-sm btn-default btn-danger manageRequest" data-action='reject'><i class="fa fa-times"></i> <?php print _('Reject'); ?></button>
		<button class="btn btn-sm btn-default btn-success manageRequest" data-action='accept'><i class="fa fa-check"></i> <?php print _('Accept'); ?></button>
		<?php } ?>
	</div>

	<!-- result -->
	<div class="manageRequestResult"></div>
</div>
