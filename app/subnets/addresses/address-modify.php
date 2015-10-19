<?php

/**
 * Script to print edit / delete / new IP address
 *
 * Fetches info from database
 *************************************************/


# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);
$Addresses	= new Addresses ($Database);

# verify that user is logged in
$User->check_user_session();

# validate action
$Tools->validate_action ($_POST['action']);

# validate post
is_numeric($_POST['subnetId']) ?:						$Result->show("danger", _("Invalid subnet ID"), true, true);
is_numeric($_POST['id']) || strlen($_POST['id'])==0 ?:	$Result->show("danger", _("Invalid ID"), true, true);

# get posted values
$subnetId= $_POST['subnetId'];
$action  = $_POST['action'];
$id      = $_POST['id'];

# fetch subnet
$subnet = (array) $Subnets->fetch_subnet(null, $subnetId);
if (strpos($_SERVER['HTTP_REFERER'], "verify-database")==0)
sizeof($subnet)>0 ?:			$Result->show("danger", _("Invalid subnet"), true, true);

# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $subnet['id']);
$subnet_permission > 1 ?:		$Result->show("danger", _('Cannot edit IP address details').'! <br>'._('You do not have write access for this network'), true, true);

# set selected address fields array
$selected_ip_fields = $User->settings->IPfilter;
$selected_ip_fields = explode(";", $selected_ip_fields);																			//format to array

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');


# if action is not add then fetch current details, otherwise fetch first available IP address
if ($action == "all-add") {
	$address['ip_addr'] = $Subnets->transform_address($id, "dotted");
}
else if ($action == "add") {
	# get first available IP address
	$first = $Addresses->get_first_available_address ($subnetId, $Subnets);
	$first = !$first ? "" : $Subnets->transform_address($first, "dotted");

	$address['ip_addr'] = $first;
}
else {
	$address = (array) $Addresses->fetch_address(null, $id);
}


# Set action and button text
if ($action == "add") 			{ $btnName = _("add");		$act = "add"; }
else if ($action == "all-add")	{ $btnName = _("add");  	$act = "add"; }
else if ($action == "edit") 	{ $btnName = _("edit"); 	$act = "edit"; }
else if ($action == "all-edit")	{ $btnName = _("edit"); 	$act = "edit"; }
else if ($action == "delete")	{ $btnName = _("delete"); 	$act = "delete"; }
else							{ $btnName = ""; }

# set delete flag
if($act=="delete")	{ $delete = "readonly='readonly'"; }
else				{ $delete = ""; }

?>

<script type="text/javascript">
$(document).ready(function() {
/* bootstrap switch */
var switch_options = {
	onText: "Yes",
	offText: "No",
    onColor: 'default',
    offColor: 'default',
    size: "mini"
};

$(".input-switch").bootstrapSwitch(switch_options);
});
</script>

<!-- header -->
<div class="pHeader"><?php print ucwords($btnName); ?> <?php print _('IP address'); ?></div>

