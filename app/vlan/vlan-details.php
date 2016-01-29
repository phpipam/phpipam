<?php
/**
 * Display VLAN details
 ***********************************************************************/

# verify that user is logged in
$User->check_user_session();

# get VLAN details
$vlan = $Tools->fetch_object("vlans", "vlanId", $_GET['subnetId']);
$vlan = (array) $vlan;

# not existing
if(!$vlan) { $Result->show("danger", _('Invalid VLAN id'),true); }

# get custom VLAN fields
$cfields = $Tools->fetch_custom_fields ('vlans');
?>

<!-- content print! -->
<h4><?php print _('VLAN details'); ?></h4>
<hr>

<table class="ipaddress_subnet table-condensed table-full">
	<tr>
		<th><?php print _('Number'); ?></th>
		<td><?php print '<b>'. $vlan['number']; ?></td>
	</tr>
	<tr>
		<th><?php print _('Name'); ?></th>
		<td>
			<?php print $vlan['name']; ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Domain'); ?></th>
		<td>
        <?php
		// domain
		$l2domain = $Tools->fetch_object("vlanDomains", "id", $vlan['domainId']);
		if($l2domain!==false)       { print $l2domain->name; }
        ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Description'); ?></th>
		<td><?php print $vlan['description']; ?></td>
	</tr>

	<?php
	# print custom subnet fields if any
	if(sizeof($cfields) > 0) {
		// divider
		print "<tr><td><hr></td><td></td></tr>";
		// fields
		foreach($cfields as $key=>$field) {
			$vlan[$key] = str_replace("\n", "<br>",$vlan[$key]);
			// create links
			$vlan[$key] = $Result->create_links($vlan[$key]);
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td style='vertical-align:top;align:left;'>$vlan[$key]</td>";
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
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='edit'   data-vlanid='$vlan[vlanId]'><i class='fa fa-pencil'></i></button>";
		print "		<button class='btn btn-xs btn-default editVLAN' data-action='delete' data-vlanid='$vlan[vlanId]'><i class='fa fa-times'></i></button>";
	}

	print "	</div>";
	print "	</div>";

	print "	</td>";
	print "</tr>";

	?>

</table>	<!-- end subnet table -->
<br>