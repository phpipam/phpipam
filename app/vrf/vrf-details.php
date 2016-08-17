<?php
/**
 * Display VRF details
 ***********************/

# verify that user is logged in
$User->check_user_session();

# get VLAN details
$vrf = $Tools->fetch_object ("vrf", "vrfId", $_GET['subnetId']);

# not existing
if(!$vrf) { $Result->show("danger", _('Invalid VRF id'), true); }

# get custom VLAN fields
$cfields = $Tools->fetch_custom_fields ('vrf');
?>

<!-- for adding IP address! -->
<div id="subnetId" style="display:none"><?php print $subnetId; ?></div>

<!-- subnet details upper table -->
<h4><?php print _('VRF details'); ?></h4>
<hr>

<table class="ipaddress_subnet table-condensed table-full">
	<tr>
		<th><?php print _('RD'); ?></th>
		<td><?php print $vrf->rd; ?></td>
	</tr>
	<tr>
		<th><?php print _('Name'); ?></th>
		<td>
			<?php print $vrf->name; ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Description'); ?></th>
		<td><?php print $vrf->description; ?></td>
	</tr>

	<tr>
        <td><hr></td>
        <td></td>
	</tr>
	<tr>
		<th><?php print _('Sections'); ?></th>
		<td>
        <div class="text-muted">
        <?php
        	// format sections
        	if(strlen($vrf->sections)==0) {
        		$sections = "All sections";
        	}
        	else {
        		//explode
        		$sections_tmp = explode(";", $vrf->sections);
        		foreach($sections_tmp as $t) {
        			//fetch section
        			$tmp_section = $Sections->fetch_section(null, $t);
        			$sec[] = $tmp_section->name;
        		}
        		//implode
        		$sections = implode("<br>", $sec);
        	}
        	print $sections;
        ?>
        </div>
		</td>
	</tr>

	<?php

	# print custom subnet fields if any
	if(sizeof($cfields) > 0) {
		// divider
		print "<tr><td><hr></td><td></td></tr>";
		// fields
		foreach($cfields as $key=>$field) {
			$vrf->{$key} = str_replace("\n", "<br>",$vrf->{$key});
			// create links
			$vrf->{$key} = $Result->create_links($vrf->{$key});
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td style='vertical-align:top;align:left;'>".$vrf->{$key}."</td>";
			print "</tr>";
		}
		// divider
		print "<tr><td><hr></td><td></td></tr>";
	}

	# action button groups
	print "<tr>";
	print "	<th style='vertical-align:bottom;align:left;'>"._('Actions')."</th>";
	print "	<td style='vertical-align:bottom;align:left;'>";

	print "	<div class='btn-toolbar' style='margin-bottom:0px'>";
	print "	<div class='btn-group'>";

	# permissions
	if($User->is_admin (false)) {
		print "		<button class='btn btn-xs btn-default vrfManagement' data-action='edit'   data-vrfId='$vrf->vrfId'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default vrfManagement' data-action='delete' data-vrfId='$vrf->vrfId'><i class='fa fa-times'></i></button>";
	}

	print "	</div>";
	print "	</div>";

	print "	</td>";
	print "</tr>";
	?>

</table>	<!-- end subnet table -->
<br>