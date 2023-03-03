<?php
/**
 * Display VLAN details
 ***********************************************************************/

# verify that user is logged in
$User->check_user_session();

# perm check
$User->check_module_permissions ("vlan", User::ACCESS_R, true, false);

# to array
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
			$vlan[$key] = $Tools->create_links($vlan[$key]);
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td style='vertical-align:top;align-content:left;'>$vlan[$key]</td>";
			print "</tr>";
		}
		// divider
		print "<tr><td><hr></td><td></td></tr>";
	}

	# action button groups
	print "<tr>";
    print "<td class='actions'>";
    $links = [];
    if($User->get_module_permissions ("vlan")>=User::ACCESS_RW) {
        $links[] = ["type"=>"header", "text"=>_("Manage")];
        $links[] = ["type"=>"link", "text"=>_("Edit VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit.php' data-action='edit' data-vlanid='$vlan[vlanId]'", "icon"=>"pencil"];
    }
    if($User->get_module_permissions ("vlan")>=User::ACCESS_RWA) {
        $links[] = ["type"=>"link", "text"=>_("Delete VLAN"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vlans/edit.php' data-action='delete' data-vlanid='$vlan[vlanId]'", "icon"=>"times"];
    }
    // print links
    print $User->print_actions($User->user->compress_actions, $links);
    print "</td>";
	print '</tr>'. "\n";
	print "</tr>";

	?>

</table>	<!-- end subnet table -->
<br>