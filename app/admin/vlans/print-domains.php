<!-- add new -->
<div class="btn-group" style="margin-bottom:10px;">
	<button class="btn btn-sm btn-default editVLANdomain" data-action="add" data-domainid="" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add L2 Domain'); ?></button>
</div>


<h4><?php print _("Select l2 domain"); ?></h4>
<hr>

<table class="table table-striped table-condensed table-auto table-auto-wide">
<!-- headers -->
<tr>
	<th><?php print _('Name'); ?></th>
	<th><?php print _('Descripton'); ?></th>
	<th><?php print _('Sections'); ?></th>
	<th></th>
	<th></th>
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
			$sec[] = $tmp_section->name;
		}
		//implode
		$sections = implode("<br>", $sec);
	}

	// print
	print "<tr class='text-top'>";
	print "	<td><a class='btn btn-sm btn-default' href='".create_link("administration", "vlans", $domain->id)."'>$domain->name</a></td>";
	print "	<td>$domain->description</td>";
	print "	<td><span class='text-muted'>$sections</span></td>";
	print "	<td><a href='".create_link("administration", "vlans", $domain->id)."'>Show VLANs</a></td>";
	// actions
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	print "		<button class='btn btn-xs btn-default editVLANdomain' data-action='edit'   data-domainid='$domain->id'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-xs btn-default editVLANdomain' data-action='delete' data-domainid='$domain->id'><i class='fa fa-times'></i></button>";
	print "	</div>";
	print "	</td>";

	print "</tr>";
}
?>
</table>