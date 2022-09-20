<?php

/**
 *	firewall zone settings.php
 *	modify firewall zone module settings like zone indicator, max. chars, ...
 *******************************************************************************/

# validate session parameters
$User->check_user_session();

# default settings for firewall zones and firewall address objects: (JSON)
# {
#	/* zoneLength defines the maximum padding length of the unique generated or free text zone name */
#	"zoneLength":"3",
#	/* ipType is used to indicate IPv4 and IPv6 address objects (the address object name will be generated as an additional information for ip addresses) */
#	"ipType":{
#		"0":"4",
#		"1":"6"
#		},
# 	/* standard separator used to keep address objects tid
#	"separator":"_",
#	/* indicator: Zone type is own zone or customer zone. */
#	"indicator":{
#		"0":"0",
#		"1":"1"
#		},
#	/* to automaticaly generate firewall zone names you may choose between "decimal" and "hex" (see "zoneGeneratorType" below). to define free text zone names choose "text" */
#	"zoneGenerator":"0",
#	"zoneGeneratorType":{
#		"0":"decimal",
#		"1":"hex",
#		"2":"text"
#		},
#	/* strictMode is only used to be sure not to have duplicate zone names of the type "text" */
#	"strictMode":"on",
# 	/* device type ID for firewall devices, default: 3 */
#	"deviceType":"3",
#	/* default value for firewall address object name pattern */
#	"pattern":{"0":"patternFQDN"}
#	/* Adds some padding to the zone name (decimal or hex) to generate zone names of equal length */
#	"padding":"on"
#	/* use the network and subnetmask or the network description to genereate firewall address objects for subnets */
# 	"subnetPatternValues":{"0":"network","1":"description"}
# 	"subnetPattern":"0"
# }

# initialize classes
$Database = new Database_PDO;
$Tools = new Tools($Database);

# fetch module settings
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);

# check if subnetPatternValues are already available, if not set them
if (!$firewallZoneSettings['subnetPatternValues']) {
	$firewallZoneSettings['subnetPatternValues'][0] = 'network';
	$firewallZoneSettings['subnetPatternValues'][1] = 'description';
}

# fetch device types and rekey
$types = $Tools->fetch_all_objects("deviceTypes", "tid");
foreach($types as $t) {
	$deviceTypes[$t->tid] = $t;
}

# build the array for name pattern
$namePattern = array (	'patternIndicator' 	=> '<span class="label label-default" style="margin-right:5px;"><input type="hidden" value="patternIndicator">Indicator</span>',
						'patternZoneName' 	=> '<span class="label label-default" style="margin-right:5px;"><input type="hidden" value="patternZoneName">Zone name</span>',
						'patternIPType' 	=> '<span class="label label-default" style="margin-right:5px;"><input type="hidden" value="patternIPType">IP Type</span>',
						'patternHost' 		=> '<span class="label label-default" style="margin-right:5px;"><input type="hidden" value="patternHost">Host</span>',
						'patternFQDN' 		=> '<span class="label label-default" style="margin-right:5px;"><input type="hidden" value="patternFQDN">FQDN</span>',
						'patternSeparator' 	=> '<span class="label label-default" style="margin-right:5px;"><input type="hidden" value="patternSeparator">'.$firewallZoneSettings['separator'].'</span>');
?>

<script type="text/javascript">
$(document).ready(function() {
	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    size: "mini"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});

$(function() {
	$('div#availableItems').sortable({
	  connectWith: "div",
	  receive: function (e, ui) {
	  	$(ui.item.children('input')).attr('name','');
	  }
	});

	$('div#itemList').sortable({
	  connectWith: "div",
	  receive: function (e, ui) {
	  	$(ui.item.children('input')).attr('name','pattern[]');
	  }
	});

	$('div#itemList input').attr('name','pattern[]');
	$('#availableItems,#itemList').disableSelection();
});
</script>

<style>
.label-default { cursor: pointer; }
</style>

