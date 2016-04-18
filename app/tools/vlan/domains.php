<h4><?php print _("L2 domains"); ?></h4>
<hr>

<!-- Manage link -->
<?php if($User->is_admin()===true) { ?>
<?php if($_GET['page']=="administration") { ?>
<div class="btn-group" style="margin-bottom:10px;">
	<button class="btn btn-sm btn-default editVLANdomain" data-action="add" data-domainid="" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add L2 Domain'); ?></button>
	<button class="btn btn-sm btn-default editVLAN" data-action="add" data-domain="all" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add VLAN'); ?></button>

</div>
<?php } else { ?>
	<a class="btn btn-sm btn-default" href="<?php print create_link("administration", "vlans"); ?>" style='margin-bottom:15px;'><i class='fa fa-pencil'></i> <?php print _("Manage"); ?></a>
<?php } ?>
<?php } ?>


<table class="table table-striped table-top table-condensed table-auto table-auto-wide">
<!-- headers -->
<tr>
	<th><?php print _('Name'); ?></th>
	<th><?php print _('Description'); ?></th>
	<th><?php print _('Sections'); ?></th>
	<th></th>
	<?php if($_GET['page']=="administration") { ?><th></th><?php } ?>
</tr>

<!-- all domains -->
<tr>
	<th style="padding: 10px;"><a href="<?php print create_link($_GET['page'], $_GET['section'], "all"); ?>" class="btn btn-sm btn-default"><i class='fa fa-list'></i> <?php print _('All domains'); ?></a></th>
	<th style="padding: 10px;padding-top: 13px;" colspan="<?php print $_GET['page']=="administration" ? 3 : 2; ?>"><?php print _('List of all VLANs in all domains'); ?></th>
	<?php if($User->is_admin()===true) { ?><th></th><?php } ?>
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
	print "	<td><strong><a href='".create_link($_GET['page'], $_GET['section'], $domain->id)."'><span class='btn btn-xs btn-default'><i class='fa fa-list'></i></span> $domain->name</a></strong></td>";
	print "	<td>$domain->description</td>";
	print "	<td><span class='text-muted'>$sections</span></td>";
	print "	<td><a class='btn btn-sm btn-default' href='".create_link($_GET['page'], $_GET['section'], $domain->id)."'>Show VLANs</a></td>";
	//manage
	if($_GET['page']=="administration") {
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	print "		<button class='btn btn-xs btn-default editVLANdomain' data-action='edit'   data-domainid='$domain->id'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-xs btn-default editVLANdomain' data-action='delete' data-domainid='$domain->id'><i class='fa fa-times'></i></button>";
	print "	</div>";
	print "	</td>";
	}
	print "	</td>";

	print "</tr>";
}
?>
</table>