<!-- content -->
<div class="pContent editIPAddress">

	<!-- IP address modify form -->
	<form class="editipaddress" role="form" name="editipaddress">
	<!-- edit IP address table -->
	<table id="editipaddress" class="table table-noborder table-condensed">

	<!-- IP address -->
	<tr>
		<td><?php print _('IP address'); ?> *</td>
		<td>
		<div class="input-group">
			<input type="text" name="ip_addr" class="ip_addr form-control input-sm" value="<?php print $Subnets->transform_address($address['ip_addr'], "dotted");; if(is_numeric($_POST['stopIP'])>0) print "-".$Subnets->transform_address($_POST['stopIP'],"dotted"); ?>" placeholder="<?php print _('IP address'); ?>">
    		<span class="input-group-addon">
    			<i class="fa fa-gray fa-info" rel="tooltip" data-html='true' data-placement="left" title="<?php print _('You can add,edit or delete multiple IP addresses<br>by specifying IP range (e.g. 10.10.0.0-10.10.0.25)'); ?>"></i>
    		</span>
			</div>

   			<input type="hidden" name="action" 	 	value="<?php print $act; 	?>">
			<input type="hidden" name="id" 		 	value="<?php print $id; 		?>">
			<input type="hidden" name="subnet"   	value="<?php print $subnet['ip']."/".$subnet['mask']; 	?>">
			<input type="hidden" name="subnetId" 	value="<?php print $subnetId; 	?>">
			<input type="hidden" name="section" 	value="<?php print $subnet['sectionId']; ?>">
			<input type="hidden" name="ip_addr_old" value="<?php print $address['ip_addr']; ?>">
			<input type="hidden" name="PTR" 		value="<?php print $address['PTR']; ?>">
			<?php
			if (strpos($_SERVER['HTTP_REFERER'], "verify-database")!=0) { print "<input type='hidden' name='verifydatabase' value='yes'>"; }
			?>

			<?php if($action=="edit" || $action=="delete") { ?>
			<input type="hidden" name="nostrict" value="yes">
			<?php }  ?>
    	</td>
	</tr>


	<!-- DNS name -->
	<?php
	if(!isset($address['dns_name'])) {$address['dns_name'] = "";}
		print '<tr>'. "\n";
		print '	<td>'._('Hostname').'</td>'. "\n";
		print '	<td>'. "\n";
		print '	<div class="input-group">';
		print ' <input type="text" name="dns_name" class="ip_addr form-control input-sm" placeholder="'._('Hostname').'" value="'. $address['dns_name']. '" '.$delete.'>'. "\n";
		print '	 <span class="input-group-addon">'."\n";
		print "		<i class='fa fa-gray fa-repeat' id='refreshHostname' data-subnetId='$subnetId' rel='tooltip' data-placement='left' title='"._('Click to check for hostname')."'></i></span>";
		print "	</span>";
		print "	</div>";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	?>

	<!-- description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" name="description" class="ip_addr form-control input-sm" value="<?php if(isset($address['description'])) {print $address['description'];} ?>" size="30"
			<?php if ( $act == "delete" ) { print " readonly";} ?>
			placeholder="<?php print _('Description'); ?>">
		</td>
	</tr>

	<!-- MAC address -->
	<?php
	if(in_array('mac', $selected_ip_fields)) {
		if(!isset($address['mac'])) {$address['mac'] = "";}

		print '<tr>'. "\n";
		print '	<td>'._('MAC address').'</td>'. "\n";
		print '	<td>'. "\n";
		print ' <input type="text" name="mac" class="ip_addr form-control input-sm" placeholder="'._('MAC address').'" value="'. $address['mac']. '" size="30" '.$delete.'>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}
	?>
	<!-- Owner -->
	<?php
	if(in_array('owner', $selected_ip_fields)) {

		if(!isset($address['owner'])) {$address['owner'] = "";}

		print '<tr>'. "\n";
		print '	<td>'._('Owner').'</td>'. "\n";
		print '	<td>'. "\n";
		print ' <input type="text" name="owner" class="ip_addr form-control input-sm" id="owner" placeholder="'._('IP address owner').'" value="'. $address['owner']. '" size="30" '.$delete.'>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}
	?>
	<!-- switch / port -->
	<?php
	if(!isset($address['switch']))  {$address['switch'] = "";}
	if(!isset($address['port'])) 	{$address['port'] = "";}

	# both are active
	if(in_array('switch', $selected_ip_fields)) {
		print '<tr>'. "\n";
		print '	<td>'._('Device').'</td>'. "\n";
		print '	<td>'. "\n";

		print '<select name="switch" class="ip_addr form-control input-sm input-w-auto" '.$delete.'>'. "\n";
		print '<option disabled>'._('Select device').':</option>'. "\n";
		print '<option value="0" selected>'._('None').'</option>'. "\n";
		$devices = $Tools->fetch_devices();

		foreach($devices as $device) {
			$device = (array) $device;
			//check if permitted in this section!
			$sections=explode(";", $device['sections']);
			if(in_array($subnet['sectionId'], $sections)) {
			//if same
			if($device['id'] == $address['switch']) { print '<option value="'. $device['id'] .'" selected>'. $device['hostname'] .'</option>'. "\n"; }
			else 									{ print '<option value="'. $device['id'] .'">'. $device['hostname'] .'</option>'. "\n";			 }
			}
		}
		print '</select>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}
	# Port
	if(in_array('port', $selected_ip_fields)) {

		if(!isset($address['port'])) {$address['port'] = "";}

		print '<tr>'. "\n";
		print '	<td>'._('Port').'</td>'. "\n";
		print '	<td>'. "\n";
		print ' <input type="text" name="port"  class="ip_addr form-control input-sm input-w-150"  id="port"   placeholder="'._('Port').'"   value="'. $address['port']. '" size="30" '.$delete.'>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}
	?>
	<!-- note -->
	<?php
	if(in_array('note', $selected_ip_fields)) {

		if(!isset($address['note'])) {$address['note'] = "";}

		print '<tr>'. "\n";
		print '	<td>'._('Note').'</td>'. "\n";
		print '	<td class="note">'. "\n";
		print ' <textarea name="note" class="ip_addr form-control input-sm" cols="23" rows="2" placeholder="'._('Additional notes about IP address').'" '.$delete.'>'. $address['note'] . '</textarea>'. "\n";
		print '	</td>'. "\n";
		print '</tr>'. "\n";
	}
	?>
	<!-- state -->
	<?php
	# fetch all states
	$ip_types = (array) $Addresses->addresses_types_fetch();
	# default type
	if(!is_numeric(@$address['state'])) 		{ $address['state'] = 2; } // online

	print '<tr>'. "\n";
	print '	<td>'._('Tag').'</td>'. "\n";
	print '	<td>'. "\n";
	print '		<select name="state" '.$delete.' class="ip_addr form-control input-sm input-w-auto">'. "\n";
	# printout
	foreach($ip_types as $k=>$type) {
		if($address['state']==$k)				{ print "<option value='$k' selected>"._($type['type'])."</option>"; }
		else									{ print "<option value='$k'>"._($type['type'])."</option>"; }
	}
	print '		</select>'. "\n";
	print '	</td>'. "\n";
	print '</tr>'. "\n";
	?>
	<!-- exclude Ping -->
	<?php
	if($subnet['pingSubnet']==1) {
		//we can exclude individual IP addresses from ping
		if(@$address['excludePing'] == "1")	{ $checked = "checked='checked'"; }
		else								{ $checked = ""; }

		print '<tr>';
	 	print '<td>'._("Ping exclude").'</td>';
	 	print '<td>';
	 	print "	<div class='checkbox info2'>";
		print ' 	<input type="checkbox" class="ip_addr" name="excludePing" value="1" '.$checked.' '.$delete.'>'. _('Exclude from ping status checks');
		print "	</div>";
	 	print '</td>';
	 	print '</tr>';
	}
	?>

	<!-- set gateway -->
	<tr>
		<td><?php print _("Is gateway"); ?></td>
		<td>
			<input type="checkbox" name="is_gateway" class="input-switch" value="1" <?php if(@$address['is_gateway']==1) print "checked"; ?>>
		</td>
	</tr>
	<?php
	// ignore PTR
	if ($User->settings->enablePowerDNS==1) {
		//we can exclude individual IP addresses from PTR creation
		if(@$address['PTRignore'] == "1")	{ $checked = "checked='checked'"; }
		else								{ $checked = ""; }

		print '<tr>';
	 	print '<td>'._("PTR exclude").'</td>';
	 	print '<td>';
		print ' 	<input type="checkbox" class="ip_addr input-switch" name="PTRignore" value="1" '.$checked.' '.$delete.'> <span class="text-muted">'. _('Dont create PTR records').'</span>';
	 	print '</td>';
	 	print '</tr>';
	}
	?>

	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- Custom fields -->
	<?php
	if(sizeof($custom_fields) > 0) {
		# count datepickers
		$timeP = 0;

		# all my fields
		foreach($custom_fields as $myField) {
			# replace spaces with |
			$myField['nameNew'] = str_replace(" ", "___", $myField['name']);

			# required
			if($myField['Null']=="NO")	{ $required = "*"; }
			else						{ $required = ""; }

			print '<tr>'. "\n";
			print '	<td>'. $myField['name'] .' '.$required.'</td>'. "\n";
			print '	<td>'. "\n";

			//set type
			if(substr($myField['type'], 0,3) == "set") {
				//parse values
				$tmp = explode(",", str_replace(array("set(", ")", "'"), "", $myField['type']));
				//null
				if($myField['Null']!="NO") { array_unshift($tmp, ""); }

				print "<select name='$myField[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$myField[Comment]'>";
				foreach($tmp as $v) {
					if($v==@$address[$myField['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
					else								{ print "<option value='$v'>$v</option>"; }
				}
				print "</select>";
			}
			//date and time picker
			elseif($myField['type'] == "date" || $myField['type'] == "datetime") {
				// just for first
				if($timeP==0) {
					print '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-datetimepicker.min.css">';
					print '<script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>';
					print '<script type="text/javascript">';
					print '$(document).ready(function() {';
					//date only
					print '	$(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
					//date + time
					print '	$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';

					print '})';
					print '</script>';
				}
				$timeP++;

				//set size
				if($myField['type'] == "date")	{ $size = 10; $class='datepicker';		$format = "yyyy-MM-dd"; }
				else							{ $size = 19; $class='datetimepicker';	$format = "yyyy-MM-dd"; }

				//field
				if(!isset($address[$myField['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $myField['nameNew'] .'" maxlength="'.$size.'" '.$delete.' rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. "\n"; }
				else									{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $myField['nameNew'] .'" maxlength="'.$size.'" value="'. $address[$myField['name']]. '" '.$delete.' rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. "\n"; }
			}
			//boolean
			elseif($myField['type'] == "tinyint(1)") {
				print "<select name='$myField[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$myField[Comment]'>";
				$tmp = array(0=>"No",1=>"Yes");
				//null
				if($myField['Null']!="NO") { $tmp[2] = ""; }

				foreach($tmp as $k=>$v) {
					if(strlen(@$address[$myField['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					elseif($k==@$address[$myField['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					else												{ print "<option value='$k'>"._($v)."</option>"; }
				}
				print "</select>";
			}
			//text
			elseif($myField['type'] == "text") {
				print ' <textarea class="form-control input-sm" name="'. $myField['nameNew'] .'" placeholder="'. $myField['name'] .'" '.$delete.' rowspan=3 rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. $address[$myField['name']]. '</textarea>'. "\n";
			}
			//default - input field
			else {
				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $myField['nameNew'] .'" placeholder="'. $myField['name'] .'" value="'. @$address[$myField['name']]. '" size="30" '.$delete.' rel="tooltip" data-placement="right" title="'.$myField['Comment'].'">'. "\n";
			}

			print '	</td>'. "\n";
			print '</tr>'. "\n";
		}
	}
	?>


	 <tr>
		<td colspan="2"><hr></td>
	 </tr>

	 <tr>
	 	<td><?php print _('Unique'); ?></td>
	 	<td>
		<div class='checkbox info2'>
		 	<input type="checkbox" name="unique" value="1" <?php print $delete; ?>><?php print _('Unique hostname'); ?>
		</div>
	 	</td>
	 </tr>

	<?php
	#get type
	 $type = $Addresses->identify_address ($subnet['subnet']);

	 if($subnet['mask'] < 31 && ($action=='add' ||  substr($action, 0,4)=="all-") && $type == "IPv4" ) { ?>
	 <!-- ignore NW /BC checks -->
	 <tr>
		<td><?php print _('Not strict'); ?></td>
		<td>
		<div class='checkbox info2'>
			<input type="checkbox" name="nostrict" value="yes"><?php print _('Permit adding network/broadcast as IP'); ?>
		</div>
		</td>
	</tr>
	<?php } ?>

	<?php
	 if($subnet['mask'] < 127 && $action=='add' && $type == "IPv6" ) { ?>
	 <!-- ignore NW /BC checks -->
	 <tr>
		<td><?php print _('Not strict'); ?></td>
		<td>
		<div class='checkbox info2'>
			<input type="checkbox" name="nostrict" value="yes"><?php print _('Permit adding network/broadcast as IP'); ?>
		</div>
		</td>
	</tr>
	<?php } ?>


</table>	<!-- end edit ip address table -->
</form>		<!-- end IP address edit form -->




</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php
		# add delete if it came from visual edit!
		if($action == 'all-edit') {
		print "<button class='btn btn-sm btn-default btn-danger' id='editIPAddressSubmit' data-action='all-delete'><i class='fa fa-trash-o'></i> "._('Delete IP')."</button>";
		}
		?>
		<button class="btn btn-sm btn-default <?php if($action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editIPAddressSubmit" data-action='<?php print $action; ?>'><i class="fa <?php if($act=="add") { print "fa-plus"; } else if ($act=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords($btnName); ?> IP</button>
	</div>

	<!-- holder for result -->
	<div class="addnew_check"></div>
</div>