<!-- database settings -->
<form name="firewallZoneSettings" id="firewallZoneSettings">
<table id="settings" class="table table-hover table-condensed table-auto">
<!-- zone settings -->
	<!-- zoneLength -->
	<tr>
		<td colspan="3" style="padding-top:25px;">
			<strong><?php print _('Zone settings'); ?></strong>
			<hr>
		</td>
	</tr>
	<tr>
		<td><?php print _('Maximum zone name length'); ?></td>
		<td style="width:120px;">
			<input type="text" class="form-control input-sm" name="zoneLength" value="<?php print $firewallZoneSettings['zoneLength']; ?>">
		</td>
		<td>
			<span class="text-muted"><?php print _("Choose a maximum lenght of the zone name.<br>The default: 3, maximum: 31 characters.<br>(keep in mind that your firewall may have a limit for the length of zone names or address objects )"); ?></span>
		</td>
	</tr>

	<!-- zoneGenerator -->
	<tr>
		<td><?php print _('Zone generator method'); ?></td>
		<td>
			<select name="zoneGenerator" class="form-control input-w-auto input-sm" style="width:110px;">
				<?php foreach ($firewallZoneSettings['zoneGeneratorType'] as $key => $generator) {
					if ($firewallZoneSettings['zoneGenerator'] == $key) {
						print '<option value='.$key.' selected>'.$generator.'</option>';
					} else {
						print '<option value='.$key.'>'.$generator.'</option>';
					}
				}?>
			</select>
		</td>
		<td>
			<span class="text-muted"><?php print _("Generate zone names automaticaly with the setting &quot;decimal&quot; or &quot;hex&quot;.<br>The maximum value for a zone in hex mode would be ffffffff (4294967295 zones).<br>To use your own unique zone names you can choose the option &quot;text&quot."); ?></span>
		</td>
	</tr>
	<!-- zone name padding / zero fill -->
	<tr>
		<td><?php print _('Zone name padding'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" name="padding" value="on" <?php if($firewallZoneSettings['padding'] == 'on'){ print 'value="'.$firewallZoneSettings['padding'].'" checked';} ?>>
		</td>
		<td>
			<span class="text-muted"><?php print _("Insert leading zeros into the zone name if you want to have a constant length of your zone name.<br>This setting will be ignored if you use the \"text\" zone name generator."); ?></span>
		</td>
	</tr>
	<!-- strict mode -->
	<tr>
		<td><?php print _('Zone name strict mode'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" name="strictMode" value="on" <?php if($firewallZoneSettings['strictMode'] == 'on'){ print 'value="'.$firewallZoneSettings['strictMode'].'" checked';} ?>>
		</td>
		<td>
			<span class="text-muted"><?php print _("Zone name strict mode is enabled by default.<br>If you like to use your own zone names with the &quot;text&quot; mode you may uncheck this to have not unique zone names."); ?></span>
		</td>
	</tr>
	<!-- device type -->
	<tr>
		<td><?php print _('Firewall device Type'); ?></td>
		<td>
			<select name="deviceType" class="form-control input-w-auto input-sm" style="width:110px;">
				<?php foreach ($deviceTypes as $deviceType) {
					if ($firewallZoneSettings['deviceType'] == $deviceType->tid) {
						print '<option value='.$deviceType->tid.' selected>'.$deviceType->tname.'</option>';
					} else {
						print '<option value='.$deviceType->tid.'>'.$deviceType->tname.'</option>';
					}
				}?>
			</select>
		</td>
		<td>
			<span class="text-muted"><?php print _("Select the appropriate device type to match firewall devices."); ?></span>
		</td>
	</tr>
	<!-- address object settings -->
	<tr>
		<td colspan="3" style="padding-top:25px;">
			<strong><?php print _('Firewall address object settings'); ?></strong>
			<hr>
		</td>
	</tr>
	<!-- enable or disable auto generated address objects -->
	<tr>
		<td><?php print _('Autogenerate address objects'); ?></td>
		<td>
			<input type="checkbox" class="input-switch" name="autogen" value="on" <?php if($firewallZoneSettings['autogen'] == 'on'){ print 'value="'.$firewallZoneSettings['autogen'].'" checked';} ?>>
		</td>
		<td>
			<span class="text-muted"><?php print _("Automaticaly generate firewall address objects as an additional information of an IP address.<br>(Works only for subnets which are bound to a firewall zone.)"); ?></span>
		</td>
	</tr>
	<!-- ipType -->
	<tr>
		<td><?php print _('IPv4 address type alias'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="ipType[0]" value="<?php print $firewallZoneSettings['ipType']['0']; ?>">
		</td>
		<td rowspan="2">
			<span class="text-muted"><?php print _("Address type aliases are used to indicate a IPv4 or IPv6 address object."); ?></span>
		</td>
	</tr>
	<tr>
		<td><?php print _('IPv6 address type alias'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="ipType[1]" value="<?php print $firewallZoneSettings['ipType']['1']; ?>">
		</td>
	</tr>
	<!-- separator -->
	<tr>
		<td><?php print _('Separator'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="separator" value="<?php print $firewallZoneSettings['separator']; ?>">
		</td>
		<td>
			<span class="text-muted"><?php print _("The separator is used to keep the name of address objects tidy."); ?></span>
		</td>
	</tr>
	<!-- indicator -->
	<tr>
		<td><?php print _('Own zone indicator'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="indicator[0]" value="<?php print $firewallZoneSettings['indicator']['0']; ?>">
		</td>
		<td rowspan="2">
			<span class="text-muted"><?php print _("The indicator is used to indicate a zone wether is owned by the company or by a customer.<br>It is the leading character of the zone name but will be separated from the zone name in the database."); ?></span>
		</td>
	</tr>
	<tr>
		<td><?php print _('Customer zone indicator'); ?></td>
		<td>
			<input type="text" class="form-control input-sm" name="indicator[1]" value="<?php print $firewallZoneSettings['indicator']['1']; ?>">
		</td>
	</tr>
	<!-- address object pattern -->
	<tr>
		<td>
		</td>
		<td colspan="2" style="padding-top:15px;">
			<span class="text-muted"><?php print _('To organize the order of the firewall IP addres object name pattern simply drag and drop the different parts in the lower field.<br>It\'s also possible to reorganize them to fit your needs.'); ?></span>
		</td>
	</tr>
	<tr>
		<td>
			<?php print _('Available items'); ?>
		</td>
		<td colspan="2">
			<div id="availableItems" class="alert alert-info dropable" style="text-align:center;">
					<?php
					$patternCount = 0;
					foreach ($namePattern as $key => $pattern) {
						if (preg_match('/patternSeparator/i',$settingsPattern)) {
							$patternCount++;

						} elseif (!$firewallZoneSettings['pattern']) {
							print $pattern;
						} elseif (!in_array($key,$firewallZoneSettings['pattern'])) {
							print $pattern;
						}
					}
					while ( $patternCount <= 4 ) {
						print $namePattern['patternSeparator'];
						$patternCount++;
					}
					?>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<?php print _('Name pattern'); ?>
		</td>
		<td colspan="2">
			<div id="itemList" class="alert alert-info dropable" style="text-align:center;">
				<?php
				if ($firewallZoneSettings['pattern']) {
					foreach ($firewallZoneSettings['pattern'] as $settingsPattern) {
						foreach ($namePattern as $key => $pattern) {
							if ($settingsPattern == $key) {
								print $pattern;
							}
						}
					}
				}
				?>
			</div>
		</td>
		</td>
	</tr>
	<!-- subnet pattern type -->
	<tr>
		<td><?php print _('Subnet name'); ?></td>
		<td>
			<select name="subnetPattern" class="form-control input-w-auto input-sm" style="width:110px;">
				<?php foreach ($firewallZoneSettings['subnetPatternValues'] as $key => $subnetObjectPattern) {
					if ($firewallZoneSettings['subnetPattern'] == $key) {
						print '<option value='.$key.' selected>'.$subnetObjectPattern.'</option>';
					} else {
						print '<option value='.$key.'>'.$subnetObjectPattern.'</option>';
					}
				}?>
			</select>
		</td>
		<td>
			<span class="text-muted"><?php print _("Firewall address objects for subnets will contain this information instead of FQDN or hostname.<br>By choosing \"description\" spaces and other characters but hiven or underline will be replaced by the separator value."); ?></span>
		</td>
	</tr>
	<!-- submit -->
	<tr>
		<td colspan="3"><hr></td>
	</tr>
	<tr>
		<td>
			<?php
			foreach ($firewallZoneSettings['zoneGeneratorType'] as $key => $value) {
				print '<input type="hidden" name="zoneGeneratorType['.$key.']" value="'.$value.'">';
			}
			foreach ($firewallZoneSettings['subnetPatternValues'] as $key => $value) {
				print '<input type="hidden" name="subnetPatternValues['.$key.']" value="'.$value.'">';
			} ?>
		</td>
		<td style="text-align: right">
			<input type="submit" class="btn btn-default btn-sm" value="<?php print _("Save"); ?>">
		</td>
	</tr>

</table>
</form>

<!-- save holder -->
<div class="settingsEdit"></div>
