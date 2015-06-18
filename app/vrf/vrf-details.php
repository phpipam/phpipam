<?php
/**
 * Display VRF details
 ***********************/

# verify that user is logged in
$User->check_user_session();

# get VLAN details
$vrf = $Tools->fetch_vrf ($method="vrfId", $_GET['subnetId']);

# not existing
if(!$vrf) { $Result->show("danger", _('Invalid VRF id'), true); }
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

	<?php

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