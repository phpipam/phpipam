<h4><?php print _("Select l2 domain"); ?></h4>
<hr>

<table class="table table-striped table-condensed table-auto table-auto-wide">
<!-- headers -->
<tr>
	<th><?php print _('Name'); ?></th>
	<th><?php print _('Descripton'); ?></th>
	<th><?php print _('Sections'); ?></th>
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
	print "	<td><a class='btn btn-sm btn-default' href='".create_link("tools", "vlan", $domain->id)."'>$domain->name</a></td>";
	print "	<td>$domain->description</td>";
	print "	<td><span class='text-muted'>$sections</span></td>";
	print "	<td><a href='".create_link("tools", "vlan", $domain->id)."'>Show VLANs</a></td>";
	print "	</td>";

	print "</tr>";
}
?>
</table>