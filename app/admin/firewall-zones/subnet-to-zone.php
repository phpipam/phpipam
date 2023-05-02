<?php

/**
 *	subnet-to-zone.php
 *	add subnet (from detail view) to existing firewall zone
 *************************************************************/

# functions
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize classes
$Database = new Database_PDO;
$Admin 	  = new Admin ($Database);
$User 	  = new User ($Database);
$Result   = new Result ();
$Zones    = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate $_POST['operation'] values
if ($_POST['operation'] != 'subnet2zone') 				{ $Result->show("danger", _("Invalid operation. Do not manipulate the POST values!"), true); }

# validate $_POST['subnetId'] values
if (!preg_match('/^[0-9]+$/i', $_POST['subnetId'])) 	{ $Result->show("danger", _("Invalid subnet ID. Do not manipulate the POST values!"), true); }

//$firewallZones = $Admin->fetch_multiple_objects("firewallZoneMapping","deviceId",$_POST['vr'],"alias");
$fwtype=$Admin->fetch_object("deviceTypes","tname","fwl");
$fwinfo=$Database->getObjectsQuery('SELECT * from devices where location='. $_POST[loc].' and type='.$fwtype->tid);
$fwvsysinfo=$Admin->fetch_multiple_objects("fwvsys","firewall",$_POST['fw'],"name");
$fwvrinfo=$Admin->fetch_multiple_objects("fwvrs","vfw",$_POST['vsys'],"name");

# no zones
if(!is_array($firewallZones) && isset($_POST['vr']))                              { $Result->show("danger", _("No zones available"), true, true); }
?>

<!-- header  -->
<div class="pHeader"><?php print _('Add this subnet to a firewall zone'); ?></div>
<!-- content -->
<div class="pContent">
<!-- form -->
<form id="subnet-to-zone-edit">
<input type="hidden" name="subnetId" value="<?php print escape_input($_POST['subnetId']); ?>">

<!-- table -->
<table class="table table-noborder table-condensed">
	<!-- firewall -->
	<tr>
		<td style="width:150px;">
			<?php print _('Firewall'); ?>
		</td>
		<td>
			<select name="fw" id="fwzsubmap" class="form-control input-sm input-w-auto input-max-200">
			<option disabled selected value="0"><?php print _('Select a FW'); ?></option>
			<?php
				foreach ($fwinfo as $fw) {
					$selected=($fw->id==$_POST['fw'] ? "selected" : "");
					print '<option '. $selected .' value="'.$fw->id.'">'.$fw->hostname.'</option>';
				}
			?>
			</select>
		</td>
	</tr>

	<!-- vsys -->
	<tr>
		<td style="width:150px;">
			<?php print _('vSys'); ?>
		</td>
		<td>
			<select name="vsys" id="fwzsubmap" class="form-control input-sm input-w-auto input-max-200">
			<option disabled selected value="0"><?php print _('Select a vSys'); ?></option>
			<?php
				foreach ($fwvsysinfo as $fw) {
					$vsysinfo=$Admin->fetch_object("vsysnames","id",$fw->name);
					$selected=($fw->id==$_POST['vsys'] ? "selected" : "");
					print '<option '. $selected .' value="'.$fw->id.'">'.$vsysinfo->name.'</option>';
				}
			?>
			</select>
		</td>
	</tr>

	<!-- virtual router -->
	<tr>
		<td style="width:150px;">
			<?php print _('FVirtual Router'); ?>
		</td>
		<td>
			<select name="vr" id="fwzsubmap" class="form-control input-sm input-w-auto input-max-200">
			<option disabled selected value="0"><?php print _('Select a Virtual Router'); ?></option>
			<?php
				foreach ($fwvrinfo as $fw) {
					$selected=($fw->id==$_POST['vr'] ? "selected" : "");
					print '<option '. $selected .' value="'.$fw->id.'">'.$fw->name.'</option>';
				}
			?>
			</select>
		</td>
	</tr>

	<!-- zone -->
	<tr>
		<td style="width:150px;">
			<?php print _('Zone name'); ?>
		</td>
		<td>
			<select name="zoneId" class="form-control input-sm input-w-auto input-max-200 checkMapping">
			<option value="0"><?php print _('Select a Zone'); ?></option>
			<?php
				foreach ($firewallZones as $firewallZone) {
					print '<option value="'.$firewallZone->id.'">'.$firewallZone->alias.'</option>';
				}
			?>
			</select>
		</td>
	</tr>
</table>
<div class="mappingAdd"></div>
</div>
</form>
</div>
<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="subnet-to-zone-submit"><i class="fa fa-plus"></i> <?php print _('Add this subnet to a firewall zone'); ?></button>
	</div>
	<!-- result -->
	<div class="subnet-to-zone-result"></div>
</div>