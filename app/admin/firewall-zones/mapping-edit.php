<?php
/**
 * firewall zone mapping-edit.php
 * add, edit and delete firewall zones mappings
 **************************************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Subnets  = new Subnets ($Database);
$Tools 	  = new Tools ($Database);
$Result   = new Result ();
$Zones 	  = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate $_POST['id'] values
if (!preg_match('/^[0-9]+$/i', $_POST['id'])) {
	$Result->show("danger", _("Invalid ID. Do not manipulate the POST values!"), true);
}

# validate $_POST['action'] values
if ($_POST['action'] != 'add' && $_POST['action'] != 'edit' && $_POST['action'] != 'delete') {
	$Result->show("danger", _("Invalid action. Do not manipulate the POST values!"), true);
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "disabled" : "";

# fetch all firewall zones
$firewallZones = $Zones->get_zones();
if (!is_array($firewallZones)) { $firewallZones = array(); }

# fetch settings
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);

# fetch all devices
$devices = $Tools->fetch_multiple_objects ("devices", "type", $firewallZoneSettings['deviceType']);

# fetch old mapping
if ($_POST['action'] != 'add') {
	$mapping = $Zones->get_zone_mapping($_POST['id']);
}
?>
<!-- header  -->
<div class="pHeader"><?php print _('Add a mapping between a firewall device and a firewall zone'); ?></div>
<!-- content -->
<div class="pContent">
	<!-- form -->
	<form id="mappingEdit">

	<!-- table -->
	<table class="table table-noborder table-condensed">
	<!-- zone name -->
	<tr>
		<td style="width:150px;">
			<?php print _('Zone to map'); ?>
		</td>
		<td>
			<select name="zoneId" class="mappingZoneInformation form-control input-sm input-w-auto input-max-200"  <?php print $readonly; ?>>
			<option value="0"><?php print _('Select a firewall zone'); ?></option>
			<?php
			foreach ($firewallZones as $zone) {
				if ($zone->id == $mapping->id) 	{
					if($zone->description) 	{ print '<option value="'.$zone->id.'" selected>'.$zone->zone.' ('.$zone->description.')</option>'; }
					else 					{ print '<option value="'.$zone->id.'" selected>'.$zone->zone.'</option>'; }}
				else {
					if($zone->description) 	{ print '<option value="'.$zone->id.'">'.		  $zone->zone.' ('.$zone->description.')</option>'; }
					else 					{ print '<option value="'.$zone->id.'">'.		  $zone->zone.'</option>'; }}
			}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<!-- spacer -->
		</td>
		<td>
			<div class="zoneInformation">
				<?php
				if ($mapping->zoneId) {
					# return the zone details
					$Zones->get_zone_detail($mapping->id);
				}
				?>
			</div>
		</td>
	</tr>
	<tr>
		<!-- zone indicator -->
		<td>
			<?php print _('Firewall to map'); ?>
		</td>
		<td>
			<select name="deviceId" class="form-control input-sm input-w-auto input-max-200" <?php print $readonly; ?>>
			<option value="0"><?php print _('Select firewall'); ?></option>
			<?php
            if ($devices !==false) {
    			foreach ($devices as $device) {
    				if ($device->id == $mapping->deviceId) 	{
    					if($device->description) 	{	print '<option value="'.$device->id.'" selected>'.	$device->hostname.' ('.$device->description.')</option>'; }
    					else 						{ 	print '<option value="'.$device->id.'" selected>'.	$device->hostname.'</option>'; }}
    				else {
    					if($device->description)	{	print '<option value="'.$device->id.'">'.			$device->hostname.' ('.$device->description.')</option>'; }
    					else 						{	print '<option value="'.$device->id.'">'.			$device->hostname.'</option>'; }}
    			}
			}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?php print _('Interface'); ?>
		</td>
		<td>
			<input type="text" class="form-control input-sm" name="interface" placeholder="<?php print _('Firewall interface'); ?>" value="<?php print $mapping->interface; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<tr>
		<!-- description -->
		<td>
			<?php print _('Zone alias'); ?>
		</td>
		<td>
			<input type="text" class="form-control input-sm" name="alias" placeholder="<?php print _('Local zone alias'); ?>" value="<?php print $mapping->alias; ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	</table>
	<!-- transmit the action and firewall zone id -->
	<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
	<input type="hidden" name="id" value="<?php print $_POST['id']; ?>">
	</form>

	<?php
	# print delete warning
	if($_POST['action'] == "delete"){
		$Result->show("warning", "<strong>"._('Warning').":</strong> "._("You are about to remove the firewall to zone mapping!"), false);
	}
	?>
</div>
<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editMappingSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="mapping-edit-result"></div>
</div>