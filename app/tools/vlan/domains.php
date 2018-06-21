<h4><?php print _("L2 domains"); ?></h4>
<hr>

<!-- Manage link -->
<?php if($User->is_admin(false)===true) { ?>
<?php if($_GET['page']=="administration") { ?>
<div class="btn-group" style="margin-bottom:10px;">
	<button class='btn btn-sm btn-default open_popup' data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='add'><i class='fa fa-plus'></i> <?php print _('Add L2 Domain'); ?></button>
	<button class="btn btn-sm btn-default editVLAN" data-action="add" data-domain="all" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add VLAN'); ?></button>

</div>
<?php } else { ?>
	<a class="btn btn-sm btn-default" href="<?php print create_link("administration", "vlans"); ?>" style='margin-bottom:15px;'><i class='fa fa-pencil'></i> <?php print _("Manage"); ?></a>
<?php } ?>
<?php } ?>


<table class="table sorted nosearch nopagination table-striped table-top table-condensed table-auto table-auto-wide" data-cookie-id-table='tools_l2_all'>
<!-- headers -->
<thead>
<tr>
	<th><?php print _('Name'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Sections'); ?></th>
	<th></th>
	<?php if($_GET['page']=="administration") { ?><th></th><?php } ?>
</tr>
</thead>

<tbody>
<!-- all domains -->
<tr>
	<td class='border-bottom'><strong><a href="<?php print create_link($_GET['page'], $_GET['section'], "all"); ?>"> <?php print _('All domains'); ?></a></strong></td>
	<td class='border-bottom'><?php print _('List of all VLANs in all domains'); ?></td>
	<td class='border-bottom'><span class='text-muted'><?php print _('All sections'); ?></span></td>
	<td class='border-bottom'><a class='btn btn-xs btn-default' href='<?php print create_link($_GET['page'], $_GET['section'], "all"); ?>'>Show VLANs</a></td>
	<?php if($_GET['page']=="administration") { ?><td class='border-bottom'></td><?php } ?>
</tr>

<!-- content -->
<?php
foreach($vlan_domains as $domain) {
	// format sections
	if($domain->id==1) {
		$sections = "All sections";
	}
	elseif(strlen(@$domain->permissions==0)) {
		$sections = "None";
	}
	else {
		//explode
		unset($sec);
		$sections_tmp = explode(";", $domain->permissions);
		foreach($sections_tmp as $t) {
			//fetch section
			$tmp_section = $Sections->fetch_section(null, $t);
			$sec[] = " &middot; ".$tmp_section->name;
		}
		//implode
		$sections = implode("<br>", $sec);
	}

	// print
	print "<tr class='text-top'>";
	print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], $_GET['section'], $domain->id)."'><i class='fa fa-cloud prefix'></i> $domain->name</a></strong></td>";
	print "	<td>$domain->description</td>";
	print "	<td><span class='text-muted'>$sections</span></td>";
	print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'], $_GET['section'], $domain->id)."'>Show VLANs</a></td>";
	//manage
	if($_GET['page']=="administration") {
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='edit'   data-id='$domain->id'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-xs btn-default open_popup' data-script='app/admin/vlans/edit-domain.php' data-class='700' data-action='delete' data-id='$domain->id'><i class='fa fa-times'></i></button>";
	print "	</div>";
	print "	</td>";
	}
	print "	</td>";

	print "</tr>";
}
?>
</tbody>
</table>
