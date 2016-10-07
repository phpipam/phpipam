<?php

/**
 *	Site settings
 **************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "settings");

# fetch all languages
$languages = $Admin->fetch_all_objects("lang", "l_id");

# set settings
$settings = (array) $User->settings;
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
</script>

<!-- title -->
<h4><?php print _('phpIPAM Server settings'); ?></h4>
<hr>

<form name="settings" id="settings">
<table id="settings" class="table table-hover table-condensed table-top">

<!-- site settings -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Site settings'); ?></h4></th>
</tr>

<!-- site title -->
<tr>
	<td><?php print _('Site title'); ?></th>
	<td>
		<input type="text" class="form-control input-sm" name="siteTitle" value="<?php print $settings['siteTitle']; ?>">
		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	</td>
	<td class="info2"><?php print _('Set site title'); ?></td>
</tr>

<!-- site domain -->
<tr>
	<td><?php print _('Site domain'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="siteDomain" value="<?php print $settings['siteDomain']; ?>">
	</td>
	<td class="info2"><?php print _('Set domain for sending mail notifications'); ?></td>
</tr>

<!-- site URL -->
<tr>
	<td class="title"><?php print _('Site URL'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="siteURL" value="<?php print $settings['siteURL']; ?>">
	</td>
	<td class="info2"><?php print _('Set site URL'); ?></td>
</tr>
<!-- Login header text -->
<tr>
        <td class="title"><?php print _('Login text'); ?></td>
        <td>
                <input type="text" class="form-control input-sm" name="siteLoginText" value="<?php print $settings['siteLoginText']; ?>">
        </td>
        <td class="info2"><?php print _("Show text above 'username' field on login page (default empty)"); ?></td>
</tr>
<!-- prettyLinks -->
<tr>
	<td class="title"><?php print _('Prettify links'); ?></td>
	<td>
		<select name="prettyLinks" class="form-control input-sm input-w-auto">
		<?php
			print "<option value='No'>"._('No')."</option>";
			if($settings['prettyLinks']=="Yes") { print "<option value='Yes' selected='selected'>"._('Yes')."</option>"; }
			else								{ print "<option value='Yes'>"._('Yes')."</option>"; }
		?>
		</select>
	</td>
	<td class="info2">
		<?php print _('Use nicer URL structure'); ?>?<br>
		<ul>
			<li><?php print _("No"); ?>:  ?page=administration&link2=settings</li>
			<li><?php print _("Yes"); ?>: /administration/settings/</li>
		</ul>
		<?php print _("Please note that mod_rewrite is required with appropriate settings if pretified links are selected."); ?><br>
		<a href="http://phpipam.net/prettified-links-with-mod_rewrite/">http://phpipam.net/prettified-links-with-mod_rewrite/</a>
		</td>
</tr>
<!-- Default language -->
<tr>
	<td class="title"><?php print _('Default language'); ?></td>
	<td>
		<select name="defaultLang" class="form-control input-sm input-w-auto">
		<?php
		if(sizeof($languages)>0) {
			//default
			print "<option value='0'>Default</option>";
			foreach($languages as $lang) {
				if($lang->l_id==$settings['defaultLang']) 	{ print "<option value='$lang->l_id' selected='selected'>$lang->l_name ($lang->l_code)</option>"; }
				else										{ print "<option value='$lang->l_id' 					>$lang->l_name ($lang->l_code)</option>"; }
			}
		}
		?>
		</select>
	</td>
	<td class="info2"><?php print _('Select default language'); ?></td>
</tr>

<!-- Mex session duration -->
<tr>
	<td class="title"><?php print _('Inactivity timeout'); ?></td>
	<td>
		<select name="inactivityTimeout" class="form-control input-sm input-w-auto">
		<?php
		$durations = array("900"=>"15 minutes","1800"=>"30 minutes", "3600"=>"1 hour", "7200"=>"2 hours", "21600"=>"6 hours", "43200"=>"12 hours", "86400"=>"24 hours");
		//default
		foreach($durations as $k=>$d) {
			if($k==$settings['inactivityTimeout']) 	{ print "<option value='$k' selected='selected'>$d</option>"; }
			else									{ print "<option value='$k' 				   >$d</option>"; }
		}
		?>
		</select>
	</td>
	<td class="info2"><?php print _('Select inactive timeout for user sessions. Please note that if default php session settings in php.ini are lower they will override this'); ?></td>
</tr>
<!-- Max VLAN number -->
<tr>
	<td class="title"><?php print _('Highest VLAN number'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="vlanMax" value="<?php print $settings['vlanMax']; ?>">
	</td>
	<td class="info2">
		<?php print _('Set highest VLAN number (default 4096)'); ?>
	</td>
</tr>



<!-- Admin settings -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Admin settings'); ?></h4></th>
</tr>

<!-- Admin name -->
<tr>
	<td class="title"><?php print _('Admin name'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="siteAdminName" value="<?php print $settings['siteAdminName']; ?>">
	</td>
	<td class="info2">
		<?php print _('Set administrator name'); ?>
	</td>
</tr>

<!-- Admin mail -->
<tr>
	<td class="title"><?php print _('Admin mail'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="siteAdminMail" value="<?php print $settings['siteAdminMail']; ?>">
	</td>
	<td class="info2">
		<?php print _('Set administrator e-mail'); ?>
	</td>
</tr>



<!-- features -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Feature settings'); ?></h4></th>
</tr>

<!-- API -->
<tr>
	<td class="title"><?php print _('API'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="api" <?php if($settings['api'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable API server module'); ?>
	</td>
</tr>

<!-- IP requests -->
<tr>
	<td class="title"><?php print _('IP request module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableIPrequests" <?php if($settings['enableIPrequests'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable IP request module'); ?>
	</td>
</tr>

<!-- VRF -->
<tr>
	<td class="title"><?php print _('Enable VRF support'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableVRF" <?php if($settings['enableVRF'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable VRF module'); ?>
	</td>
</tr>

<!-- nat -->
<tr>
	<td class="title"><?php print _('Enable NAT'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableNAT" <?php if($settings['enableNAT'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable NAT module'); ?>
	</td>
</tr>

<!-- powerdns -->
<tr>
	<td class="title"><?php print _('Enable PowerDNS'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enablePowerDNS" <?php if($settings['enablePowerDNS'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable PowerDNS module'); ?>
	</td>
</tr>

<!-- dHCP -->
<!--
<tr>
	<td class="title"><?php print _('Enable DHCP'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableDHCP" <?php if($settings['enableDHCP'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable DHCP module'); ?>
	</td>
</tr>
-->

<!-- firewall zone management -->
<tr>
	<td class="title"><?php print _('Enable Firewall Zones'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableFirewallZones" <?php if($settings['enableFirewallZones'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable firewall zone management module'); ?>
	</td>
</tr>

<!-- DNS resolving -->
<tr>
	<td class="title"><?php print _('Resolve DNS names'); ?></td>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="enableDNSresolving" <?php if($settings['enableDNSresolving'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Check reverse dns lookups for IP addresses that do not have hostname in database. (Activating this feature can significantly increase ip address pages loading time!)'); ?>
	</td>
</tr>

<!-- Share -->
<tr>
	<td class="title"><?php print _('Temporary shares'); ?></td>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="tempShare" <?php if($settings['tempShare'] == 0) print ''; else print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Allow temporary subnet sharing'); ?>
	</td>
</tr>

<!-- changelog -->
<tr>
	<td class="title"><?php print _('Changelog'); ?></td>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="enableChangelog" <?php if($settings['enableChangelog'] == 0) print ''; else print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable changelog module'); ?>
	</td>
</tr>

<!-- Multicast -->
<tr>
	<td class="title"><?php print _('Multicast module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableMulticast" <?php if($settings['enableMulticast'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable multicast module'); ?>
	</td>
</tr>

<!-- threshold -->
<tr>
	<td class="title"><?php print _('Threshold module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableThreshold" <?php if($settings['enableThreshold'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable threshold module'); ?>
	</td>
</tr>

<!-- Rack -->
<tr>
	<td class="title"><?php print _('Rack module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableRACK" <?php if($settings['enableRACK'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable rack drawing module'); ?>
	</td>
</tr>

<!-- Locations -->
<tr>
	<td class="title"><?php print _('Locations module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableLocations" <?php if($settings['enableLocations'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable locations module'); ?>
	</td>
</tr>

<!-- SNMP -->
<tr>
	<td class="title"><?php print _('SNMP module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enableSNMP" <?php if($settings['enableSNMP'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable SNMP module for devices'); ?>
	</td>
</tr>


<!-- pstn -->
<tr>
	<td class="title"><?php print _('PSTN module'); ?></td>
	<td>
		<input type="checkbox" class="input-switch" value="1" name="enablePSTN" <?php if($settings['enablePSTN'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Enable or disable PSTN module to manage phone numbers'); ?>
	</td>
</tr>

<!-- Link fields -->
<tr>
	<td class="title"><?php print _('Link addresses'); ?></td>
	<td>
		<select name="link_field" class="form-control input-sm input-w-auto">
		<?php
        # fetch all custom IP fields
        $custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
        $custom_fields2[]['name'] = "None";
        $custom_fields2[]['name'] = "ip_addr";
        $custom_fields2[]['name'] = "dns_name";
        $custom_fields2[]['name'] = "mac";
        $custom_fields2[]['name'] = "owner";
        // merge
        $custom_fields = array_merge($custom_fields2, $custom_fields);

		//default
		foreach($custom_fields as $k=>$d) {
			if($d['name']==$settings['link_field'])     { print "<option value='$d[name]' selected='selected'>$d[name]</option>"; }
			else						                { print "<option value='$d[name]' 				     >$d[name]</option>"; }
		}
		?>
		</select>

	</td>
	<td class="info2">
		<?php print _('Display linked addresses from another subnet if it matches selected field'); ?>
	</td>
</tr>

<!-- Log location -->
<tr>
	<td class="title"><?php print _('Syslog'); ?></td>
	<td>
		<select name="log" class="form-control input-sm input-w-auto">
		<?php
		$types = array("Database"=>"Database", "syslog"=>"Syslog", "both"=>"Syslog and local Database");
		//default
		foreach($types as $k=>$d) {
			if($k==$settings['log']) 	{ print "<option value='$k' selected='selected'>$d</option>"; }
			else						{ print "<option value='$k' 				   >$d</option>"; }
		}
		?>
		</select>

	</td>
	<td class="info2">
		<?php print _('Set where to send system logs'); ?>
	</td>
</tr>



<!-- ICPM -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('ICMP settings'); ?></h4></th>
</tr>

<!-- Scan type -->
<tr>
	<td class="title"><?php print _('Scan type'); ?></td>
	<td>
		<select name="scanPingType" class="form-control input-sm input-w-auto">
		<?php
		$types = array("ping"=>"ping", "pear"=>"pear ping", "fping"=>"fping");
		//default
		foreach($types as $k=>$d) {
			if($k==$settings['scanPingType']) 	{ print "<option value='$k' selected='selected'>$d</option>"; }
			else								{ print "<option value='$k' 				   >$d</option>"; }
		}
		?>
		</select>
	</td>
	<td class="info2"><?php print _('Select which utility to use for status checks.'); ?></td>
</tr>


<!-- Ping path -->
<tr>
	<td class="title"><?php print _('Ping path'); ?></td>
	<?php
	//verify that ping file exists!
	if(!file_exists($settings['scanPingPath'])&&$settings['scanFPingType']=="ping")		{ $class="danger"; }
	else																				{ $class=""; }
	?>
	<td class="<?php print $class; ?>">
		<input type="text" class="form-control input-sm" name="scanPingPath" value="<?php print $settings['scanPingPath']; ?>">
	</td>
	<td class="info2">
		<?php print _('Set path for ping executable file (default /bin/ping)'); ?>
	</td>
</tr>

<!-- fping path -->
<tr>
	<td class="title"><?php print _('FPing path'); ?></td>
	<?php
	//verify that ping file exists!
	if(!file_exists($settings['scanFPingPath'])&&$settings['scanFPingType']=="fping")	{ $class="danger"; }
	else																				{ $class=""; }
	?>
	<td class="<?php print $class; ?>">
		<input type="text" class="form-control input-sm" name="scanFPingPath" value="<?php print $settings['scanFPingPath']; ?>">
	</td>
	<td class="info2">
		<?php print _('Set path for fping executable file (default /bin/fping)'); ?>
	</td>
</tr>

<!-- Ping status intervals -->
<tr>
	<td class="title"><?php print _('Ping status intervals'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="pingStatus" value="<?php print $settings['pingStatus']; ?>">
	</td>
	<td class="info2">
		<?php print _('Ping status intervals for IP addresses in seconds - warning;offline (Default: 1800;3600)'); ?>
	</td>
</tr>

<!-- Ping threads -->
<tr>
	<td class="title"><?php print _('Max scan threads'); ?></td>
	<td>
		<input type="text" class="form-control input-sm" name="scanMaxThreads" value="<?php print $settings['scanMaxThreads']; ?>">
	</td>
	<td class="info2">
		<?php print _('Set maximum number of concurrent ICMP checks (default 128)'); ?>
	</td>
</tr>





<!-- Display -->
<tr class="settings-title">
	<th colspan="3"><h4><?php print _('Display settings'); ?></h4></th>
</tr>

<!-- Disable donation field -->
<tr>
	<td class="title"><?php print _('Hide donation button'); ?></td>
	<td>
		<input type="checkbox" value="1" class="input-switch" name="donate" <?php if($settings['donate'] == 1) print 'checked'; ?>>
	</td>
	<td class="info2">
		<?php print _('Hide donation button'); ?>
	</td>
</tr>

<!-- Visual display limit -->
<tr>
	<td class="title"><?php print _('IP visual display limit'); ?></td>
	<td>
		<select name="visualLimit" class="form-control input-sm input-w-auto">
			<?php
			$opts = array(
				"0"=>_("Don't show visual display"),
				"19"=>"/19 (8190)",
				"20"=>"/20 (4094)",
				"21"=>"/21 (2046)",
				"22"=>"/22 (1024)",
				"23"=>"/23 (512)",
				"24"=>"/24 (256)"
			);

			foreach($opts as $key=>$line) {
				if($settings['visualLimit'] == $key) { print "<option value='$key' selected>$line</option>"; }
				else 								{ print "<option value='$key'>$line</option>"; }
			}

			?>
		</select>
	</td>
	<td class="info2">
		<?php print _('Select netmask limit for visual display of IP addresses (mask equal or bigger than - more then /22 not recommended)'); ?>
	</td>
</tr>

<!-- Subnet ordering -->
<tr>
	<td class="title"><?php print _('Subnet ordering'); ?></td>
	<td>
		<select name="subnetOrdering" class="form-control input-sm input-w-auto">
			<?php
			$opts = array(
				"subnet,asc"		=> _("Subnet, ascending"),
				"subnet,desc"		=> _("Subnet, descending"),
				"description,asc"	=> _("Description, ascending"),
				"description,desc"	=> _("Description, descending"),
			);

			foreach($opts as $key=>$line) {
				if($settings['subnetOrdering'] == $key) { print "<option value='$key' selected>$line</option>"; }
				else 									{ print "<option value='$key'>$line</option>"; }
			}

			?>
		</select>
	</td>
	<td class="info2">
		<?php print _('How to order display of subnets'); ?>
	</td>
</tr>

<!-- Subnet View -->
<tr>
	<td class="title"><?php print _('Subnet Display'); ?></td>
	<td>
		<select name="subnetView" class="form-control input-sm input-w-auto">
			<?php
			$opts = array(
				"0"=>_("Subnet Network Only"),
				"1"=>"Description Only",
				"2"=>"Subnet Network and Description"
			);
			foreach($opts as $key=>$line) {
				if($settings['subnetView'] == $key) { print "<option value='$key' selected>$line</option>"; }
				else 								{ print "<option value='$key'>$line</option>"; }
			}
			?>
		</select>
	</td>
	<td class="info2">
		<?php print _('Select which view you would prefer on the menu'); ?>
	</td>
</tr>

<!-- Logo -->
<tr>
	<td class="title"><?php print _('Upload logo'); ?></td>
	<td>
	    <a class="btn btn-sm btn-default" id="upload-logo"><i class="fa fa-upload"></i> <?php print _("Upload"); ?></a>
	</td>
	<td class="info2">
		<?php print _('Upload custom logo'); ?>
	</td>
</tr>


<!-- result -->
<tr class="th">
	<td colspan="2">
		<div class="settingsEdit"></div>
	</td>
	<td></td>
</tr>

<!-- Submit -->
<tr class="th">
	<td class="title"></td>
	<td class="submit">
		<input type="submit" class="btn btn-sm btn-success pull-right" value="<?php print _('Save changes'); ?>">
	</td>
	<td></td>
</tr>

</table>
</form>
